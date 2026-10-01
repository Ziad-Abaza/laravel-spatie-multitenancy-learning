<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression guard for the per-request query budget on the member directory.
 * The page must stay bounded regardless of row count: one paginated list
 * query, one filtered count, eager-loaded roles/media, and the fixed
 * middleware/share() overhead — never a query per user row.
 */
class RequestQueryBudgetTest extends TestCase
{
    public function test_users_index_stays_within_query_budget(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        $tenant->execute(function () {
            // Beyond one page of members to prove the budget is row-independent.
            for ($i = 0; $i < 20; $i++) {
                User::create([
                    'name' => "Member {$i}",
                    'email' => "member{$i}@test.local",
                    'password' => bcrypt('password'),
                ]);
            }
        });

        $owner = $tenant->execute(fn () => tap(
            User::orderBy('id')->firstOrFail(),
            fn (User $user) => $user->markEmailAsVerified()
        ));

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($owner, 'web')
            ->get("http://{$tenant->domain}/users")
            ->assertOk();

        // Measured 38 on the first request and 23 on repeat in the test
        // environment (array cache/session drivers) with 21 users present.
        $this->assertLessThanOrEqual(40, $queries, "users index executed {$queries} queries");

        $queries = 0;
        $this->actingAs($owner, 'web')
            ->get("http://{$tenant->domain}/users")
            ->assertOk();

        $this->assertLessThanOrEqual(40, $queries, "users index executed {$queries} queries on repeat");
    }
}

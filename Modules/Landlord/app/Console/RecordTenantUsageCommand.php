<?php

namespace Modules\Landlord\Console;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\UsageRecord;

/**
 * Scheduled usage metering: snapshot per-tenant aggregates into
 * `usage_records` for billing/fair-use history. Tenant reads happen inside
 * `execute()` and return scalars only — the landlord-side write never sits
 * inside a tenant-context transaction.
 */
class RecordTenantUsageCommand extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    protected $signature = 'tenants:record-usage';

    protected $description = 'Snapshot per-tenant usage aggregates into usage_records';

    public function handle(): int
    {
        $failed = 0;
        $recordedAt = now();

        Tenant::query()
            ->whereIn('status', [TenantStatus::Active->value, TenantStatus::Trialing->value])
            ->get()
            ->each(function (Tenant $tenant) use (&$failed, $recordedAt) {
                try {
                    // Scalars leave the tenant context; writes stay landlord-side.
                    $metrics = $tenant->execute(fn () => [
                        UsageRecord::METRIC_USERS => User::count(),
                        UsageRecord::METRIC_STORAGE_BYTES => (int) DB::table('media')
                            ->selectRaw('COALESCE(SUM(size), 0) as bytes')
                            ->value('bytes'),
                    ]);

                    // One INSERT per tenant, not per metric row.
                    UsageRecord::insert(
                        collect($metrics)->map(fn ($value, $metric) => [
                            'tenant_id' => $tenant->id,
                            'metric' => $metric,
                            'value' => $value,
                            'recorded_at' => $recordedAt,
                        ])->all()
                    );
                } catch (\Throwable $e) {
                    $failed++;
                    report($e);
                    $this->components->warn("Usage snapshot failed for {$tenant->domain}: {$e->getMessage()}");
                }
            });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

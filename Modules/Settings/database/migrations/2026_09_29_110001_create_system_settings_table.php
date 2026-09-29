<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        if (! Schema::connection($connection)->hasTable('settings')) {
            Schema::connection($connection)->create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->default('system')->index();
                $table->string('key')->index();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->boolean('is_public')->default(false);
                $table->timestamps();

                $table->unique(['domain', 'key']);
            });
        } else {
            Schema::connection($connection)->table('settings', function (Blueprint $table) use ($connection) {
                if (! Schema::connection($connection)->hasColumn('settings', 'domain')) {
                    $table->string('domain')->default('system')->index()->after('id');
                }
                if (! Schema::connection($connection)->hasColumn('settings', 'is_public')) {
                    $table->boolean('is_public')->default(false)->after('type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->dropIfExists('settings');
    }
};

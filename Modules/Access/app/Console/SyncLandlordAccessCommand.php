<?php

namespace Modules\Access\Console;

use Illuminate\Console\Command;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Services\AccessInvariants;
use Modules\Access\Services\AuditWriter;
use Modules\Access\Support\LandlordPermissions;
use Modules\Landlord\Models\LandlordUser;

/**
 * Backfills the platform permission catalog and baseline roles on the
 * landlord connection. With --repair it additionally restores the management
 * invariant when the platform has zero active management principals —
 * promoting the earliest ACTIVE landlord administrator, never creating an
 * account or touching passwords. Every repair writes an audit record.
 */
class SyncLandlordAccessCommand extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    protected $signature = 'access:sync-landlord {--repair : Restore the management baseline when no active principal remains}';

    protected $description = 'Sync platform permission catalog and roles on the landlord connection';

    public function handle(): int
    {
        app(AccessBaselineProvisioner::class)->ensureLandlordBaseline();
        $this->info('Landlord access baseline synced.');

        if (! $this->option('repair')) {
            return self::SUCCESS;
        }

        if (AccessInvariants::principalsQuery('landlord')->exists()) {
            $this->info('Management invariant intact — nothing to repair.');

            return self::SUCCESS;
        }

        $admin = LandlordUser::where('status', 'active')->orderBy('id')->first();

        if (! $admin) {
            $this->error('No active landlord administrator exists — manual provisioning required.');

            return self::FAILURE;
        }

        $admin->assignRole(LandlordPermissions::ROLE_SUPER_ADMIN);

        app(AuditWriter::class)->record(
            null,
            'landlord',
            'system.repair',
            'admin_user',
            $admin,
            after: ['restored_role' => LandlordPermissions::ROLE_SUPER_ADMIN]
        );

        $this->warn("Management baseline was broken — promoted {$admin->email} to Super Admin (audited).");

        return self::SUCCESS;
    }
}

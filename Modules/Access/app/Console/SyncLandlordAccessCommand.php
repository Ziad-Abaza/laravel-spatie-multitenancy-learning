<?php

namespace Modules\Access\Console;

use Illuminate\Console\Command;
use Modules\Access\Services\AccessBaselineProvisioner;

/**
 * Backfills the platform (landlord-side) permission catalog and baseline
 * roles on the landlord connection. Idempotent.
 */
class SyncLandlordAccessCommand extends Command
{
    protected $signature = 'access:sync-landlord';

    protected $description = 'Sync platform permission catalog and roles on the landlord connection';

    public function handle(): int
    {
        app(AccessBaselineProvisioner::class)->ensureLandlordBaseline();
        $this->info('Landlord access baseline synced.');

        return self::SUCCESS;
    }
}

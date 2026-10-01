<?php

namespace Modules\Landlord\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Process;
use Modules\Landlord\Models\TenantBackup;

/**
 * Creates and removes tenant database backups. Dumps are landlord-side
 * artifacts under `storage/app/backups/{slug}/` — they use the configured
 * connection credentials, never anything stored on the tenant row, and are
 * never exposed to tenant hosts.
 */
class TenantBackupService
{
    /**
     * Dump the tenant database to the backup registry directory.
     */
    public function create(Tenant $tenant): TenantBackup
    {
        $connection = config('multitenancy.tenant_database_connection_name', 'tenant');
        $driver = config("database.connections.{$connection}.driver", 'sqlite');

        $directory = "backups/{$tenant->slug}";
        $target = storage_path("app/{$directory}");

        if (! is_dir($target) && ! mkdir($target, 0755, true)) {
            throw new \RuntimeException("Cannot create backup directory [{$target}].");
        }

        $filename = "{$tenant->slug}-".now()->format('Ymd-His');
        $path = $driver === 'sqlite'
            ? $this->dumpSqlite($tenant, $target, $filename)
            : $this->dumpMysql($tenant, $connection, $target, $filename);

        return TenantBackup::create([
            'tenant_id' => $tenant->id,
            'driver' => $driver,
            'path' => "{$directory}/".basename($path),
            'size' => filesize($path) ?: 0,
            'status' => TenantBackup::STATUS_COMPLETED,
            'created_at' => now(),
        ]);
    }

    /**
     * Remove the registry row and its artifact on disk.
     */
    public function delete(TenantBackup $backup): void
    {
        $path = $backup->absolutePath();

        if (is_file($path) && ! unlink($path)) {
            throw new \RuntimeException("Failed to delete backup file [{$path}].");
        }

        $backup->delete();
    }

    /**
     * Remove a tenant's entire backup directory — called when the tenant
     * itself is deleted so artifacts do not outlive the registry row.
     */
    public function purgeTenantDirectory(Tenant $tenant): void
    {
        $directory = storage_path("app/backups/{$tenant->slug}");

        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($directory);
    }

    private function dumpSqlite(Tenant $tenant, string $target, string $filename): string
    {
        $source = $tenant->database;

        if (! is_file($source)) {
            throw new \RuntimeException("Tenant database file not found [{$source}].");
        }

        $destination = "{$target}/{$filename}.sqlite";

        if (! copy($source, $destination)) {
            throw new \RuntimeException("Failed to copy database to [{$destination}].");
        }

        return $destination;
    }

    private function dumpMysql(Tenant $tenant, string $connection, string $target, string $filename): string
    {
        $config = config("database.connections.{$connection}");
        $destination = "{$target}/{$filename}.sql";

        $result = Process::run([
            'mysqldump',
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.($config['username'] ?? ''),
            '--password='.($config['password'] ?? ''),
            '--result-file='.$destination,
            $tenant->database,
        ]);

        throw_unless(
            $result->successful() && is_file($destination),
            \RuntimeException::class,
            'mysqldump failed: '.$result->errorOutput()
        );

        return $destination;
    }
}

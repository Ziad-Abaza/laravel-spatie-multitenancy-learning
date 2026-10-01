<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Access\Services\AuditWriter;
use Modules\Landlord\Models\TenantBackup;
use Modules\Landlord\Services\TenantBackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Landlord-side backup actions for a single tenant. Downloads are
 * signed-URL only — artifacts are never served on tenant hosts.
 */
class TenantBackupController extends Controller
{
    public function __construct(
        protected TenantBackupService $backups,
        protected AuditWriter $audit
    ) {}

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        try {
            $backup = $this->backups->create($tenant);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('backup_failed', ['reason' => $e->getMessage()]));
        }

        $this->audit->record(
            $request->user('landlord'), 'landlord', 'tenant.backup_created', 'tenant', $tenant,
            $tenant->name,
            after: ['backup_id' => $backup->id, 'size' => $backup->size],
        );

        return back()->with('success', __('backup_created'));
    }

    public function download(Tenant $tenant, TenantBackup $backup): BinaryFileResponse
    {
        abort_unless($backup->tenant_id === $tenant->id, 404);

        $path = $backup->absolutePath();
        abort_unless(is_file($path), 404);

        $this->audit->record(
            request()->user('landlord'), 'landlord', 'tenant.backup_downloaded', 'tenant', $tenant,
            $tenant->name,
            after: ['backup_id' => $backup->id],
        );

        return response()->download($path, basename($path));
    }

    public function destroy(Request $request, Tenant $tenant, TenantBackup $backup): RedirectResponse
    {
        abort_unless($backup->tenant_id === $tenant->id, 404);

        $this->backups->delete($backup);

        $this->audit->record(
            $request->user('landlord'), 'landlord', 'tenant.backup_deleted', 'tenant', $tenant,
            $tenant->name,
            before: ['backup_id' => $backup->id, 'size' => $backup->size],
        );

        return back()->with('success', __('backup_deleted'));
    }
}

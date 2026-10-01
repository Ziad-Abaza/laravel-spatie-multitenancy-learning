<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Landlord\Models\AdminAuditLog;

/**
 * Read-only viewer for the platform audit trail. Append-only by design —
 * there is intentionally no edit/delete surface here.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AdminAuditLog::query()
            ->when($request->query('action'), fn ($q, string $a) => $q->where('action', $a))
            ->when($request->query('search'), fn ($q, string $s) => $q->where(fn ($q) => $q
                ->where('actor_label', 'like', "%{$s}%")
                ->orWhere('target_label', 'like', "%{$s}%")))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Landlord/Audit/Index', [
            'logs' => $logs,
            'actions' => AdminAuditLog::ACTIONS,
            'filters' => $request->only(['action', 'search']),
        ]);
    }
}

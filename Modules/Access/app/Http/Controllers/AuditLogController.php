<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Models\AuditLog;

/**
 * Read-only viewer for the tenant audit trail. Append-only by design —
 * there is intentionally no edit/delete surface here.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->when($request->query('action'), fn ($q, string $a) => $q->where('action', $a))
            ->when($request->query('search'), fn ($q, string $s) => $q->where(fn ($q) => $q
                ->where('actor_label', 'like', "%{$s}%")
                ->orWhere('target_label', 'like', "%{$s}%")))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Access/Audit/Index', [
            'logs' => $logs,
            'actions' => AuditLog::ACTIONS,
            'filters' => $request->only(['action', 'search']),
        ]);
    }
}

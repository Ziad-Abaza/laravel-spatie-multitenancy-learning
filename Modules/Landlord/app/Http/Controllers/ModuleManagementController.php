<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Nwidart\Modules\Facades\Module;

class ModuleManagementController extends Controller
{
    /**
     * Display a listing of all registered modular-monolith modules.
     */
    /**
     * Modules that are platform infrastructure and can never be toggled off.
     * The SSoT consumed by both index() and toggle().
     *
     * @var array<int, string>
     */
    private const LOCKED_MODULES = ['core', 'landlord', 'access', 'subscription', 'settings', 'tenant'];

    public function index(): Response
    {
        $modules = collect(Module::all())->map(function ($mod) {
            return [
                'name' => $mod->getName(),
                'lower_name' => $mod->getLowerName(),
                'description' => $mod->getDescription() ?: 'Self-contained modular domain',
                'is_enabled' => $mod->isEnabled(),
                'is_locked' => in_array($mod->getLowerName(), self::LOCKED_MODULES, true),
                'priority' => $mod->getPriority(),
                'path' => $mod->getPath(),
            ];
        })->values();

        return Inertia::render('Landlord/Modules/Index', [
            'modules' => $modules,
        ]);
    }

    /**
     * Toggle enabled state of a module.
     */
    public function toggle(string $name): RedirectResponse
    {
        $module = Module::find($name);

        if (! $module) {
            return back()->with('error', __('module_not_found', ['name' => $name]));
        }

        if (in_array(strtolower($name), self::LOCKED_MODULES, true)) {
            return back()->with('warning', __('core_module_locked', ['name' => $name]));
        }

        if ($module->isEnabled()) {
            $module->disable();

            return back()->with('success', __('module_disabled', ['name' => $name]));
        } else {
            $module->enable();

            return back()->with('success', __('module_enabled', ['name' => $name]));
        }
    }
}

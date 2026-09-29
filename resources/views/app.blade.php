<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($tenant = \App\Models\Tenant::current()) ? $tenant->name : 'Landlord' }} - Laravel Multi-Tenant</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700,800|inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Cairo', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans selection:bg-indigo-500 selection:text-white">

    @php
        $tenant = \App\Models\Tenant::current();
        $isLandlord = ! $tenant || $tenant->domain === 'localhost' || $tenant->database === config('database.connections.landlord.database');
        
        // Fetch all tenants from landlord connection safely
        try {
            $allTenants = \App\Models\Tenant::all();
        } catch (\Throwable $e) {
            $allTenants = collect();
        }

        $activeDbName = config('database.connections.tenant.database') ?: config('database.connections.landlord.database');
        $activeConnection = $tenant ? 'tenant' : config('database.default', 'landlord');

        // Safely fetch users if tenant database is selected
        $users = collect();
        $usersCount = 0;
        if (! empty(config('database.connections.tenant.database'))) {
            try {
                if (\Illuminate\Support\Facades\Schema::connection('tenant')->hasTable('users')) {
                    $users = \App\Models\User::latest()->take(10)->get();
                    $usersCount = \App\Models\User::count();
                }
            } catch (\Throwable $e) {
                // Table might not be migrated yet for this tenant
            }
        } elseif ($isLandlord) {
            try {
                if (\Illuminate\Support\Facades\Schema::connection('landlord')->hasTable('users')) {
                    $users = \DB::connection('landlord')->table('users')->latest()->take(10)->get();
                    $usersCount = \DB::connection('landlord')->table('users')->count();
                }
            } catch (\Throwable $e) {}
        }

        $currentPort = request()->getPort();
        $portSuffix = ($currentPort && ! in_array($currentPort, [80, 443])) ? ":{$currentPort}" : '';
    @endphp

    <!-- Navigation Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold text-white tracking-wide">Laravel Multitenancy</h1>
                    <p class="text-xs text-slate-400">Multi-Database Architecture (Spatie)</p>
                </div>
            </div>

            <!-- Context Badge -->
            <div>
                @if ($isLandlord)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        المنصة الرئيسية (Landlord)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <span class="h-2 w-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        مستأجر نشط (Tenant)
                    </span>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Hero Status Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-b from-slate-900 via-slate-900/90 to-slate-950 border border-slate-800 p-6 sm:p-8 shadow-xl">
            <div class="absolute top-0 right-0 -mt-12 -mr-12 w-64 h-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-64 h-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-400">
                            {{ $isLandlord ? 'Central Environment' : 'Tenant Isolated Environment' }}
                        </span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">
                        {{ $tenant ? $tenant->name : 'قاعدة بيانات الـ Landlord الرئيسية' }}
                    </h2>
                    <p class="text-slate-400 text-sm flex items-center gap-2 font-mono" dir="ltr">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span>{{ request()->getHost() }}{{ $portSuffix }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="px-4 py-3 rounded-xl bg-slate-800/60 border border-slate-700/60 text-right">
                        <span class="block text-xs text-slate-400 font-medium">قاعدة البيانات النشطة</span>
                        <span class="font-mono text-sm font-bold text-emerald-400" dir="ltr">{{ $activeDbName }}</span>
                    </div>
                    <div class="px-4 py-3 rounded-xl bg-slate-800/60 border border-slate-700/60 text-right">
                        <span class="block text-xs text-slate-400 font-medium">نوع الاتصال</span>
                        <span class="font-mono text-sm font-bold text-indigo-400" dir="ltr">{{ $activeConnection }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Tenant Switcher Bar -->
        <div class="rounded-xl bg-slate-900/80 border border-slate-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <h3 class="text-sm font-bold text-slate-200">التبديل السريع بين المستأجرين (Tenant Switcher)</h3>
                </div>
                <span class="text-xs text-slate-500">اضغط على أي نطاق لتجربة التبديل الحي</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach ($allTenants as $t)
                    @php
                        $isActive = request()->getHost() === $t->domain;
                        $targetUrl = "http://{$t->domain}{$portSuffix}/";
                    @endphp
                    <a href="{{ $targetUrl }}"
                       class="group relative flex items-center justify-between p-3.5 rounded-xl border transition-all duration-200 {{ $isActive ? 'bg-indigo-600/15 border-indigo-500/50 text-white shadow-lg shadow-indigo-500/10' : 'bg-slate-800/40 border-slate-800/90 text-slate-300 hover:bg-slate-800 hover:border-slate-700' }}">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center font-bold text-xs {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-slate-700/60 text-slate-300 group-hover:bg-slate-700' }}">
                                {{ $t->id }}
                            </div>
                            <div>
                                <span class="block text-sm font-bold leading-tight">{{ $t->name }}</span>
                                <span class="block text-xs font-mono text-slate-400 mt-0.5" dir="ltr">{{ $t->domain }}</span>
                            </div>
                        </div>

                        <div class="text-left">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-mono font-medium {{ $isActive ? 'bg-indigo-500/20 text-indigo-300' : 'bg-slate-900 text-slate-400' }}">
                                {{ $t->database }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Card 1: Tenant ID & Name -->
            <div class="rounded-xl bg-slate-900/60 border border-slate-800 p-5">
                <span class="text-xs text-slate-400 block mb-1">المعرف والاسم</span>
                <div class="text-lg font-bold text-white flex items-center gap-2">
                    <span>{{ $tenant ? $tenant->name : 'Landlord Platform' }}</span>
                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-400">ID: {{ $tenant?->id ?? 'Root' }}</span>
                </div>
                <div class="mt-3 text-xs text-slate-500 flex items-center justify-between border-t border-slate-800/80 pt-2">
                    <span>النطاق المسجل:</span>
                    <span class="font-mono text-slate-300" dir="ltr">{{ $tenant?->domain ?? 'localhost' }}</span>
                </div>
            </div>

            <!-- Card 2: Database Isolation -->
            <div class="rounded-xl bg-slate-900/60 border border-slate-800 p-5">
                <span class="text-xs text-slate-400 block mb-1">عزل قاعدة البيانات (DB Isolation)</span>
                <div class="text-lg font-bold text-emerald-400 font-mono" dir="ltr">
                    {{ $activeDbName }}
                </div>
                <div class="mt-3 text-xs text-slate-500 flex items-center justify-between border-t border-slate-800/80 pt-2">
                    <span>الاتصال الحالي:</span>
                    <span class="font-mono text-slate-300" dir="ltr">{{ $activeConnection }}</span>
                </div>
            </div>

            <!-- Card 3: Users in this Database -->
            <div class="rounded-xl bg-slate-900/60 border border-slate-800 p-5">
                <span class="text-xs text-slate-400 block mb-1">المستخدمين في هذه القاعدة فقط</span>
                <div class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="text-2xl font-extrabold text-indigo-400 font-mono">{{ $usersCount }}</span>
                    <span class="text-xs text-slate-400">مستخدمين مسجلين</span>
                </div>
                <div class="mt-3 text-xs text-slate-500 flex items-center justify-between border-t border-slate-800/80 pt-2">
                    <span>النموذج:</span>
                    <span class="font-mono text-slate-300" dir="ltr">App\Models\User</span>
                </div>
            </div>
        </div>

        <!-- Users Table in Active Tenant -->
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 overflow-hidden shadow-lg">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">المستخدمون في قاعدة البيانات الحالية (<span class="font-mono text-emerald-400" dir="ltr">{{ $activeDbName }}</span>)</h3>
                    <p class="text-xs text-slate-400 mt-0.5">هؤلاء المستخدمون خاصون بهذا المستأجر فقط ومعزولون تماماً عن بقية المستأجرين.</p>
                </div>
                <span class="text-xs font-mono bg-slate-800 px-2.5 py-1 rounded-md text-slate-300">Total: {{ $usersCount }}</span>
            </div>

            @if ($users->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-950/60 text-slate-400 text-xs font-semibold uppercase border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-3"># ID</th>
                                <th class="px-6 py-3">الاسم (Name)</th>
                                <th class="px-6 py-3">البريد الإلكتروني (Email)</th>
                                <th class="px-6 py-3">تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            @foreach ($users as $user)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-3.5 text-slate-400 font-medium">{{ $user->id }}</td>
                                    <td class="px-6 py-3.5 text-white font-sans font-semibold">{{ $user->name }}</td>
                                    <td class="px-6 py-3.5 text-indigo-300" dir="ltr">{{ $user->email }}</td>
                                    <td class="px-6 py-3.5 text-slate-400 text-xs" dir="ltr">
                                        {{ isset($user->created_at) ? (is_string($user->created_at) ? $user->created_at : $user->created_at->format('Y-m-d H:i')) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 px-4">
                    <div class="h-12 w-12 rounded-full bg-slate-800/80 text-slate-500 mx-auto flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-300">لا يوجد مستخدمون حالياً في قاعدة بيانات هذا المستأجر</h4>
                    <p class="text-xs text-slate-500 mt-1">يمكنك زراعة المستخدمين لكل مستأجر باستخدام الأمر: <code class="bg-slate-800 px-1.5 py-0.5 rounded text-indigo-300 font-mono">php artisan tenants:artisan "db:seed"</code></p>
                </div>
            @endif
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950 py-4 text-center text-xs text-slate-600">
        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }}) &bull; Spatie Laravel Multitenancy v4
    </footer>

</body>
</html>

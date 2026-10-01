<?php

namespace Modules\Access\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Modules\Access\Console\SyncLandlordAccessCommand;
use Modules\Access\Console\SyncTenantAccessCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Spatie\Multitenancy\Models\Tenant;

class AccessServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Access';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'access';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        SyncTenantAccessCommand::class,
        SyncLandlordAccessCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function boot(): void
    {
        parent::boot();

        // Reset links must point at the tenant host the user actually
        // authenticates on — APP_URL (landlord host) would produce a link
        // that can never resolve a tenant session.
        ResetPassword::createUrlUsing(function (object $user, string $token) {
            $domain = Tenant::current()?->domain ?? request()?->getHost() ?? 'localhost';

            return 'http://'.$domain.route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false);
        });

        // Same constraint as reset links: verification URLs must resolve on
        // the tenant host. The signed route is generated relative so the
        // signature is host-agnostic, then prefixed with the tenant domain.
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $domain = Tenant::current()?->domain ?? request()?->getHost() ?? 'localhost';

            $relative = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false
            );

            return 'http://'.$domain.$relative;
        });
    }
}

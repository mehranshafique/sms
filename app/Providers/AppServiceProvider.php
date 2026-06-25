<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate; // <--- IMPORTANT: Don't forget this!
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Services\InAppNotificationService;
use App\Services\PlanContextService;
use Illuminate\Support\Facades\File;
use App\Policies\ResourcePolicy;
use App\Interfaces\SmsGatewayInterface;
use App\Services\Sms\InfobipService;
use App\Services\Sms\MobishastraService;
use App\Services\SystemCommunicationConfigService;
use App\Services\InstitutionSetupAlertService;
use App\Services\SidebarMenuBadgeService;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        app(SystemCommunicationConfigService::class)->applyGlobalOverrides();

        // --- Dynamic Policy Registration Start ---
        $modelsPath = app_path('Models');

        if (File::isDirectory($modelsPath)) {
            $files = File::files($modelsPath);

            // Models to exclude
            $excludedModels = ['User', 'Permission', 'Role', 'ActivityLog']; 

            foreach ($files as $file) {
                $modelName = $file->getFilenameWithoutExtension();

                if (!in_array($modelName, $excludedModels)) {
                    $modelClass = "App\\Models\\{$modelName}";

                    if (class_exists($modelClass)) {
                        // Register the policy using the Gate facade
                        Gate::policy($modelClass, ResourcePolicy::class);
                    }
                }
            }
        }
        // --- Dynamic Policy Registration End ---

        Gate::before(function ($user, string $ability) {
            if ($user && $user->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value)) {
                return true;
            }

            return null;
        });

        View::composer('*', function ($view) {
            if (Auth::check()) {
                $view->with('planCtx', app(PlanContextService::class)->snapshot());
            }
        });

        View::composer(['layout.header', 'layout.partials.in-app-notification-scripts'], function ($view) {
            if (Auth::check()) {
                $service = app(InAppNotificationService::class);
                $view->with('inAppNotifications', $service->getRecent(Auth::id(), 12));
                $view->with('inAppUnreadCount', $service->getUnreadCount(Auth::id()));
            }
        });

        View::composer(['layout.sidebar'], function ($view) {
            if (Auth::check()) {
                $view->with(
                    'sidebarBadges',
                    app(SidebarMenuBadgeService::class)->countsForUser(Auth::id())
                );
            }
        });

        View::composer(['layout.layout', 'layout.partials.setup-alerts'], function ($view) {
            if (Auth::check()) {
                $view->with(
                    'setupAlerts',
                    app(InstitutionSetupAlertService::class)->getAlertsForUser(Auth::user())
                );
            }
        });

        // Bind the SMS Interface based on Config
        $this->app->bind(SmsGatewayInterface::class, function ($app) {
            $driver = config('sms.default', 'log');

            switch ($driver) {
                case 'infobip':
                    return new InfobipService();
                case 'mobishastra':
                    return new MobishastraService();
                default:
                    // Dummy implementation for testing/log
                    return new class implements SmsGatewayInterface {
                        public function send(string $to, string $message): bool {
                            Log::info("SMS Mock to $to: $message");
                            return true;
                        }
                    };
            }
        });
    }
}

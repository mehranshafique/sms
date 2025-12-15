<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate; // <--- IMPORTANT: Don't forget this!
use Illuminate\Support\Facades\File;
use App\Policies\ResourcePolicy;

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
    }
}

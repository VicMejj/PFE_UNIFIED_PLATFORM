<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerModelNamespaceAliases();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function registerModelNamespaceAliases(): void
    {
        $modelsPath = app_path('Models');

        if (!is_dir($modelsPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelsPath));

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($modelsPath . DIRECTORY_SEPARATOR, '', $fileInfo->getPathname());
            $relativeClass = str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            if (!str_contains($relativeClass, '\\')) {
                continue;
            }

            $actualClass = 'App\\Models\\' . $relativeClass;
            $legacyClass = 'App\\Models\\' . class_basename($relativeClass);

            if (class_exists($legacyClass, false) || !class_exists($actualClass)) {
                continue;
            }

            class_alias($actualClass, $legacyClass);
        }
    }
}

<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the SemVer package.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SemVerServiceProvider extends PackageServiceProvider
{
    /**
     * Register package services.
     *
     * Laravel 10 compatibility: manually register SemVerManager as singleton
     * (Laravel 11+ uses #[Singleton] attribute which is ignored in Laravel 10).
     */
    #[Override()]
    public function registeringPackage(): void
    {
        $this->app->singleton(SemVerManager::class);
    }

    /**
     * Configure the package settings.
     *
     * @param Package $package The package configuration instance
     */
    public function configurePackage(Package $package): void
    {
        $package->name('semver');
    }
}

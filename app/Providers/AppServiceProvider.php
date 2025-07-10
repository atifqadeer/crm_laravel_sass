<?php

namespace App\Providers;

use Horsefly\Applicant;
use App\Observers\ApplicantObserver;
use Horsefly\Sale;
use App\Observers\SaleObserver;
use Horsefly\Office;
use App\Observers\HeadOfficeObserver;
use Horsefly\Unit;
use App\Observers\UnitObserver;
use Horsefly\User;
use App\Observers\UserObserver;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\ServiceProvider;

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
        // Register the Applicant observer
        Applicant::observe(ApplicantObserver::class);
        Sale::observe(SaleObserver::class);
        Office::observe(HeadOfficeObserver::class);
        Unit::observe(UnitObserver::class);
        User::observe(UserObserver::class);
        Paginator::useBootstrapFive(); // or Paginator::useBootstrapFour();
    }
}

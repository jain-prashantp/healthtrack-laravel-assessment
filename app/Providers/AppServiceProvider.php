<?php

namespace App\Providers;

use App\Policies\DoctorPolicy;
use App\Policies\PatientPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('patient.view-own-record', [PatientPolicy::class, 'view']);
        Gate::define('patient.update-own-record', [PatientPolicy::class, 'update']);
        Gate::define('doctor.view-assigned-patient', [DoctorPolicy::class, 'view']);
        Gate::define('doctor.update-assigned-patient', [DoctorPolicy::class, 'update']);
    }
}

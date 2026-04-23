<?php

namespace App\Providers;

use App\Models\PatientMedication;
use App\Models\PatientProfile;
use App\Models\WellnessAlert;
use App\Models\WellnessCheckin;
use App\Policies\DoctorPolicy;
use App\Policies\PatientMedicationPolicy;
use App\Policies\PatientProfilePolicy;
use App\Policies\PatientPolicy;
use App\Policies\WellnessAlertPolicy;
use App\Policies\WellnessCheckinPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(PatientProfile::class, PatientProfilePolicy::class);
        Gate::policy(WellnessCheckin::class, WellnessCheckinPolicy::class);
        Gate::policy(PatientMedication::class, PatientMedicationPolicy::class);
        Gate::policy(WellnessAlert::class, WellnessAlertPolicy::class);

        Gate::define('patient.view-own-record', [PatientPolicy::class, 'view']);
        Gate::define('patient.update-own-record', [PatientPolicy::class, 'update']);
        Gate::define('doctor.view-assigned-patient', [DoctorPolicy::class, 'view']);
        Gate::define('doctor.update-assigned-patient', [DoctorPolicy::class, 'update']);

        RateLimiter::for('patient-checkins', function (Request $request) {
            return Limit::perHour(3)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('patient-medications', function (Request $request) {
            return Limit::perDay(10)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}

<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Doctor\DoctorAlertController;
use App\Http\Controllers\Api\V1\Doctor\DoctorPatientController;
use App\Http\Controllers\Api\V1\Patient\PatientAlertController;
use App\Http\Controllers\Api\V1\Patient\PatientCheckinController;
use App\Http\Controllers\Api\V1\Patient\PatientMedicationController;
use App\Http\Controllers\Api\V1\Patient\PatientProfileController;
use App\Http\Middleware\EnsureDoctorUser;
use App\Http\Middleware\EnsurePatientUser;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        });
    });

    Route::middleware(['auth:sanctum', EnsurePatientUser::class])
        ->prefix('patient')
        ->group(function () {
            Route::get('/profile', [PatientProfileController::class, 'show'])->name('api.v1.patient.profile.show');
            Route::put('/profile', [PatientProfileController::class, 'update'])->name('api.v1.patient.profile.update');

            Route::post('/checkins', [PatientCheckinController::class, 'store'])
                ->middleware('throttle:patient-checkins')
                ->name('api.v1.patient.checkins.store');
            Route::get('/checkins', [PatientCheckinController::class, 'index'])->name('api.v1.patient.checkins.index');
            Route::get('/checkins/{id}', [PatientCheckinController::class, 'show'])->name('api.v1.patient.checkins.show');

            Route::post('/medications', [PatientMedicationController::class, 'store'])
                ->middleware('throttle:patient-medications')
                ->name('api.v1.patient.medications.store');
            Route::get('/medications', [PatientMedicationController::class, 'index'])->name('api.v1.patient.medications.index');
            Route::delete('/medications/{id}', [PatientMedicationController::class, 'destroy'])
                ->middleware('throttle:patient-medications')
                ->name('api.v1.patient.medications.destroy');

            Route::get('/alerts', [PatientAlertController::class, 'index'])->name('api.v1.patient.alerts.index');
        });

    Route::middleware(['auth:sanctum', EnsureDoctorUser::class])
        ->prefix('doctor')
        ->group(function () {
            Route::get('/patients', [DoctorPatientController::class, 'index'])->name('api.v1.doctor.patients.index');
            Route::get('/patients/{id}/wellness-summary', [DoctorPatientController::class, 'wellnessSummary'])
                ->name('api.v1.doctor.patients.wellness-summary');
            Route::get('/patients/{id}/checkins', [DoctorPatientController::class, 'checkins'])
                ->name('api.v1.doctor.patients.checkins.index');
            Route::get('/patients/{id}/medications', [DoctorPatientController::class, 'medications'])
                ->name('api.v1.doctor.patients.medications.index');
            Route::get('/alerts', [DoctorAlertController::class, 'index'])->name('api.v1.doctor.alerts.index');
            Route::put('/alerts/{id}/read', [DoctorAlertController::class, 'markRead'])
                ->name('api.v1.doctor.alerts.read');
        });
});

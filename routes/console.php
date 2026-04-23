<?php

use App\Jobs\DailyMissedCheckinAlertJob;
use App\Jobs\WeeklyAnalyticsReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new DailyMissedCheckinAlertJob())
    ->dailyAt('08:00')
    ->timezone(config('app.timezone'));

Schedule::job(new WeeklyAnalyticsReportJob())
    ->mondays()
    ->at('07:00')
    ->timezone(config('app.timezone'));

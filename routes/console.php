<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ExpireOldPosts;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule jobs
Schedule::job(new ExpireOldPosts)->daily();

// Clean up expired posts every hour
Schedule::command('posts:cleanup-expired')->hourly();

// Refresh materialized view for place-region associations every 6 hours
Schedule::command('places:refresh-materialized-view')->everySixHours();

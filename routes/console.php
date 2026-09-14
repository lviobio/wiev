<?php

use App\Core\Upload\Console\Commands\PruneChunkedUploadsCommand;
use App\Core\Upload\Console\Commands\PruneTemporaryUploadsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(PruneTemporaryUploadsCommand::class)->hourly();
Schedule::command(PruneChunkedUploadsCommand::class)->hourly();

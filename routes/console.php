<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fetch:accounts')->dailyAt('02:00');
Schedule::command('fetch:properties')->dailyAt('02:00');
Schedule::command('fetch:avalibilities')->dailyAt('02:00');
Schedule::command('fetch:availability-calendars')->everyFiveSeconds();
Schedule::command('fetch:configurations')->dailyAt('02:00');
Schedule::command('fetch:prices')->dailyAt('02:00');
Schedule::command('fetch:property-contents')->dailyAt('02:00');
Schedule::command('fetch:single-property')->dailyAt('02:00');
Schedule::command('fetch:prices-Listing')->everyTwoHours();
Schedule::command('fetch:content-listing')->everyFiveSeconds();
// Schedule::command('payments:charge-final')->dailyAt('02:00');
// Schedule::command('payments:hold-deposit')->everyFiveSeconds();
Schedule::command('payments:charge-remaining')->everyFiveSeconds();
// Schedule::command('deposit:hold')->everyFiveSeconds();
// Schedule::command('deposit:release')->everyFiveSeconds();
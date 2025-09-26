<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fetch:accounts')->dailyAt('02:00');
Schedule::command('fetch:properties')->dailyAt('02:00');
Schedule::command('fetch:avalibilities')->dailyAt('02:00');
Schedule::command('fetch:availability-calendars')->dailyAt('02:00');
Schedule::command('fetch:configurations')->dailyAt('02:00');
Schedule::command('fetch:prices')->dailyAt('02:00');
Schedule::command('fetch:property-contents')->dailyAt('02:00');
Schedule::command('fetch:single-property')->dailyAt('02:00');
Schedule::command('fetch:prices-Listing')->dailyAt('02:00');
Schedule::command('payments:charge-final')->dailyAt('02:00');
Schedule::command('payments:hold-deposit')->dailyAt('02:00');
Schedule::command('payments:charge-remaining')->everyFiveSeconds();
Schedule::command('deposit:hold')->dailyAt('02:00');
Schedule::command('deposit:release')->dailyAt('02:00');
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fetch:accounts')->everyFiveSeconds();
Schedule::command('fetch:properties')->everyFiveSeconds();
Schedule::command('fetch:avalibilities')->everyFiveSeconds();
Schedule::command('fetch:configurations')->everyFiveSeconds();
Schedule::command('fetch:prices')->everyFiveSeconds();
Schedule::command('fetch:property-contents')->everyFiveSeconds();
Schedule::command('fetch:single-property')->everyFiveSeconds();
Schedule::command('fetch:prices-Listing')->hourly();

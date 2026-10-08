<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('rent:generate')->dailyAt('06:05')->timezone('Africa/Kinshasa')->withoutOverlapping();
Schedule::command('rent:remind')->dailyAt('07:00')->timezone('Africa/Kinshasa')->withoutOverlapping();
Schedule::command('rent:move-outs')->dailyAt('07:30')->timezone('Africa/Kinshasa')->withoutOverlapping();

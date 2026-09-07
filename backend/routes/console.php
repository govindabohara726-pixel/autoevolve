<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('autoevolve:evolve')
    ->dailyAt('08:00')
    ->withoutOverlapping();

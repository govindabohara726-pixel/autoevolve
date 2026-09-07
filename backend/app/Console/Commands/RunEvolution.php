<?php

namespace App\Console\Commands;

use App\Services\EvolutionEngine;
use Illuminate\Console\Command;

class RunEvolution extends Command
{
    protected $signature='autoevolve:evolve';
    protected $description='Run one autonomous content and SEO evolution cycle.';
    public function handle(EvolutionEngine $engine): int { $this->line(json_encode($engine->run(),JSON_PRETTY_PRINT)); return self::SUCCESS; }
}

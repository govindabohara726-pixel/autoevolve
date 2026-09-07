<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Services\EvolutionEngine;

class EvolutionController extends Controller
{
    public function index() { return view('admin.evolution.index', ['actions'=>AiAction::with('content:id,title')->latest()->paginate(50)]); }
    public function run(EvolutionEngine $engine) { $result=$engine->run(); return back()->with('success','Evolution cycle completed: '.count($result['improved']).' pages processed, '.$result['opportunities_found'].' opportunities found.'); }
}

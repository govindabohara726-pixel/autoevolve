<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Services\EvolutionEngine;

class OpportunityController extends Controller
{
    public function index() { return view('admin.opportunities.index', ['items'=>Opportunity::orderByDesc('priority')->latest()->paginate(40)]); }
    public function discover(EvolutionEngine $engine) { $count=$engine->discoverOpportunities(); return back()->with('success',"Discovered {$count} opportunities."); }
}

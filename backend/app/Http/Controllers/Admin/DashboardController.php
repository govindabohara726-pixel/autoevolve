<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\ContentItem;
use App\Models\Opportunity;
use App\Models\SiteSetting;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'stats'=>[
                'pages'=>ContentItem::count(),
                'published'=>ContentItem::where('status','published')->count(),
                'pending'=>AiAction::where('status','pending_approval')->count(),
                'opportunities'=>Opportunity::where('status','new')->count(),
            ],
            'settings'=>SiteSetting::current(),
            'actions'=>AiAction::with('content:id,title')->latest()->limit(10)->get(),
        ]);
    }
}

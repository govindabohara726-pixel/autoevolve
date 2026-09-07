<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function home(Request $request)
    {
        $site = Site::where('domain', $this->requestHost($request))->where('status', 'active')->first();
        if ($site) {
            $items = $site->content()->where('status', 'published')->orderByDesc('published_at')->limit(12)->get();
            return view('public.site-home', compact('site', 'items'));
        }

        return view('marketing.home', ['plans' => config('plans.plans', [])]);
    }

    public function privacy() { return view('marketing.privacy'); }
    public function terms() { return view('marketing.terms'); }

    private function requestHost(Request $request): string
    {
        $host = strtolower(trim((string) $request->header('host', $request->getHost())));
        return preg_replace('/:\d+$/', '', $host) ?: $host;
    }
}

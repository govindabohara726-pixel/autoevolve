<?php

use App\Http\Controllers\Admin\SaasController;
use App\Http\Controllers\App\ActivityController;
use App\Http\Controllers\App\AutomationController;
use App\Http\Controllers\App\BillingController;
use App\Http\Controllers\App\ContentController;
use App\Http\Controllers\App\CustomerAuthController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\OnboardingController;
use App\Http\Controllers\App\OpportunityController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\SiteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\PublicSiteController;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class,'home'])->name('home');
Route::get('/privacy', [MarketingController::class,'privacy'])->name('privacy');
Route::get('/terms', [MarketingController::class,'terms'])->name('terms');

Route::get('/setup', function () {
    $db=false; $admins=0;
    try { DB::connection()->getPdo(); $db=true; $admins=User::where('is_admin',true)->count(); } catch (Throwable) {}
    return view('setup',['checks'=>[
        'app_key'=>filled(config('app.key')),
        'database'=>$db,
        'admin'=>$admins>0,
        'ai'=>filled(config('services.ai.key')),
        'stripe'=>filled(config('services.stripe.secret')),
    ],'adminCount'=>$admins]);
})->name('setup');

Route::middleware('guest')->group(function(){
    Route::get('/login',[CustomerAuthController::class,'showLogin'])->name('login');
    Route::post('/login',[CustomerAuthController::class,'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register',[CustomerAuthController::class,'showRegister'])->name('register');
    Route::post('/register',[CustomerAuthController::class,'register'])->middleware('throttle:8,1')->name('register.submit');
});
Route::post('/logout',[CustomerAuthController::class,'logout'])->middleware('auth')->name('logout');

Route::post('/stripe/webhook',[BillingController::class,'webhook'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('stripe.webhook');

Route::middleware('auth')->group(function(){
    Route::get('/app/onboarding',[OnboardingController::class,'show'])->name('app.onboarding');
    Route::post('/app/onboarding',[OnboardingController::class,'store'])->name('app.onboarding.store');
});

Route::prefix('app')->middleware(['auth','workspace'])->group(function(){
    Route::get('/',DashboardController::class)->name('app.dashboard');
    Route::get('/sites',[SiteController::class,'index'])->name('app.sites');
    Route::post('/sites',[SiteController::class,'store'])->middleware('workspace.role:owner,admin')->name('app.sites.store');
    Route::post('/sites/{site}/switch',[SiteController::class,'switch'])->name('app.sites.switch');
    Route::put('/sites/{site}',[SiteController::class,'update'])->middleware('workspace.role:owner,admin')->name('app.sites.update');

    Route::get('/content',[ContentController::class,'index'])->name('app.content.index');
    Route::post('/content/generate',[ContentController::class,'generate'])->middleware('workspace.role:owner,admin,editor')->name('app.content.generate');
    Route::get('/content/{content}/edit',[ContentController::class,'edit'])->name('app.content.edit');
    Route::put('/content/{content}',[ContentController::class,'update'])->middleware('workspace.role:owner,admin,editor')->name('app.content.update');
    Route::post('/content/{content}/improve',[ContentController::class,'improve'])->middleware('workspace.role:owner,admin,editor')->name('app.content.improve');
    Route::post('/content/{content}/publish',[ContentController::class,'publish'])->middleware('workspace.role:owner,admin,editor')->name('app.content.publish');
    Route::post('/revisions/{revision}/approve',[ContentController::class,'approveRevision'])->middleware('workspace.role:owner,admin,editor')->name('app.revisions.approve');
    Route::post('/revisions/{revision}/reject',[ContentController::class,'rejectRevision'])->middleware('workspace.role:owner,admin,editor')->name('app.revisions.reject');
    Route::delete('/content/{content}',[ContentController::class,'destroy'])->middleware('workspace.role:owner,admin')->name('app.content.destroy');

    Route::get('/automation',[AutomationController::class,'index'])->name('app.automation');
    Route::post('/automation/run',[AutomationController::class,'run'])->middleware('workspace.role:owner,admin,editor')->name('app.automation.run');
    Route::get('/opportunities',[OpportunityController::class,'index'])->name('app.opportunities');
    Route::post('/opportunities/discover',[OpportunityController::class,'discover'])->middleware('workspace.role:owner,admin,editor')->name('app.opportunities.discover');
    Route::post('/opportunities/{opportunity}/dismiss',[OpportunityController::class,'dismiss'])->middleware('workspace.role:owner,admin,editor')->name('app.opportunities.dismiss');
    Route::get('/activity',ActivityController::class)->name('app.activity');

    Route::get('/billing',[BillingController::class,'index'])->name('app.billing');
    Route::post('/billing/checkout',[BillingController::class,'checkout'])->middleware('workspace.role:owner')->name('app.billing.checkout');
    Route::post('/billing/portal',[BillingController::class,'portal'])->middleware('workspace.role:owner')->name('app.billing.portal');

    Route::get('/settings',[SettingsController::class,'index'])->name('app.settings');
    Route::put('/settings',[SettingsController::class,'update'])->middleware('workspace.role:owner,admin')->name('app.settings.update');
    Route::post('/settings/members',[SettingsController::class,'addMember'])->middleware('workspace.role:owner,admin')->name('app.settings.members.add');
    Route::delete('/settings/members/{membership}',[SettingsController::class,'removeMember'])->middleware('workspace.role:owner,admin')->name('app.settings.members.remove');
    Route::post('/settings/api-keys',[SettingsController::class,'createApiKey'])->middleware('workspace.role:owner,admin')->name('app.settings.api-keys.create');
    Route::post('/settings/api-keys/{apiKey}/revoke',[SettingsController::class,'revokeApiKey'])->middleware('workspace.role:owner,admin')->name('app.settings.api-keys.revoke');
});

Route::get('/s/{site:slug}',[PublicSiteController::class,'index'])->name('public.site');
Route::get('/s/{site:slug}/sitemap.xml',[PublicSiteController::class,'sitemap'])->name('public.sitemap');
Route::get('/s/{site:slug}/{slug}',[PublicSiteController::class,'show'])->name('public.article');

Route::get('/admin/login',[AuthController::class,'showLogin'])->name('admin.login');
Route::post('/admin/login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('admin.login.submit');
Route::post('/admin/logout',[AuthController::class,'logout'])->name('admin.logout');
Route::prefix('admin')->middleware(['auth','admin'])->group(function () {
    Route::get('/',[SaasController::class,'index'])->name('admin.dashboard');
    Route::get('/workspaces/{workspace}',[SaasController::class,'show'])->name('admin.workspaces.show');
    Route::post('/workspaces/{workspace}/toggle',[SaasController::class,'toggle'])->name('admin.workspaces.toggle');
});

// Custom-domain article route. Specific application/marketing routes above always win first.
Route::get('/{slug}',[PublicSiteController::class,'customDomainArticle'])->where('slug','[A-Za-z0-9][A-Za-z0-9\-_]*');

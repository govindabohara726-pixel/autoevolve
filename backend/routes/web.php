<?php

use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvolutionController as AdminEvolutionController;
use App\Http\Controllers\Admin\OpportunityController as AdminOpportunityController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
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
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn()=>redirect(config('services.frontend_url')));
Route::get('/setup', function () {
    $db=false; $admins=0;
    try { DB::connection()->getPdo(); $db=true; $admins=User::where('is_admin',true)->count(); } catch (Throwable) {}
    return view('setup',['checks'=>['app_key'=>filled(config('app.key')),'database'=>$db,'admin'=>$admins>0,'ai'=>filled(config('services.ai.key')),'frontend'=>filled(config('services.frontend_url')),'stripe'=>filled(config('services.stripe.secret'))],'adminCount'=>$admins]);
})->name('setup');

Route::middleware('guest')->group(function(){
    Route::get('/login',[CustomerAuthController::class,'showLogin'])->name('login');
    Route::post('/login',[CustomerAuthController::class,'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register',[CustomerAuthController::class,'showRegister'])->name('register');
    Route::post('/register',[CustomerAuthController::class,'register'])->middleware('throttle:8,1')->name('register.submit');
});
Route::post('/logout',[CustomerAuthController::class,'logout'])->middleware('auth')->name('logout');
Route::post('/stripe/webhook',[BillingController::class,'webhook'])->name('stripe.webhook');

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
    Route::delete('/content/{content}',[ContentController::class,'destroy'])->middleware('workspace.role:owner,admin')->name('app.content.destroy');

    Route::get('/automation',[AutomationController::class,'index'])->name('app.automation');
    Route::post('/automation/run',[AutomationController::class,'run'])->middleware('workspace.role:owner,admin,editor')->name('app.automation.run');
    Route::get('/opportunities',[OpportunityController::class,'index'])->name('app.opportunities');
    Route::post('/opportunities/discover',[OpportunityController::class,'discover'])->middleware('workspace.role:owner,admin,editor')->name('app.opportunities.discover');
    Route::post('/opportunities/{opportunity}/dismiss',[OpportunityController::class,'dismiss'])->middleware('workspace.role:owner,admin,editor')->name('app.opportunities.dismiss');
    Route::get('/activity',ActivityController::class)->name('app.activity');

    Route::get('/billing',[BillingController::class,'index'])->name('app.billing');
    Route::post('/billing/checkout',[BillingController::class,'checkout'])->name('app.billing.checkout');
    Route::post('/billing/portal',[BillingController::class,'portal'])->name('app.billing.portal');

    Route::get('/settings',[SettingsController::class,'index'])->name('app.settings');
    Route::put('/settings',[SettingsController::class,'update'])->name('app.settings.update');
    Route::post('/settings/members',[SettingsController::class,'addMember'])->name('app.settings.members.add');
    Route::delete('/settings/members/{membership}',[SettingsController::class,'removeMember'])->name('app.settings.members.remove');
    Route::post('/settings/api-keys',[SettingsController::class,'createApiKey'])->name('app.settings.api-keys.create');
    Route::post('/settings/api-keys/{apiKey}/revoke',[SettingsController::class,'revokeApiKey'])->name('app.settings.api-keys.revoke');
});

Route::get('/admin/login',[AuthController::class,'showLogin'])->name('admin.login');
Route::post('/admin/login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('admin.login.submit');
Route::post('/admin/logout',[AuthController::class,'logout'])->name('admin.logout');
Route::prefix('admin')->middleware(['auth','admin'])->group(function () {
    Route::get('/',AdminDashboardController::class)->name('admin.dashboard');
    Route::get('/content',[AdminContentController::class,'index'])->name('admin.content.index');
    Route::post('/content/generate',[AdminContentController::class,'generate'])->name('admin.content.generate');
    Route::get('/content/{content}/edit',[AdminContentController::class,'edit'])->name('admin.content.edit');
    Route::put('/content/{content}',[AdminContentController::class,'update'])->name('admin.content.update');
    Route::post('/content/{content}/improve',[AdminContentController::class,'improve'])->name('admin.content.improve');
    Route::post('/content/{content}/publish',[AdminContentController::class,'publish'])->name('admin.content.publish');
    Route::delete('/content/{content}',[AdminContentController::class,'destroy'])->name('admin.content.destroy');
    Route::get('/evolution',[AdminEvolutionController::class,'index'])->name('admin.evolution');
    Route::post('/evolution/run',[AdminEvolutionController::class,'run'])->name('admin.evolution.run');
    Route::get('/opportunities',[AdminOpportunityController::class,'index'])->name('admin.opportunities');
    Route::post('/opportunities/discover',[AdminOpportunityController::class,'discover'])->name('admin.opportunities.discover');
    Route::get('/settings',[AdminSettingController::class,'index'])->name('admin.settings');
    Route::put('/settings',[AdminSettingController::class,'update'])->name('admin.settings.update');
});

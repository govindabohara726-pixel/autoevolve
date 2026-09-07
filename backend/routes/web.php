<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EvolutionController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn()=>redirect()->route('admin.dashboard'));
Route::get('/setup', function () {
    $db=false; $admins=0;
    try { DB::connection()->getPdo(); $db=true; $admins=User::where('is_admin',true)->count(); } catch (Throwable) {}
    return view('setup',['checks'=>['app_key'=>filled(config('app.key')),'database'=>$db,'admin'=>$admins>0,'ai'=>filled(config('services.ai.key')),'frontend'=>filled(config('services.frontend_url'))],'adminCount'=>$admins]);
})->name('setup');

Route::get('/admin/login',[AuthController::class,'showLogin'])->name('admin.login');
Route::post('/admin/login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('admin.login.submit');
Route::post('/admin/logout',[AuthController::class,'logout'])->name('admin.logout');

Route::prefix('admin')->middleware(['auth','admin'])->group(function () {
    Route::get('/',DashboardController::class)->name('admin.dashboard');
    Route::get('/content',[ContentController::class,'index'])->name('admin.content.index');
    Route::post('/content/generate',[ContentController::class,'generate'])->name('admin.content.generate');
    Route::get('/content/{content}/edit',[ContentController::class,'edit'])->name('admin.content.edit');
    Route::put('/content/{content}',[ContentController::class,'update'])->name('admin.content.update');
    Route::post('/content/{content}/improve',[ContentController::class,'improve'])->name('admin.content.improve');
    Route::post('/content/{content}/publish',[ContentController::class,'publish'])->name('admin.content.publish');
    Route::delete('/content/{content}',[ContentController::class,'destroy'])->name('admin.content.destroy');
    Route::get('/evolution',[EvolutionController::class,'index'])->name('admin.evolution');
    Route::post('/evolution/run',[EvolutionController::class,'run'])->name('admin.evolution.run');
    Route::get('/opportunities',[OpportunityController::class,'index'])->name('admin.opportunities');
    Route::post('/opportunities/discover',[OpportunityController::class,'discover'])->name('admin.opportunities.discover');
    Route::get('/settings',[SettingController::class,'index'])->name('admin.settings');
    Route::put('/settings',[SettingController::class,'update'])->name('admin.settings.update');
});

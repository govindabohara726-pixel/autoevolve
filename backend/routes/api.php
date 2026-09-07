<?php
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\SiteContentController;
use Illuminate\Support\Facades\Route;
Route::get('/health',[ContentController::class,'health']);
Route::get('/content',[ContentController::class,'index']);
Route::get('/content/{slug}',[ContentController::class,'show']);
Route::prefix('public/{workspaceSlug}/{siteSlug}')->group(function(){
    Route::get('/',[SiteContentController::class,'showSite']);
    Route::get('/content',[SiteContentController::class,'index']);
    Route::get('/content/{slug}',[SiteContentController::class,'show']);
});
Route::prefix('v1')->middleware('api.key')->group(function(){ Route::get('/account',[AccountController::class,'show']); });

<?php

use App\Http\Controllers\Api\ContentController;
use Illuminate\Support\Facades\Route;

Route::get('/health',[ContentController::class,'health']);
Route::get('/content',[ContentController::class,'index']);
Route::get('/content/{slug}',[ContentController::class,'show']);

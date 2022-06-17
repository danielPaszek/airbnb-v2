<?php

use App\Http\Controllers\OfficeController;
use App\Http\Controllers\OfficeImageController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/tags', TagController::class);

//Office
Route::get('/offices', [OfficeController::class, 'index']);
Route::post('/offices', [OfficeController::class, 'create'])
    ->middleware(['auth:sanctum', 'verified']);
Route::put('/offices/{office}', [OfficeController::class, 'update'])
    ->middleware(['auth:sanctum', 'verified']);
Route::delete('/offices/{office}', [OfficeController::class, 'delete'])
    ->middleware(['auth:sanctum', 'verified']);
Route::get('/offices/{office}', [OfficeController::class, 'show']);

//officeImage
Route::post('/offices/{office}/images', [OfficeImageController::class, 'store'])
    ->middleware(['auth:sanctum', 'verified']);
Route::delete('/offices/{office}/images/{image}', [OfficeImageController::class, 'delete'])
    ->middleware(['auth:sanctum', 'verified'])
    //I think it is ok to do this
    ->scopeBindings();

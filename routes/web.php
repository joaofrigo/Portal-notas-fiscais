<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NFUploadController;

#Route::get('/', function () {
#    return view('welcome');
#});

Route::get('/', function () {
    return view('home');
});

Route::post('/upload-xml', [NFUploadController::class, 'store'])
    ->name('upload.xml');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NFUploadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotaFiscalController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\TransportadoraController;

#Route::get('/', function () {
#    return view('welcome');
#});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/upload-xml', [NFUploadController::class, 'store'])->name('upload.xml');
Route::get('/nota/{id}', [NotaFiscalController::class, 'show'])->name('nota.show');
Route::get('/impostos', [NotaFiscalController::class, 'impostos'])->name('nota.impostos');
Route::get('/fornecedores', [FornecedorController::class, 'index'])->name('fornecedores.index');
Route::get('/transportadoras', [TransportadoraController::class, 'index'])->name('transportadoras.index');








    
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/payments/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post ('/payments/create', [PaymentController::class, 'create'])->name('payment.create');
Route::get ('/payments', [PaymentController::class, 'index'])->name('payment.index');

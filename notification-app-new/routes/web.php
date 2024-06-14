<?php

use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    //return view('welcome');
});

Route::get('/getConfirmationCode', [VerificationController::class, 'getConfirmationCode']);
Route::get('/confirmCode', [VerificationController::class, 'confirmCode']);
Route::get('/isCodeVerified', [VerificationController::class, 'isCodeVerified']);
Route::get('/test', [VerificationController::class, 'test2']);

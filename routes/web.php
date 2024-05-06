<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SheShiController;

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

Route::get('/', [SheShiController::class, 'index']);
Route::get('/shenshe', [SheShiController::class, 'shengShe']);

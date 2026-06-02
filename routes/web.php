<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('requests.index');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/requests', [DocumentRequestController::class, 'index'])->name('requests.index');
Route::get('/requests/create', [DocumentRequestController::class, 'create'])->name('requests.create');
Route::post('/requests', [DocumentRequestController::class, 'store'])->name('requests.store');
Route::patch('/requests/{documentRequest}/hr-approve', [DocumentRequestController::class, 'approveHr'])->name('requests.hr-approve');
Route::patch('/requests/{documentRequest}/director-approve', [DocumentRequestController::class, 'approveDirector'])->name('requests.director-approve');
Route::patch('/requests/{documentRequest}/reject', [DocumentRequestController::class, 'reject'])->name('requests.reject');

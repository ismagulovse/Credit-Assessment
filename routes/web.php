<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/calculate', [AttendanceController::class, 'calculate'])->name('attendance.calculate');
Route::get('/export', [AttendanceController::class, 'export'])->name('attendance.export');

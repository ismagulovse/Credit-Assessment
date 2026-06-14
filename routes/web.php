<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

// Дашборд (главная)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// «Кто на автомат» — разбор xlsx
Route::get('/avtomat', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/avtomat/calculate', [AttendanceController::class, 'calculate'])->name('attendance.calculate');
Route::get('/avtomat/export', [AttendanceController::class, 'export'])->name('attendance.export');

// Группы
Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

// Шаблон списка студентов
Route::get('/students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');

// Студенты (вложены в группу)
Route::post('/groups/{group}/students', [StudentController::class, 'store'])->name('students.store');
Route::post('/groups/{group}/students/bulk', [StudentController::class, 'storeBulk'])->name('students.storeBulk');
Route::post('/groups/{group}/students/import', [StudentController::class, 'import'])->name('students.import');
Route::delete('/groups/{group}/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

// Журнал — предметы
Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
Route::get('/subjects/{subject}', [SubjectController::class, 'show'])->name('subjects.show');
Route::get('/subjects/{subject}/export', [SubjectController::class, 'export'])->name('subjects.export');
Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
Route::post('/subjects/{subject}/groups', [SubjectController::class, 'attachGroup'])->name('subjects.attachGroup');
Route::delete('/subjects/{subject}/groups/{group}', [SubjectController::class, 'detachGroup'])->name('subjects.detachGroup');
Route::post('/subjects/{subject}/mark', [SubjectController::class, 'mark'])->name('subjects.mark');

// Журнал — занятия
Route::post('/subjects/{subject}/lessons', [LessonController::class, 'store'])->name('lessons.store');
Route::delete('/subjects/{subject}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

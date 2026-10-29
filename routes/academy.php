<?php

use App\Http\Controllers\AcademyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('academy')->name('academy.')->group(function () {
    Route::get('/', [AcademyController::class, 'index'])->name('index');
    Route::post('/groups', [AcademyController::class, 'storeGroup'])->name('groups.store');
    Route::get('/groups/{group}', [AcademyController::class, 'showGroup'])->whereNumber('group')->name('groups.show');
    Route::patch('/groups/{group}', [AcademyController::class, 'updateGroup'])->whereNumber('group')->name('groups.update');
    Route::delete('/groups/{group}', [AcademyController::class, 'archiveGroup'])->whereNumber('group')->name('groups.archive');
    Route::post('/groups/{group}/students', [AcademyController::class, 'enroll'])->whereNumber('group')->name('enroll');
    Route::get('/students/{student}', [AcademyController::class, 'student'])->whereNumber('student')->name('student');
    Route::patch('/enrollments/{enrollment}/units', [AcademyController::class, 'units'])->whereNumber('enrollment')->name('units');
    Route::post('/enrollments/{enrollment}/complete', [AcademyController::class, 'complete'])->whereNumber('enrollment')->name('complete');
});

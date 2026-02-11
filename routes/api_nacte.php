<?php

use App\Http\Controllers\Nacte\StudentsNacteController;
use Illuminate\Support\Facades\Route;

Route::prefix('nacte')
    ->middleware(['auth:sanctum', 'throttle:30,1'])
    ->group(function () {
        Route::get('/bachelor-programme', [StudentsNacteController::class, 'bachelorProgramme']);
        Route::post('/enrollment', [StudentsNacteController::class, 'bachelorEnrollment']);
        Route::get('/unprocessed', [StudentsNacteController::class, 'studentUnprocessed']);
        Route::get('/processed', [StudentsNacteController::class, 'studentProcessed']);
        Route::get('/getInfrastructure', [StudentsNacteController::class, 'getInfrastructure']);

        

    });

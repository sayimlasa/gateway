<?php

use Illuminate\Support\Facades\Route;

Route::prefix('nacte')->group(function () {
    Route::get('/test', function () {
        return response()->json(['message' => 'NACTE API Container']);
    });
});


<?php

use Illuminate\Support\Facades\Route;

Route::prefix('tcu')->group(function () {
    Route::get('/test', function () {
        return response()->json(['message' => 'TCU API Container']);
    });
});


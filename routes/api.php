<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataAnalysisController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('data-analysis')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'AI Data Analysis API',
            'status' => 'success'
        ]);
    });
    Route::post('/input-data', [DataAnalysisController::class, 'inputData'])->name('api.input-data');
    Route::post('/query', [DataAnalysisController::class, 'queryData'])->name('api.query-data');
}); 
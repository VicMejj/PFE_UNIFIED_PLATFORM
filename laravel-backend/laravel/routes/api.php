<?php
use App\Http\Controllers\Api\RhController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\AssuranceController;

Route::prefix('rh')->group(function () {
    Route::get('/employees', [RhController::class, 'employees']);
});

Route::prefix('social')->group(function () {
    // social routes
});

Route::prefix('assurance')->group(function () {
    // assurance routes
});

?>

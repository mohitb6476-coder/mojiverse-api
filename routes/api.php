<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\MatchController;

Route::post('/generate', [GeneratorController::class, 'generate']);
Route::post('/match', [MatchController::class, 'match']);

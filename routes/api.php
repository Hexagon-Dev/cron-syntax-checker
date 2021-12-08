<?php

use App\Http\Controllers\CheckController;
use Illuminate\Support\Facades\Route;

Route::get('/check/{value}', [CheckController::class, 'check']);

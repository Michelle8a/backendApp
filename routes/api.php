<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\IdiomaController;

//IDIOMAS
Route::get('/idiomas', [IdiomaController::class, 'index']);
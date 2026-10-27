<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\IdiomaController;
use App\Http\Controllers\AlergiaController;
use App\Http\Controllers\EnfermedadController;

use App\Http\Controllers\UsuarioController;

//IDIOMAS
Route::get('/idiomas', [IdiomaController::class, 'index']);

//ALERGIAS
Route::get('/alergias', [AlergiaController::class, 'index']);

//ENFERMEDADES
Route::get('/enfermedades', [EnfermedadController::class, 'index']);

//USUARIOS
Route::apiResource('usuarios', UsuarioController::class);
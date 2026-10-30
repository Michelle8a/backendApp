<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;

use App\Http\Controllers\IdiomaController;
use App\Http\Controllers\AlergiaController;
use App\Http\Controllers\EnfermedadController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\Centro_SaludController;
use App\Http\Controllers\FarmaciaController;
use App\Http\Controllers\Categoria_ForoController;


//USUARIOS
Route::apiResource('/usuarios', UsuarioController::class);


//------------SOLO GET--------------------

//IDIOMAS
Route::get('/idiomas', [IdiomaController::class, 'index']);

//ALERGIAS
Route::get('/alergias', [AlergiaController::class, 'index']);

//ENFERMEDADES
Route::get('/enfermedades', [EnfermedadController::class, 'index']);

//ESPECIALIDADES
Route::get('/especialidades', [EspecialidadController::class, 'index']);

//CENTROS DE SALUD
Route::get('/centros_salud', [Centro_SaludController::class, 'index']);

//FARMACIAS
Route::get('/farmacias', [FarmaciaController::class, 'index']);

//CATEGORIAS FORO
Route::get('/categorias_foro', [Categoria_ForoController::class, 'index']);



//----------------CRUD-----------------------


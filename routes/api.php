<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\IdiomaController;
use App\Http\Controllers\AlergiaController;
use App\Http\Controllers\EnfermedadController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\Centro_SaludController;
use App\Http\Controllers\FarmaciaController;
use App\Http\Controllers\Categoria_ForoController;

use App\Http\Controllers\PacienteController;


//------------AUTENTICACION (JWT)--------------------
//publicas
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

//requiere token
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/perfil', [AuthController::class, 'perfil']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout',[AuthController::class, 'logout']);
});

//------------SOLO ADMIN--------------------
// USUARIOS (antes era publico, cualquiera podia crear admins o borrar usuarios)
Route::middleware(['auth:api', 'rol:admin'])->group(function (){
    Route::apiResource('/usuarios', UsuarioController::class);
});

//------------SOLO GET--------------------

Route::get('/idiomas', [IdiomaController::class, 'index']);

Route::get('/alergias', [AlergiaController::class, 'index']);

Route::get('/enfermedades', [EnfermedadController::class, 'index']);

Route::get('/especialidades', [EspecialidadController::class, 'index']);

Route::get('/centros_salud', [Centro_SaludController::class, 'index']);

Route::get('/farmacias', [FarmaciaController::class, 'index']);

Route::get('/categorias_foro', [Categoria_ForoController::class, 'index']);





//----------------CRUD-----------------------

//Route::apiResource('/usuarios', UsuarioController::class); //cambiar despues

Route::apiResource('/pacientes', PacienteController::class);

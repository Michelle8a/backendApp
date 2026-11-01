<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\IdiomaController;
use App\Http\Controllers\AlergiaController;
use App\Http\Controllers\EnfermedadController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\CentroSaludController;
use App\Http\Controllers\FarmaciaController;
use App\Http\Controllers\CategoriaForoController;

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



//------------PACIENTES--------------------
//el paciente consulta su propio perfil
Route::middleware(['auth:api', 'rol:paciente'])->get('/mi-paciente', [PacienteController::class, 'miPerfil']);

//listar: solo admin y medico
Route::middleware(['auth:api', 'rol:admin,medico'])->get('/pacientes', [PacienteController::class, 'index']);

//crear y actualizar: paciente(el suyo) y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/pacientes', [PacienteController::class, 'store']);
    Route::put('/pacientes/{paciente}', [PacienteController::class, 'update']);
});

//ver uno: los tres roles (el controller se encarga de revisar que sea suyo si el rol es paciente)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->get('/pacientes/{paciente}', [PacienteController::class, 'show']);

//eliminar: solo el admin
Route::middleware(['auth:api', 'rol:admin'])->delete('/paciente/{paciente}', [PacienteController::class, 'destroy']);



//------------SOLO GET--------------------

Route::get('/idiomas', [IdiomaController::class, 'index']);
Route::get('/alergias', [AlergiaController::class, 'index']);
Route::get('/enfermedades', [EnfermedadController::class, 'index']);
Route::get('/especialidades', [EspecialidadController::class, 'index']);
Route::get('/centros_salud', [CentroSaludController::class, 'index']);
Route::get('/farmacias', [FarmaciaController::class, 'index']);
Route::get('/categorias_foro', [CategoriaForoController::class, 'index']);



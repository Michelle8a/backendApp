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
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\TokenDispositivoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\BitacoraAuditoriaController;
use App\Http\Controllers\PublicacionForoController;
use App\Http\Controllers\ContactoEmergenciaController;


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



//------------MEDICOS--------------------

//EL MEDICO PUEDE CONSULTAR SU PROPIO PERFIL
Route::middleware(['auth:api', 'rol:medico'])->get('mi-medico', [MedicoController::class, 'miPerfil']);

//buscar y ver: cualquier usuario son sesion (el controler oculta los no verificados)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/medicos', [MedicoController::class, 'index']);
    Route::get('/medicos/{medico}', [MedicoController::class, 'show']);
});

//crear y actualizar: medico (el suyo) y admin (cualquiera)
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/medicos', [MedicoController::class, 'store']);
    Route::put('/medicos/{medico}', [MedicoController::class, 'update']);
});

//verificar y eliminar: solo el admin
Route::middleware(['auth:api', 'rol:admin'])->group(function () {
    Route::patch('/medicos/{medico}/verificar', [MedicoController::class, 'verificar']);
    Route::delete('/medicos/{medico}', [MedicoController::class, 'destroy']);
});

//------------SOLO GET--------------------

Route::get('/idiomas', [IdiomaController::class, 'index']);
Route::get('/alergias', [AlergiaController::class, 'index']);
Route::get('/enfermedades', [EnfermedadController::class, 'index']);
Route::get('/especialidades', [EspecialidadController::class, 'index']);
Route::get('/centros_salud', [CentroSaludController::class, 'index']);
Route::get('/farmacias', [FarmaciaController::class, 'index']);

//------------CATEGORIAS FORO--------------------
//ver: publico
Route::get('/categorias_foro', [CategoriaForoController::class, 'index']);
Route::get('/categorias_foro/{categoria}', [CategoriaForoController::class, 'show']);

//crear, actualizar y eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])->group(function () {
    Route::post('/categorias_foro', [CategoriaForoController::class, 'store']);
    Route::put('/categorias_foro/{categoria}', [CategoriaForoController::class, 'update']);
    Route::delete('/categorias_foro/{categoria}', [CategoriaForoController::class, 'destroy']);
});

//------------TOKENS DISPOSITIVO (FCM)--------------------
Route::middleware(['auth:api'])->group(function () {
    Route::post('/tokens_dispositivo', [TokenDispositivoController::class, 'store']);
    Route::delete('/tokens_dispositivo', [TokenDispositivoController::class, 'destroy']);
});

//------------NOTIFICACIONESSSSS--------------------
Route::middleware('auth:api')->group(function () {
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::get('/notificaciones/contador', [NotificacionController::class, 'contador']);
    Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
    Route::patch('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida']);
    Route::delete('/notificaciones/{notificacion}', [NotificacionController::class, 'destroy']);
});

//crear: solo admin y medico lo mismo anterior, pero no se puede crear notificaciones para otros usuarios, solo para uno mismo
Route::middleware(['auth:api', 'rol:admin,medico'])
    ->post('/notificaciones', [NotificacionController::class, 'store']);


//------------BITACORA DE AUDITORIA (solo lectura y solo admin)--------------------
Route::middleware(['auth:api', 'rol:admin'])->group(function () {
    Route::get('/bitacora', [BitacoraAuditoriaController::class, 'index']);
    Route::get('/bitacora/{bitacora}', [BitacoraAuditoriaController::class, 'show']);
});

//------------FORO PUBLICACIONES--------------------
//cualquier usuario con sesión puede ver, crear, dar me gusta;
//el controller revisa que solo el autor o el admin editen/eliminen
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->prefix('foro')->group(function () {
    Route::get('/publicaciones', [PublicacionForoController::class, 'index']);
    Route::post('/publicaciones', [PublicacionForoController::class, 'store']);
    Route::get('/publicaciones/{publicacion}', [PublicacionForoController::class, 'show']);
    Route::put('/publicaciones/{publicacion}', [PublicacionForoController::class, 'update']);
    Route::delete('/publicaciones/{publicacion}', [PublicacionForoController::class, 'destroy']);
    Route::post('/publicaciones/{publicacion}/me-gusta', [PublicacionForoController::class, 'toggleMeGusta']);
});

//------------CONTACTOS DE EMERGENCIA--------------------
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/pacientes/{paciente}/contactos_emergencia', [ContactoEmergenciaController::class, 'index']);
    Route::get('/contactos_emergencia/{contacto}', [ContactoEmergenciaController::class, 'show']);
});

//crear, actualizar y eliminar: paciente (los suyos) y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/pacientes/{paciente}/contactos_emergencia', [ContactoEmergenciaController::class, 'store']);
    Route::put('/contactos_emergencia/{contacto}', [ContactoEmergenciaController::class, 'update']);
    Route::delete('/contactos_emergencia/{contacto}', [ContactoEmergenciaController::class, 'destroy']);
});
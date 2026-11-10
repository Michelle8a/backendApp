<?php

use App\Http\Controllers\MedicoIdiomaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitasController;
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
use App\Http\Controllers\ResultadoLaboratorioController;
use App\Http\Controllers\HorarioMedicoController;
use App\Http\Controllers\ArchivosAdjuntosController;
use App\Http\Controllers\MedicoEspecialidadController;
use App\Http\Controllers\ComentariosForoController;
use App\Http\Controllers\MeGustaForoController;
use App\Http\Controllers\SesionAdmisionController;
use App\Http\Controllers\ConversacionController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\RespuestaAdmisionController;
use App\Http\Controllers\BanderaRojaController;
use App\Http\Controllers\ParticipantesConversacionController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\DetalleRecetaController;
use App\Http\Controllers\RecetaCompartidaController;
use App\Http\Controllers\HistorialMedicoController;
use App\Http\Controllers\PacienteAlergiaController;

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
//el paciente consulta y actualiza su propio perfil
Route::middleware(['auth:api', 'rol:paciente'])->group(function () {
    Route::get('/mi-paciente', [PacienteController::class, 'miPerfil']);
    Route::put('/mi-paciente', [PacienteController::class, 'actualizarMiPerfil']);
});

//listar: solo admin y medico
Route::middleware(['auth:api', 'rol:admin,medico'])->get('/pacientes', [PacienteController::class, 'index']);

//crear y actualizar: paciente(el suyo) y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/pacientes', [PacienteController::class, 'store']);
    Route::put('/pacientes/{paciente}', [PacienteController::class, 'update']);
});

//ver uno: los tres roles (el controller revisa que sea suyo si el rol es paciente)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->get('/pacientes/{paciente}', [PacienteController::class, 'show']);

//eliminar: solo el admin
Route::middleware(['auth:api', 'rol:admin'])->delete('/pacientes/{paciente}', [PacienteController::class, 'destroy']);


//------------ALERGIAS DEL PACIENTE--------------------
//el paciente gestiona sus propias alergias
Route::middleware(['auth:api', 'rol:paciente'])->group(function () {
    Route::get('/mi-paciente/alergias', [PacienteAlergiaController::class, 'indexMias']);
    Route::post('/mi-paciente/alergias', [PacienteAlergiaController::class, 'store']);
    Route::put('/mi-paciente/alergias/{alergiaId}', [PacienteAlergiaController::class, 'update']);
    Route::delete('/mi-paciente/alergias/{alergiaId}', [PacienteAlergiaController::class, 'destroy']);
});

//el medico y el admin solo consultan
Route::middleware(['auth:api', 'rol:medico,admin'])
    ->get('/pacientes/{paciente}/alergias', [PacienteAlergiaController::class, 'indexDe']);


//------------MEDICOS--------------------
//el medico consulta su propio perfil
Route::middleware(['auth:api', 'rol:medico'])->get('/mi-medico', [MedicoController::class, 'miPerfil']);

//buscar y ver medicos
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/medicos', [MedicoController::class, 'index']);
    Route::get('/medicos/{medico}', [MedicoController::class, 'show']);
});

//registrar un medico: SOLO admin
Route::middleware(['auth:api', 'rol:admin'])->post('/medicos', [MedicoController::class, 'store']);

//Registrar un medico junto con su usuario(cuenta + perfil): SOLO ADMIN
Route::middleware(['auth:api', 'rol:admin'])->post('/admin/medicos', [MedicoController::class, 'registrarConUsuario']);

//actualizar: el propio medico o el admin
Route::middleware(['auth:api', 'rol:medico,admin'])->put('/medicos/{medico}', [MedicoController::class, 'update']);

//verificar y eliminar: solo admin
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


//------------BITACORA DE AUDITORIA --------------------
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

//crear, actualizar y eliminar: paciente y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/pacientes/{paciente}/contactos_emergencia', [ContactoEmergenciaController::class, 'store']);
    Route::put('/contactos_emergencia/{contacto}', [ContactoEmergenciaController::class, 'update']);
    Route::delete('/contactos_emergencia/{contacto}', [ContactoEmergenciaController::class, 'destroy']);
});

//------------RESULTADOS DE LABORATORIO--------------------
//ver, listar y descargar: los tres roles (el controller revisa que el paciente solo vea lo suyo)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/pacientes/{paciente}/resultados_laboratorio', [ResultadoLaboratorioController::class, 'index']);
    Route::get('/resultados_laboratorio/{resultado}', [ResultadoLaboratorioController::class, 'show']);
    Route::get('/resultados_laboratorio/{resultado}/archivo', [ResultadoLaboratorioController::class, 'archivo']);
});

//crear y actualizar: paciente , medico y admin
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::post('/pacientes/{paciente}/resultados_laboratorio', [ResultadoLaboratorioController::class, 'store']);
    Route::put('/resultados_laboratorio/{resultado}', [ResultadoLaboratorioController::class, 'update']);
});

//eliminar: paciente y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])
    ->delete('/resultados_laboratorio/{resultado}', [ResultadoLaboratorioController::class, 'destroy']);



//------------ARCHIVOS ADJUNTOS--------------------
//ver y descargar: los tres roles (el controller revisa que sea suyo si es paciente)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/pacientes/{paciente}/archivos_adjuntos', [ArchivosAdjuntosController::class, 'index']);
    Route::get('/archivos_adjuntos/{archivo}', [ArchivosAdjuntosController::class, 'show']);
    Route::get('/archivos_adjuntos/{archivo}/descargar', [ArchivosAdjuntosController::class, 'descargar']);
});

//subir y eliminar: pacientey admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/pacientes/{paciente}/archivos_adjuntos', [ArchivosAdjuntosController::class, 'store']);
    Route::delete('/archivos_adjuntos/{archivo}', [ArchivosAdjuntosController::class, 'destroy']);
});

//------------MEDICO - ESPECIALIDADES--------------------
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/medicos/{medico}/especialidades', [MedicoEspecialidadController::class, 'index']);
    Route::get('/especialidades/{especialidad}/medicos', [MedicoEspecialidadController::class, 'medicosPorEspecialidad']);
});

//asignar, reemplazar y quitar: medico (las suyas) y admin (cualquiera)
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/medicos/{medico}/especialidades', [MedicoEspecialidadController::class, 'store']);
    Route::put('/medicos/{medico}/especialidades', [MedicoEspecialidadController::class, 'sync']);
    Route::delete('/medicos/{medico}/especialidades/{especialidad}', [MedicoEspecialidadController::class, 'destroy']);
});

//------------MEDICO - IDIOMAS--------------------
//ver: los tres roles (el controller oculta medicos no verificados)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/medicos/{medico}/idiomas', [MedicoIdiomaController::class, 'index']);
    Route::get('/idiomas/{idioma}/medicos', [MedicoIdiomaController::class, 'medicosPorIdioma']);
});

//asignar, reemplazar y quitar: medico (los suyos) y admin (cualquiera)
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/medicos/{medico}/idiomas', [MedicoIdiomaController::class, 'store']);
    Route::put('/medicos/{medico}/idiomas', [MedicoIdiomaController::class, 'sync']);
    Route::delete('/medicos/{medico}/idiomas/{idioma}', [MedicoIdiomaController::class, 'destroy']);
});

//ver: los tres roles (el controller oculta medicos no verificados)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/medicos/{medico}/horarios', [HorarioMedicoController::class, 'index']);
    Route::get('/horarios_medico/{horario}', [HorarioMedicoController::class, 'show']);
});

//crear, actualizar y eliminar: medico y admin (cualquiera)
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/medicos/{medico}/horarios', [HorarioMedicoController::class, 'store']);
    Route::put('/horarios_medico/{horario}', [HorarioMedicoController::class, 'update']);
    Route::delete('/horarios_medico/{horario}', [HorarioMedicoController::class, 'destroy']);
});

//------------CITAS--------------------
//listar y ver: los tres roles (el controller filtra: paciente/medico solo ven las suyas)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/citas', [CitasController::class, 'index']);
    Route::get('/citas/{cita}', [CitasController::class, 'show']);

    //el controller decide que campos puede tocar cada rol (paciente: reprogramar, medico: notas, admin: todo)
    Route::put('/citas/{cita}', [CitasController::class, 'update']);

    //cancelar: cada rol sobre sus propias citas
    Route::patch('/citas/{cita}/cancelar', [CitasController::class, 'cancelar']);
});

//crear: paciente (para si mismo) y admin (para cualquier paciente)
Route::middleware(['auth:api', 'rol:paciente,admin'])
    ->post('/citas', [CitasController::class, 'store']);

//cambiar estado (confirmar, iniciar, finalizar): medico de la cita y admin
Route::middleware(['auth:api', 'rol:medico,admin'])
    ->patch('/citas/{cita}/estado', [CitasController::class, 'cambiarEstado']);

//eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/citas/{cita}', [CitasController::class, 'destroy']);

//------------FORO COMENTARIOS--------------------
//cualquier usuario con sesion puede ver y comentar;
//el controller revisa: editar = solo el autor, eliminar = admin
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->prefix('foro')->group(function () {
    Route::get('/publicaciones/{publicacion}/comentarios', [ComentariosForoController::class, 'index']);
    Route::post('/publicaciones/{publicacion}/comentarios', [ComentariosForoController::class, 'store']);
    Route::get('/comentarios/{comentario}', [ComentariosForoController::class, 'show']);
    Route::put('/comentarios/{comentario}', [ComentariosForoController::class, 'update']);
    Route::delete('/comentarios/{comentario}', [ComentariosForoController::class, 'destroy']);
});


// ruta POST /foro/publicaciones/{publicacion}/me-gusta del toggle, convive con esta

//------------FORO ME GUSTA--------------------
//los tres roles; cada usuario solo da/quita SU propio me gusta (sale del token)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->prefix('foro')->group(function () {
    //Route::post('/foro/publicaciones/{publicacion}/me-gusta', [MeGustaForoController::class, 'estado']);
    Route::get('/publicaciones/{publicacion}/me-gusta', [MeGustaForoController::class, 'estado']);
    Route::put('/publicaciones/{publicacion}/me-gusta', [MeGustaForoController::class, 'dar']);
    Route::delete('/publicaciones/{publicacion}/me-gusta', [MeGustaForoController::class, 'quitar']);
    Route::get('/mis-me-gusta', [MeGustaForoController::class, 'mios']);
});

//ver quien dio me gusta: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->get('/foro/publicaciones/{publicacion}/me-gusta/usuarios', [MeGustaForoController::class, 'usuarios']);

use App\Http\Controllers\ValorResultadoController;

//------------VALORES DE RESULTADO--------------------
//ver: los tres roles
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/resultados_laboratorio/{resultado}/valores', [ValorResultadoController::class, 'index']);
    Route::get('/valores_resultado/{valor}', [ValorResultadoController::class, 'show']);

    //crear y actualizar: paciente, medico y admin
    Route::post('/resultados_laboratorio/{resultado}/valores', [ValorResultadoController::class, 'store']);
    Route::post('/resultados_laboratorio/{resultado}/valores/lote', [ValorResultadoController::class, 'storeLote']);
    Route::put('/valores_resultado/{valor}', [ValorResultadoController::class, 'update']);
});

//eliminar: paciente y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])
    ->delete('/valores_resultado/{valor}', [ValorResultadoController::class, 'destroy']);

//------------SESIONES DE ADMISION--------------------
//ver y listar: los tres roles (el controller revisa: paciente lo suyo, medico solo sus citas)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/pacientes/{paciente}/sesiones_admision', [SesionAdmisionController::class, 'index']);
    Route::get('/citas/{cita}/sesion_admision', [SesionAdmisionController::class, 'porCita']);
    Route::get('/sesiones_admision/{sesion}', [SesionAdmisionController::class, 'show']);
});

//crear y actualizar: paciente  y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/citas/{cita}/sesion_admision', [SesionAdmisionController::class, 'store']);
    Route::put('/sesiones_admision/{sesion}', [SesionAdmisionController::class, 'update']);
});

//eliminar: solo admin (es un registro clinico)
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/sesiones_admision/{sesion}', [SesionAdmisionController::class, 'destroy']);
    

//------------CONVERSACIONES--------------------
//ver, listar y crear: los tres roles (el controller revisa que sea participante de la cita/conversacion)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/conversaciones', [ConversacionController::class, 'index']);
    Route::post('/conversaciones', [ConversacionController::class, 'store']);
    Route::get('/conversaciones/{conversacion}', [ConversacionController::class, 'show']);
    Route::get('/citas/{cita}/conversacion', [ConversacionController::class, 'porCita']);
});

//eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/conversaciones/{conversacion}', [ConversacionController::class, 'destroy']);

//------------RECETAS--------------------
//listar y ver: los tres roles 
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/recetas', [RecetaController::class, 'index']);
    Route::get('/recetas/{receta}', [RecetaController::class, 'show']);
});

//crear y actualizar: medico y admin
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/recetas', [RecetaController::class, 'store']);
    Route::put('/recetas/{receta}', [RecetaController::class, 'update']);
});

//eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/recetas/{receta}', [RecetaController::class, 'destroy']);

//------------RESPUESTAS DE ADMISION--------------------
//ver y listar: los tres roles 
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/sesiones_admision/{sesion}/respuestas', [RespuestaAdmisionController::class, 'index']);
    Route::get('/respuestas_admision/{respuesta}', [RespuestaAdmisionController::class, 'show']);
});

//crear y actualizar: paciente (sesion no completada) y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/sesiones_admision/{sesion}/respuestas', [RespuestaAdmisionController::class, 'store']);
    Route::put('/respuestas_admision/{respuesta}', [RespuestaAdmisionController::class, 'update']);
});

//eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/respuestas_admision/{respuesta}', [RespuestaAdmisionController::class, 'destroy']);



//------------BANDERAS ROJAS--------------------
//listar y ver: medico y admin
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::get('/banderas_rojas', [BanderaRojaController::class, 'index']);
    Route::get('/banderas_rojas/{bandera}', [BanderaRojaController::class, 'show']);
    Route::post('/banderas_rojas', [BanderaRojaController::class, 'store']);
});

//eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/banderas_rojas/{bandera}', [BanderaRojaController::class, 'destroy']);

//------------PARTICIPANTES DE CONVERSACION--------------------
//listar: los tres roles
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])
    ->get('/conversaciones/{conversacion}/participantes', [ParticipantesConversacionController::class, 'index']);

//agregar y quitar: solo admin
Route::middleware(['auth:api', 'rol:admin'])->group(function () {
    Route::post('/conversaciones/{conversacion}/participantes', [ParticipantesConversacionController::class, 'store']);
    Route::delete('/conversaciones/{conversacion}/participantes/{usuario}', [ParticipantesConversacionController::class, 'destroy']);
});


//------------MENSAJES (CHAT)--------------------
//listar, enviar, ver y marcar leidos: paciente y medico (el controller revisa que participen)
Route::middleware(['auth:api', 'rol:paciente,medico'])->group(function () {
    Route::get('/conversaciones/{conversacion}/mensajes', [MensajeController::class, 'index']);
    Route::post('/conversaciones/{conversacion}/mensajes', [MensajeController::class, 'store']);
    Route::patch('/conversaciones/{conversacion}/mensajes/leer', [MensajeController::class, 'marcarLeidos']);
    Route::get('/mensajes/{mensaje}', [MensajeController::class, 'show']);
    Route::patch('/mensajes/{mensaje}/leer', [MensajeController::class, 'marcarLeido']);
});

//eliminar (moderacion): solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/mensajes/{mensaje}', [MensajeController::class, 'destroy']);

//------------DETALLE DE RECETA (MEDICAMENTOS)--------------------
//ver y listar: los tres roles 
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/recetas/{receta}/detalles', [DetalleRecetaController::class, 'index']);
    Route::get('/detalles_receta/{detalle}', [DetalleRecetaController::class, 'show']);
});

//crear, actualizar y eliminar: medico  y admin
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/recetas/{receta}/detalles', [DetalleRecetaController::class, 'store']);
    Route::put('/detalles_receta/{detalle}', [DetalleRecetaController::class, 'update']);
    Route::delete('/detalles_receta/{detalle}', [DetalleRecetaController::class, 'destroy']);
});

//------------RECETAS COMPARTIDAS CON FARMACIAS--------------------
//ver con que farmacias esta compartida: los tres roles (el controller revisa que sea su receta)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])
    ->get('/recetas/{receta}/farmacias', [RecetaCompartidaController::class, 'index']);

//compartir y dejar de compartir: paciente (dueño de la receta) y admin
Route::middleware(['auth:api', 'rol:paciente,admin'])->group(function () {
    Route::post('/recetas/{receta}/farmacias', [RecetaCompartidaController::class, 'store']);
    Route::delete('/recetas/{receta}/farmacias/{farmacia}', [RecetaCompartidaController::class, 'destroy']);
});

//recetas recibidas por una farmacia: solo admin (las farmacias no tienen login)
Route::middleware(['auth:api', 'rol:admin'])
    ->get('/farmacias/{farmacia}/recetas', [RecetaCompartidaController::class, 'recetasDeFarmacia']);

//------------HISTORIAL MEDICO--------------------
//ver y listar: los tres roles (el controller revisa: paciente el suyo, medico solo pacientes que atiende)
Route::middleware(['auth:api', 'rol:paciente,medico,admin'])->group(function () {
    Route::get('/pacientes/{paciente}/historial_medico', [HistorialMedicoController::class, 'index']);
    Route::get('/historial_medico/{historial}', [HistorialMedicoController::class, 'show']);
});

//crear y actualizar: medico y admin
Route::middleware(['auth:api', 'rol:medico,admin'])->group(function () {
    Route::post('/pacientes/{paciente}/historial_medico', [HistorialMedicoController::class, 'store']);
    Route::put('/historial_medico/{historial}', [HistorialMedicoController::class, 'update']);
});

//eliminar: solo admin
Route::middleware(['auth:api', 'rol:admin'])
    ->delete('/historial_medico/{historial}', [HistorialMedicoController::class, 'destroy']);
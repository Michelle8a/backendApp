<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\MedicoAdminController;
use App\Http\Controllers\Admin\PacienteAdminController;
use App\Http\Controllers\Admin\UsuarioAdminController;
use App\Http\Controllers\Admin\IdiomaController;
use App\Http\Controllers\Admin\AlergiaController;
use App\Http\Controllers\Admin\EnfermedadController;
use App\Http\Controllers\Admin\EspecialidadController;
use App\Http\Controllers\Admin\CentroSaludController;
use App\Http\Controllers\Admin\FarmaciaController;
use App\Http\Controllers\Admin\CategoriaForoWebController;
use App\Http\Controllers\Admin\PublicacionForoWebController;
use App\Http\Controllers\Admin\ComentarioForoWebController;
use App\Http\Controllers\Admin\BitacoraAdminController;
use App\Http\Controllers\Admin\CitaAdminController;

Route::get('/', function () {
    return redirect('/home');
});

Auth::routes(['register' => false]);

Route::get('/home', [HomeController::class, 'index'])->name('home');

//------------MEDICOS--------------------
Route::middleware(['auth:web', 'rol:admin'])->prefix('admin/medicos')->group(function () {
    Route::get('/reporte/pdf', [MedicoAdminController::class, 'pdf'])->name('medico.pdf');
    Route::get('/', [MedicoAdminController::class, 'index'])->name('medico.index');
    Route::get('/crear', [MedicoAdminController::class, 'create'])->name('medico.create');
    Route::post('/', [MedicoAdminController::class, 'store'])->name('medico.store');
    Route::get('/{medico}', [MedicoAdminController::class, 'show'])->name('medico.show');
    Route::get('/{medico}/editar', [MedicoAdminController::class, 'edit'])->name('medico.edit');
    Route::put('/{medico}', [MedicoAdminController::class, 'update'])->name('medico.update');
    Route::patch('/{medico}/verificar', [MedicoAdminController::class, 'verificar'])->name('medico.verificar');
    Route::delete('/{medico}', [MedicoAdminController::class, 'destroy'])->name('medico.destroy');
});

//------------RUTAS CON NOMBRE admin.* --------------------
Route::middleware(['auth:web', 'rol:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        //------------PACIENTES--------------------
        Route::get('/pacientes', [PacienteAdminController::class, 'index'])->name('pacientes.index');
        Route::get('/pacientes/reporte/pdf', [PacienteAdminController::class, 'pdf'])->name('pacientes.pdf');
        Route::get('/pacientes/{paciente}', [PacienteAdminController::class, 'show'])->name('pacientes.show');

        //------------USUARIOS--------------------
        Route::get('/usuarios', [UsuarioAdminController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/reporte/pdf', [UsuarioAdminController::class, 'pdf'])->name('usuarios.pdf');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioAdminController::class, 'cambiarEstado'])->name('usuarios.estado');

        //------------IDIOMAS--------------------
        //el reporte va ANTES del resource para que no lo confunda con {idioma}
        Route::get('idiomas/reporte', [IdiomaController::class, 'reporte'])->name('idiomas.reporte');
        Route::resource('idiomas', IdiomaController::class)
            ->parameters(['idiomas' => 'idioma'])
            ->except(['show']);

        //------------ALERGIAS--------------------
        Route::get('alergias/reporte', [AlergiaController::class, 'reporte'])->name('alergias.reporte');
        Route::resource('alergias', AlergiaController::class)
            ->parameters(['alergias' => 'alergia'])
            ->except(['show']);

        //------------ENFERMEDADES--------------------
        Route::get('enfermedades/reporte', [EnfermedadController::class, 'reporte'])->name('enfermedades.reporte');
        Route::resource('enfermedades', EnfermedadController::class)
            ->parameters(['enfermedades' => 'enfermedad'])
            ->except(['show']);

        //------------ESPECIALIDADES--------------------
        Route::get('especialidades/reporte', [EspecialidadController::class, 'reporte'])->name('especialidades.reporte');
        Route::resource('especialidades', EspecialidadController::class)
            ->parameters(['especialidades' => 'especialidad'])
            ->except(['show']);

        //------------CENTROS DE SALUD--------------------
        Route::get('centros_salud/reporte', [CentroSaludController::class, 'reporte'])->name('centros_salud.reporte');
        Route::resource('centros_salud', CentroSaludController::class)
            ->parameters(['centros_salud' => 'centro'])
            ->except(['show']);

        //------------FARMACIAS--------------------
        Route::get('farmacias/reporte', [FarmaciaController::class, 'reporte'])->name('farmacias.reporte');
        Route::resource('farmacias', FarmaciaController::class)
            ->parameters(['farmacias' => 'farmacia'])
            ->except(['show']);
    });

//------------FORO, BITACORA Y CITAS (nombres sin prefijo admin.) --------------------
Route::middleware(['auth:web', 'rol:admin'])->prefix('admin')->group(function () {

    //------------CATEGORIAS FORO--------------------
    Route::get('/categorias-foro', [CategoriaForoWebController::class, 'show'])->name('categoria_foro.show');
    Route::get('/categorias-foro/crear', [CategoriaForoWebController::class, 'create'])->name('categoria_foro.create');
    Route::post('/categorias-foro', [CategoriaForoWebController::class, 'store'])->name('categoria_foro.store');
    Route::get('/categorias-foro/{categoria}/editar', [CategoriaForoWebController::class, 'edit'])->name('categoria_foro.edit');
    Route::put('/categorias-foro/{categoria}', [CategoriaForoWebController::class, 'update'])->name('categoria_foro.update');
    Route::delete('/categorias-foro/{categoria}', [CategoriaForoWebController::class, 'destroy'])->name('categoria_foro.destroy');

    //------------PUBLICACIONES FORO (moderacion)--------------------
    Route::get('/publicaciones-foro', [PublicacionForoWebController::class, 'show'])->name('publicacion_foro.show');
    Route::get('/publicaciones-foro/reporte', [PublicacionForoWebController::class, 'pdf'])->name('publicacion_foro.pdf');
    Route::patch('/publicaciones-foro/{publicacion}/visibilidad', [PublicacionForoWebController::class, 'visibilidad'])->name('publicacion_foro.visibilidad');

    //------------COMENTARIOS FORO (moderacion)--------------------
    Route::get('/comentarios-foro', [ComentarioForoWebController::class, 'show'])->name('comentario_foro.show');
    Route::get('/comentarios-foro/reporte', [ComentarioForoWebController::class, 'pdf'])->name('comentario_foro.pdf');
    Route::delete('/comentarios-foro/{comentario}', [ComentarioForoWebController::class, 'destroy'])->name('comentario_foro.destroy');

    //------------BITACORA DE AUDITORIA (solo lectura + reporte)--------------------
    Route::get('/bitacora', [BitacoraAdminController::class, 'index'])->name('bitacora.index');
    Route::get('/bitacora/reporte/pdf', [BitacoraAdminController::class, 'pdf'])->name('bitacora.pdf');

    //------------CITAS (solo reporte)--------------------
    Route::get('/citas', [CitaAdminController::class, 'index'])->name('citas.index');
    Route::get('/citas/reporte/pdf', [CitaAdminController::class, 'pdf'])->name('citas.pdf');
});
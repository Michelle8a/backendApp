<?php

namespace Tests\Feature;

use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pruebas de los adjuntos (imagen/PDF) del chat paciente-medico.
 *
 * Usan SQLite en memoria (phpunit.xml) y Storage::fake('local'), por lo que
 * NO tocan la base de datos compartida de Aiven ni el disco real.
 */
class ChatAdjuntosTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, string $correo): Usuario
    {
        return Usuario::create([
            'correo' => $correo,
            'contrasena_hash' => Hash::make('secreto123'),
            'rol' => $rol,
            'telefono' => '70000000',
            'consentimiento_aceptado' => true,
            'consentimiento_fecha' => now(),
            'consentimiento_version' => '1.0',
        ]);
    }

    /** Conversacion de triaje: no depende de una cita ni de su ventana de horario. */
    private function conversacion(Usuario ...$participantes): Conversacion
    {
        $conversacion = Conversacion::create([
            'tipo' => 'triaje',
            'creado_en' => now(),
        ]);

        $conversacion->participantes()->attach(
            array_map(fn (Usuario $u) => $u->id, $participantes)
        );

        return $conversacion;
    }

    private function subir(Usuario $emisor, Conversacion $conversacion, UploadedFile $archivo, string $contenido = '')
    {
        $this->actingAs($emisor, 'api');

        return $this->post("/api/conversaciones/{$conversacion->id}/mensajes", array_filter([
            'contenido' => $contenido,
            'archivo' => $archivo,
        ]), ['Accept' => 'application/json']);
    }

    // ------------------------------------------------------------------
    // Subida
    // ------------------------------------------------------------------

    public function test_el_paciente_sube_una_imagen_y_recibe_enlace_firmado(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $medico = $this->usuario('medico', 'medico@test.com');
        $conversacion = $this->conversacion($paciente, $medico);

        $r = $this->subir($paciente, $conversacion, UploadedFile::fake()->image('mi foto.jpg', 600, 400), 'Mira la lesión');

        $r->assertCreated()
          ->assertJsonPath('mensaje_chat.tipo', 'imagen')
          ->assertJsonPath('mensaje_chat.contenido', 'Mira la lesión')
          ->assertJsonPath('mensaje_chat.adjunto_nombre', 'mi foto.jpg');

        $url = $r->json('mensaje_chat.adjunto_url');

        // Enlace relativo y firmado: sin host fijo, sin token y con caducidad
        $this->assertStringStartsWith('/api/mensajes/', $url);
        $this->assertStringContainsString('signature=', $url);

        // El archivo quedo en el disco privado, no en public
        $archivos = Storage::disk('local')->allFiles("chat/{$conversacion->id}");
        $this->assertCount(1, $archivos);
        $this->assertCount(0, Storage::disk('public')->allFiles("chat/{$conversacion->id}"));

        // El enlace firmado sirve el archivo sin token
        $this->get($url)->assertOk();

        // Sin firma no hay acceso (ni con token, si no es participante)
        $mensaje = Mensaje::first();
        $this->getJson("/api/mensajes/{$mensaje->id}/adjunto")->assertForbidden();
    }

    public function test_un_pdf_se_guarda_como_archivo(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $medico = $this->usuario('medico', 'medico@test.com');
        $conversacion = $this->conversacion($paciente, $medico);

        $r = $this->subir($paciente, $conversacion, UploadedFile::fake()->create('Resultados Lab.pdf', 300, 'application/pdf'));

        $r->assertCreated()
          ->assertJsonPath('mensaje_chat.tipo', 'archivo')
          ->assertJsonPath('mensaje_chat.adjunto_nombre', 'Resultados Lab.pdf');

        $this->assertCount(1, Storage::disk('local')->allFiles("chat/{$conversacion->id}"));
    }

    public function test_el_medico_tambien_puede_adjuntar(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $medico = $this->usuario('medico', 'medico@test.com');
        $conversacion = $this->conversacion($paciente, $medico);

        $r = $this->subir($medico, $conversacion, UploadedFile::fake()->image('receta.png'));

        $r->assertCreated()->assertJsonPath('mensaje_chat.emisor_id', $medico->id);
    }

    public function test_el_mensaje_de_texto_sigue_funcionando_sin_archivo(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $conversacion = $this->conversacion($paciente);

        $this->actingAs($paciente, 'api');

        $this->postJson("/api/conversaciones/{$conversacion->id}/mensajes", [
            'tipo' => 'texto',
            'contenido' => 'Hola doctor',
        ])->assertCreated()
          ->assertJsonPath('mensaje_chat.tipo', 'texto')
          ->assertJsonPath('mensaje_chat.adjunto_url', null);
    }

    public function test_un_mensaje_vacio_sin_archivo_se_rechaza(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $conversacion = $this->conversacion($paciente);

        $this->actingAs($paciente, 'api');

        $this->postJson("/api/conversaciones/{$conversacion->id}/mensajes", [
            'contenido' => '   ',
        ])->assertStatus(422)->assertJsonValidationErrors('contenido');
    }

    // ------------------------------------------------------------------
    // Validacion y permisos
    // ------------------------------------------------------------------

    public function test_rechaza_archivos_que_no_son_imagen_ni_pdf(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $conversacion = $this->conversacion($paciente);

        $this->subir($paciente, $conversacion, UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('archivo');

        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_rechaza_archivos_mayores_a_10_mb(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $conversacion = $this->conversacion($paciente);

        $this->subir($paciente, $conversacion, UploadedFile::fake()->create('grande.pdf', 10 * 1024 + 1, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('archivo');
    }

    public function test_un_usuario_ajeno_a_la_conversacion_no_puede_adjuntar(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $intruso = $this->usuario('medico', 'intruso@test.com');
        $conversacion = $this->conversacion($paciente);

        $this->subir($intruso, $conversacion, UploadedFile::fake()->image('foto.jpg'))
            ->assertStatus(403);

        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_el_enlace_firmado_caduca(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $conversacion = $this->conversacion($paciente);

        $url = $this->subir($paciente, $conversacion, UploadedFile::fake()->image('foto.jpg'))
            ->json('mensaje_chat.adjunto_url');

        $this->get($url)->assertOk();

        $this->travel(8)->days();

        $this->get($url)->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Moderacion
    // ------------------------------------------------------------------

    public function test_al_borrar_el_mensaje_tambien_se_borra_el_archivo(): void
    {
        Storage::fake('local');

        $paciente = $this->usuario('paciente', 'paciente@test.com');
        $admin = $this->usuario('admin', 'admin@test.com');
        $conversacion = $this->conversacion($paciente);

        $id = $this->subir($paciente, $conversacion, UploadedFile::fake()->image('foto.jpg'))
            ->json('mensaje_chat.id');

        $this->assertCount(1, Storage::disk('local')->allFiles("chat/{$conversacion->id}"));

        $this->actingAs($admin, 'api');
        $this->deleteJson("/api/mensajes/{$id}")->assertOk();

        $this->assertDatabaseMissing('mensajes', ['id' => $id]);
        $this->assertCount(0, Storage::disk('local')->allFiles("chat/{$conversacion->id}"));
    }
}

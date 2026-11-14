<?php

namespace Tests\Feature;

use App\Models\CategoriaForo;
use App\Models\CentroSalud;
use App\Models\Idioma;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PublicacionForo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pruebas del módulo "Foro de salud".
 *
 * Usan SQLite en memoria (phpunit.xml), por lo que NO tocan la base de datos
 * compartida de Aiven. Cada test corre todas las migraciones desde cero.
 */
class ForoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'paciente', ?string $correo = null): Usuario
    {
        return Usuario::create([
            'correo' => $correo ?? $rol.'-'.uniqid().'@test.com',
            'contrasena_hash' => Hash::make('secreto123'),
            'rol' => $rol,
            'telefono' => '70000000',
            'consentimiento_aceptado' => true,
            'consentimiento_fecha' => now(),
            'consentimiento_version' => '1.0',
        ]);
    }

    private function categoria(string $nombre = 'Salud general'): CategoriaForo
    {
        return CategoriaForo::create(['nombre' => $nombre]);
    }

    private function publicacion(Usuario $autor, CategoriaForo $cat, array $extra = []): PublicacionForo
    {
        return PublicacionForo::create(array_merge([
            'autor_id' => $autor->id,
            'categoria_id' => $cat->id,
            'titulo' => 'Mi experiencia',
            'contenido' => 'Comparto lo que me ayudo',
            'anonimo' => false,
            'creado_en' => now(),
        ], $extra));
    }

    // Perfil de paciente con nombres y apellidos (para probar el nombre del autor).
    private function perfilPaciente(Usuario $u, string $nombres = 'Ana', string $apellidos = 'Perez'): Paciente
    {
        $idioma = Idioma::firstOrCreate(['nombre' => 'Español'], ['codigo' => 'es']);

        return Paciente::create([
            'usuario_id' => $u->id,
            'idioma_id' => $idioma->id,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'dui_cifrado' => Crypt::encryptString('00000000-0'),
            'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'F',
            'tipo_sangre' => 'O+',
            'direccion' => 'San Salvador',
            'latitud' => 13.6989,
            'longitud' => -89.1914,
        ]);
    }

    // Perfil de médico con nombres y apellidos.
    private function perfilMedico(Usuario $u, string $nombres = 'Luis', string $apellidos = 'Gomez'): Medico
    {
        $centro = CentroSalud::create([
            'nombre' => 'Hospital de prueba',
            'tipo' => 'hospital',
            'direccion' => 'San Salvador',
            'latitud' => 13.6989,
            'longitud' => -89.1914,
            'telefono' => '2222-0000',
        ]);

        return Medico::create([
            'usuario_id' => $u->id,
            'centro_salud_id' => $centro->id,
            'jvpm' => 'JVPM-123',
            'nombres' => $nombres,
            'apellidos' => $apellidos,
        ]);
    }

    // ------------------------------------------------------------------
    // Seguridad / acceso
    // ------------------------------------------------------------------

    public function test_el_foro_requiere_token_pero_las_categorias_son_publicas(): void
    {
        $this->getJson('/api/foro/publicaciones')->assertStatus(401);

        $this->categoria();
        $this->getJson('/api/categorias_foro')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Salud general']);
    }

    public function test_solo_admin_crea_edita_y_borra_categorias(): void
    {
        $cat = $this->categoria();

        $this->actingAs($this->usuario('paciente'), 'api');
        $this->postJson('/api/categorias_foro', ['nombre' => 'Otra'])->assertStatus(403);

        $this->actingAs($this->usuario('admin', 'admin@test.com'), 'api');
        $this->postJson('/api/categorias_foro', ['nombre' => 'Otra'])->assertCreated();
        $this->postJson('/api/categorias_foro', ['nombre' => 'Otra'])->assertStatus(422); // nombre único
        $this->deleteJson("/api/categorias_foro/{$cat->id}")->assertOk();
    }

    public function test_no_se_puede_borrar_una_categoria_con_publicaciones(): void
    {
        $cat = $this->categoria();
        $this->publicacion($this->usuario(), $cat);

        $this->actingAs($this->usuario('admin', 'admin@test.com'), 'api');
        $this->deleteJson("/api/categorias_foro/{$cat->id}")->assertStatus(409);
    }

    // ------------------------------------------------------------------
    // Publicaciones
    // ------------------------------------------------------------------

    public function test_el_autor_sale_del_token_y_el_anonimato_oculta_la_identidad(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'autor@test.com');
        $otro = $this->usuario('medico', 'otro@test.com');

        $this->actingAs($autor, 'api');
        $r = $this->postJson('/api/foro/publicaciones', [
            'categoria_id' => $cat->id,
            'titulo' => 'Mi experiencia',
            'contenido' => 'Contenido de apoyo',
            'anonimo' => true,
            'autor_id' => $otro->id, // intento de suplantación: debe ignorarse
        ])->assertCreated();

        $r->assertJsonPath('es_mia', true)
          ->assertJsonPath('autor.id', $autor->id); // el dueño sí se ve

        $this->assertDatabaseHas('publicaciones_foro', [
            'id' => $r->json('id'),
            'autor_id' => $autor->id,
        ]);

        $id = $r->json('id');

        $this->actingAs($otro, 'api');
        $this->getJson("/api/foro/publicaciones/{$id}")
            ->assertOk()
            ->assertJsonPath('autor', null); // a los demás se oculta

        $this->actingAs($this->usuario('admin', 'admin@test.com'), 'api');
        $this->getJson("/api/foro/publicaciones/{$id}")
            ->assertOk()
            ->assertJsonPath('autor.id', $autor->id); // admin sí lo ve
    }

    public function test_al_publicar_sin_el_campo_anonimo_se_muestra_el_autor(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'ana@test.com');
        $this->perfilPaciente($autor, 'Ana', 'Perez');

        $this->actingAs($autor, 'api');

        // Sin "anonimo" en el body: la columna tiene default(true) por legado,
        // pero la API debe crear la publicación con el autor visible.
        $r = $this->postJson('/api/foro/publicaciones', [
            'categoria_id' => $cat->id,
            'titulo' => 'Mi experiencia',
            'contenido' => 'Contenido de apoyo',
        ])->assertCreated();

        $this->assertFalse((bool) $r->json('anonimo'));
        $r->assertJsonPath('autor.nombre', 'Ana Perez');
    }

    public function test_el_nombre_real_se_muestra_en_publicaciones_y_comentarios(): void
    {
        $cat = $this->categoria();

        $paciente = $this->usuario('paciente', 'ana@test.com');
        $this->perfilPaciente($paciente, 'Ana', 'Perez');

        $medico = $this->usuario('medico', 'luis@test.com');
        $this->perfilMedico($medico, 'Luis', 'Gomez');

        // Publicación de paciente: nombre y apellidos, sin "Dr(a)."
        $this->actingAs($paciente, 'api');
        $pub = $this->postJson('/api/foro/publicaciones', [
            'categoria_id' => $cat->id,
            'titulo' => 'De paciente',
            'contenido' => 'Hola',
            'anonimo' => false,
        ])->assertCreated();
        $pub->assertJsonPath('autor.nombre', 'Ana Perez');

        $com = $this->postJson("/api/foro/publicaciones/{$pub->json('id')}/comentarios", [
            'contenido' => 'Comentario de paciente',
        ])->assertCreated();
        $com->assertJsonPath('data.autor.nombre', 'Ana Perez');

        // Publicación y comentario de médico: conserva el "Dr(a)."
        $this->actingAs($medico, 'api');
        $pubM = $this->postJson('/api/foro/publicaciones', [
            'categoria_id' => $cat->id,
            'titulo' => 'De medico',
            'contenido' => 'Hola',
            'anonimo' => false,
        ])->assertCreated();
        $pubM->assertJsonPath('autor.nombre', 'Dr(a). Luis Gomez');

        $comM = $this->postJson("/api/foro/publicaciones/{$pubM->json('id')}/comentarios", [
            'contenido' => 'Comentario de medico',
        ])->assertCreated();
        $comM->assertJsonPath('data.autor.nombre', 'Dr(a). Luis Gomez');

        // En el listado también viaja el nombre.
        $this->getJson('/api/foro/publicaciones')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Ana Perez'])
            ->assertJsonFragment(['nombre' => 'Dr(a). Luis Gomez']);
    }

    public function test_validacion_al_crear_una_publicacion(): void
    {
        $this->actingAs($this->usuario(), 'api');

        $this->postJson('/api/foro/publicaciones', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['categoria_id', 'titulo', 'contenido']);
    }

    public function test_el_listado_filtra_por_categoria_busqueda_y_mias(): void
    {
        $cat1 = $this->categoria('Salud mental');
        $cat2 = $this->categoria('Nutricion');
        $yo = $this->usuario('paciente', 'yo@test.com');
        $otro = $this->usuario('medico', 'otro@test.com');

        $this->publicacion($yo, $cat1, ['titulo' => 'Ansiedad', 'contenido' => 'Respirar ayuda']);
        $this->publicacion($otro, $cat2, ['titulo' => 'Dieta', 'contenido' => 'Verduras']);

        $this->actingAs($yo, 'api');

        $this->getJson('/api/foro/publicaciones')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/foro/publicaciones?categoria_id={$cat1->id}")
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/foro/publicaciones?buscar=Verduras')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.titulo', 'Dieta');
        $this->getJson('/api/foro/publicaciones?mias=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.titulo', 'Ansiedad');
    }

    public function test_solo_el_autor_o_admin_editan_y_eliminan_una_publicacion(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'autor@test.com');
        $otro = $this->usuario('paciente', 'otro@test.com');
        $p = $this->publicacion($autor, $cat);

        $this->actingAs($otro, 'api');
        $this->putJson("/api/foro/publicaciones/{$p->id}", ['titulo' => 'Hackeado'])->assertStatus(403);
        $this->deleteJson("/api/foro/publicaciones/{$p->id}")->assertStatus(403);

        $this->actingAs($autor, 'api');
        $this->putJson("/api/foro/publicaciones/{$p->id}", ['titulo' => 'Editado'])
            ->assertOk()->assertJsonPath('titulo', 'Editado');

        $this->actingAs($this->usuario('admin', 'admin@test.com'), 'api');
        $this->deleteJson("/api/foro/publicaciones/{$p->id}")->assertOk();
        $this->assertDatabaseMissing('publicaciones_foro', ['id' => $p->id]);
    }

    // ------------------------------------------------------------------
    // Comentarios
    // ------------------------------------------------------------------

    public function test_comentarios_crud_permisos_y_anonimato(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'autor@test.com');
        $otro = $this->usuario('medico', 'otro@test.com');
        $p = $this->publicacion($autor, $cat);

        $this->actingAs($otro, 'api');
        $r = $this->postJson("/api/foro/publicaciones/{$p->id}/comentarios", [
            'contenido' => 'Mucho animo',
        ])->assertCreated();

        $id = $r->json('data.id');
        $r->assertJsonPath('data.es_mio', true)
          ->assertJsonPath('data.autor.rol', 'medico');

        $this->postJson("/api/foro/publicaciones/{$p->id}/comentarios", ['contenido' => '   '])
            ->assertStatus(422);

        // El dueño de la publicación NO puede editar/borrar el comentario de otro.
        $this->actingAs($autor, 'api');
        $this->putJson("/api/foro/comentarios/{$id}", ['contenido' => 'No'])->assertStatus(403);
        $this->deleteJson("/api/foro/comentarios/{$id}")->assertStatus(403);

        $this->actingAs($otro, 'api');
        $this->putJson("/api/foro/comentarios/{$id}", ['contenido' => 'Editado'])
            ->assertOk()->assertJsonPath('data.contenido', 'Editado');
        $this->getJson("/api/foro/publicaciones/{$p->id}/comentarios")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.es_mio', true);

        // El admin sí puede moderar (borrar).
        $this->actingAs($this->usuario('admin', 'admin@test.com'), 'api');
        $this->deleteJson("/api/foro/comentarios/{$id}")->assertOk();
        $this->assertDatabaseMissing('comentarios_foro', ['id' => $id]);
    }

    public function test_el_autor_anonimo_tambien_se_oculta_en_sus_comentarios(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'autor@test.com');
        $p = $this->publicacion($autor, $cat, ['anonimo' => true]);

        $this->actingAs($autor, 'api');
        $this->postJson("/api/foro/publicaciones/{$p->id}/comentarios", ['contenido' => 'Gracias'])
            ->assertCreated()
            ->assertJsonPath('data.autor.nombre', 'Anonimo')
            ->assertJsonPath('data.autor.id', null);
    }

    // ------------------------------------------------------------------
    // Me gusta
    // ------------------------------------------------------------------

    public function test_me_gusta_es_por_usuario_idempotente_y_solo_admin_ve_quien_lo_dio(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'autor@test.com');
        $otro = $this->usuario('medico', 'otro@test.com');
        $p = $this->publicacion($autor, $cat);

        $this->actingAs($otro, 'api');

        $this->postJson("/api/foro/publicaciones/{$p->id}/me-gusta")
            ->assertOk()->assertJsonPath('le_gusta', true)->assertJsonPath('me_gusta_count', 1);
        $this->postJson("/api/foro/publicaciones/{$p->id}/me-gusta")
            ->assertOk()->assertJsonPath('le_gusta', false)->assertJsonPath('me_gusta_count', 0);

        // dar() es idempotente: la segunda vez responde 200, no duplica.
        $this->putJson("/api/foro/publicaciones/{$p->id}/me-gusta")->assertCreated();
        $this->putJson("/api/foro/publicaciones/{$p->id}/me-gusta")->assertOk();
        $this->assertDatabaseCount('me_gusta_foro', 1);

        $this->getJson("/api/foro/publicaciones/{$p->id}/me-gusta")
            ->assertOk()->assertJsonPath('le_gusta', true);
        $this->getJson('/api/foro/mis-me-gusta')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $p->id);

        $this->getJson("/api/foro/publicaciones/{$p->id}/me-gusta/usuarios")->assertStatus(403);

        $this->actingAs($this->usuario('admin', 'admin@test.com'), 'api');
        $this->getJson("/api/foro/publicaciones/{$p->id}/me-gusta/usuarios")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($otro, 'api');
        $this->deleteJson("/api/foro/publicaciones/{$p->id}/me-gusta")
            ->assertOk()->assertJsonPath('le_gusta', false);
        $this->assertDatabaseCount('me_gusta_foro', 0);
    }

    public function test_al_borrar_una_publicacion_se_limpian_comentarios_y_me_gusta(): void
    {
        $cat = $this->categoria();
        $autor = $this->usuario('paciente', 'autor@test.com');
        $p = $this->publicacion($autor, $cat);

        $this->actingAs($autor, 'api');
        $this->postJson("/api/foro/publicaciones/{$p->id}/comentarios", ['contenido' => 'Apoyo'])->assertCreated();
        $this->postJson("/api/foro/publicaciones/{$p->id}/me-gusta")->assertOk();

        $this->assertDatabaseCount('comentarios_foro', 1);
        $this->assertDatabaseCount('me_gusta_foro', 1);

        $this->deleteJson("/api/foro/publicaciones/{$p->id}")->assertOk();

        $this->assertDatabaseCount('publicaciones_foro', 0);
        $this->assertDatabaseCount('comentarios_foro', 0);
        $this->assertDatabaseCount('me_gusta_foro', 0);
    }
}

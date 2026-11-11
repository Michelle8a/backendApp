@extends('layouts.app')

@section('title', 'Hi! Doctor | Servicios Médicos Digitales')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        /* Fondos: de más oscuro a más claro */
        --ds-bg-0: #050f1e;
        --ds-bg-1: #081a30;
        --ds-bg-2: #0b2240;
        --ds-card: #0f2a4a;
        --ds-card-hover: #133459;
        /* Líneas y acento */
        --ds-line: #1c3d66;
        --ds-line-soft: #16314f;
        --ds-accent: #4f9be0;
        --ds-accent-soft: rgba(79, 155, 224, .14);
        /* Texto */
        --ds-title: #ffffff;
        --ds-text: #b4c6da;
        --ds-muted: #8aa2bd;
        /* Sistema */
        --ds-radius: 8px;
        --ds-space: 88px;
    }

    html { scroll-behavior: smooth; }
    body { background: var(--ds-bg-0); color: var(--ds-text); font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }

    .docstor-page { overflow: hidden; }
    .doc-container { max-width: 1140px; }
    .docstor-page a:focus-visible { outline: 2px solid var(--ds-accent); outline-offset: 3px; }

    /* ---------- Secciones ---------- */
    .doc-section { padding: var(--ds-space) 0; background: var(--ds-bg-1); }
    .doc-section.alt { background: var(--ds-bg-2); }
    .doc-section + .doc-section { border-top: 1px solid var(--ds-line-soft); }

    .doc-heading { max-width: 680px; margin-bottom: 48px; }
    .doc-heading h2 {
        color: var(--ds-title);
        font-size: clamp(28px, 3vw, 36px);
        font-weight: 700;
        letter-spacing: -.5px;
        line-height: 1.2;
        margin: 0 0 14px;
    }
    .doc-heading h2::after {
        content: "";
        display: block;
        width: 44px;
        height: 3px;
        margin-top: 16px;
        background: var(--ds-accent);
        border-radius: 2px;
    }
    .doc-heading p { color: var(--ds-text); font-size: 16px; line-height: 1.75; margin: 0; }

    /* ---------- Tarjetas base ---------- */
    .doc-card {
        height: 100%;
        background: var(--ds-card);
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius);
    }
    .doc-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        color: var(--ds-accent);
        background: var(--ds-accent-soft);
        border: 1px solid rgba(79, 155, 224, .3);
        border-radius: var(--ds-radius);
        font-size: 20px;
    }

    /* ---------- Hero ---------- */
    .doc-hero {
        padding: 104px 0 96px;
        background: linear-gradient(180deg, var(--ds-bg-0) 0%, #071a31 100%);
        border-bottom: 1px solid var(--ds-line-soft);
    }
    .doc-hero h1 {
        max-width: 640px;
        color: var(--ds-title);
        font-size: clamp(38px, 5vw, 56px);
        font-weight: 800;
        letter-spacing: -1.4px;
        line-height: 1.1;
        margin: 0;
    }
    .doc-hero-copy { max-width: 560px; margin: 24px 0 0; font-size: 18px; line-height: 1.75; color: var(--ds-text); }
    .doc-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 36px; }
    .doc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 22px;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        border-radius: var(--ds-radius);
        border: 1px solid transparent;
        transition: background-color .2s ease, border-color .2s ease;
    }
    .doc-btn-primary { color: #fff; background: #2a7bc8; border-color: #2a7bc8; }
    .doc-btn-primary:hover { color: #fff; background: #3a8ad8; border-color: #3a8ad8; }
    .doc-btn-outline { color: var(--ds-title); border-color: var(--ds-line); background: transparent; }
    .doc-btn-outline:hover { color: var(--ds-title); background: var(--ds-card); border-color: var(--ds-accent); }

    .doc-hero-panel { padding: 32px; background: var(--ds-bg-2); border: 1px solid var(--ds-line); border-radius: var(--ds-radius); }
    .doc-hero-panel h3 { color: var(--ds-title); font-size: 20px; font-weight: 700; margin: 0 0 10px; }
    .doc-hero-panel > p { color: var(--ds-text); font-size: 15px; line-height: 1.7; margin: 0 0 24px; }
    .doc-panel-list { list-style: none; margin: 0; padding: 0; border-top: 1px solid var(--ds-line-soft); }
    .doc-panel-list li {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 0;
        color: var(--ds-title);
        font-size: 15px;
        font-weight: 600;
        border-bottom: 1px solid var(--ds-line-soft);
    }
    .doc-panel-list small { display: block; color: var(--ds-muted); font-size: 13px; font-weight: 400; margin-top: 2px; }

    /* ---------- Sobre DocStor ---------- */
    .doc-about { padding: 36px; }
    .doc-about h3 { display: flex; align-items: center; gap: 12px; color: var(--ds-title); font-size: 20px; font-weight: 700; margin: 0 0 20px; }
    .doc-about p { color: var(--ds-text); line-height: 1.8; margin: 0 0 14px; }
    .doc-about p:last-child { margin-bottom: 0; }
    .doc-objective { padding: 36px; background: var(--ds-bg-0); border-color: var(--ds-line); }
    .doc-objective h3 { color: var(--ds-title); font-size: 20px; font-weight: 700; margin: 18px 0 14px; }
    .doc-objective p { line-height: 1.8; margin: 0; }
    .doc-objective .doc-divider { height: 1px; margin: 26px 0; background: var(--ds-line); }
    .doc-objective h4 { display: flex; align-items: center; gap: 10px; color: var(--ds-title); font-size: 15px; font-weight: 600; margin: 0 0 8px; }
    .doc-objective h4 i { color: var(--ds-accent); }
    .doc-objective .small-copy { color: var(--ds-muted); font-size: 14px; }

    /* ---------- Funcionalidades ---------- */
    .doc-feature { padding: 28px; transition: background-color .2s ease, border-color .2s ease; }
    .doc-feature:hover { background: var(--ds-card-hover); border-color: var(--ds-accent); }
    .doc-feature h3 { color: var(--ds-title); font-size: 17px; font-weight: 600; margin: 20px 0 10px; }
    .doc-feature p { color: var(--ds-text); font-size: 14.5px; line-height: 1.7; margin: 0; }

    /* ---------- Tecnologías ---------- */
    .doc-tech { display: flex; align-items: center; gap: 16px; padding: 18px 20px; }
    .doc-tech h3 { color: var(--ds-title); font-size: 15px; font-weight: 600; margin: 0 0 3px; }
    .doc-tech p { color: var(--ds-muted); font-size: 13px; margin: 0; }
    .doc-note {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 28px;
        padding: 16px 20px;
        color: var(--ds-text);
        font-size: 14.5px;
        background: var(--ds-accent-soft);
        border: 1px solid rgba(79, 155, 224, .3);
        border-radius: var(--ds-radius);
    }
    .doc-note i { color: var(--ds-accent); font-size: 18px; }

    /* ---------- Equipo ---------- */
    .doc-team-card { padding: 28px 18px; text-align: center; }
    .doc-team-card .doc-icon { width: 56px; height: 56px; font-size: 24px; margin-bottom: 18px; }
    .doc-team-card h3 { min-height: 44px; color: var(--ds-title); font-size: 15px; font-weight: 600; line-height: 1.45; margin: 0 0 8px; }
    .doc-team-card p { color: var(--ds-muted); font-size: 13px; margin: 0 0 16px; }
    .doc-team-role {
        display: inline-block;
        padding: 5px 12px;
        color: var(--ds-text);
        font-size: 12px;
        font-weight: 500;
        background: var(--ds-bg-0);
        border: 1px solid var(--ds-line);
        border-radius: 999px;
    }

    /* ---------- Descarga ---------- */
    .doc-download { padding: 64px 0; background: var(--ds-bg-0); border-top: 1px solid var(--ds-line); border-bottom: 1px solid var(--ds-line); }
    .doc-download h2 { color: var(--ds-title); font-size: clamp(24px, 3vw, 32px); font-weight: 700; letter-spacing: -.4px; margin: 14px 0 12px; }
    .doc-download p { max-width: 620px; line-height: 1.75; margin: 0; }
    .doc-download small { display: block; margin-top: 12px; color: var(--ds-muted); font-size: 13px; }

    /* ---------- Información académica ---------- */
    .doc-academic { overflow: hidden; }
    .doc-academic-item { display: flex; gap: 16px; height: 100%; padding: 28px; }
    .doc-academic-item h3 { color: var(--ds-title); font-size: 15px; font-weight: 600; margin: 0 0 6px; }
    .doc-academic-item p { color: var(--ds-text); font-size: 14.5px; line-height: 1.65; margin: 0; }
    @media (min-width: 768px) {
        .doc-academic .col-md-4 + .col-md-4 .doc-academic-item { border-left: 1px solid var(--ds-line); }
    }
    @media (max-width: 767px) {
        .doc-academic .col-md-4 + .col-md-4 .doc-academic-item { border-top: 1px solid var(--ds-line); }
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 991px) {
        :root { --ds-space: 72px; }
        .doc-hero { padding: 80px 0; }
    }
    @media (max-width: 575px) {
        :root { --ds-space: 60px; }
        .doc-hero { padding: 64px 0; }
        .doc-hero-copy { font-size: 16px; }
        .doc-about, .doc-objective, .doc-hero-panel { padding: 26px; }
        .doc-btn { width: 100%; }
        .doc-download { text-align: center; }
    }
</style>
<div class="docstor-page">

    <!-- PRESENTACIÓN PRINCIPAL -->
    <section class="doc-hero">
        <div class="container doc-container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-7">
                    <h1>Servicios médicos digitales con Hi! Doctor</h1>
                    <p class="doc-hero-copy">
                        Una plataforma que conecta a pacientes y profesionales de la salud.
                        Consultas virtuales, recetas electrónicas, historiales médicos
                        y comunicación directa en un solo lugar.
                    </p>
                    <div class="doc-actions">
                        <a href="#nosotros" class="doc-btn doc-btn-primary">Conocer el proyecto</a>
                        <a href="#descargar" class="doc-btn doc-btn-outline">
                            <i class="bi bi-download"></i> Descargar aplicación
                        </a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="doc-hero-panel">
                        <h3>Atención médica digital</h3>
                        <p>
                            Herramientas de consulta, comunicación y seguimiento para acercar
                            los servicios de salud a las personas.
                        </p>
                        <ul class="doc-panel-list">
                            <li>
                                <span class="doc-icon"><i class="bi bi-calendar2-check"></i></span>
                                <span>Citas médicas<small>Programa consultas virtuales</small></span>
                            </li>
                            <li>
                                <span class="doc-icon"><i class="bi bi-chat-dots"></i></span>
                                <span>Chat con especialistas<small>Comunicación en tiempo real</small></span>
                            </li>
                            <li>
                                <span class="doc-icon"><i class="bi bi-file-medical"></i></span>
                                <span>Recetas electrónicas<small>Consulta y descarga desde tu móvil</small></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SOBRE DOCSTOR -->
    <section class="doc-section" id="nosotros">
        <div class="container doc-container">
            <div class="doc-heading">
                <h2>¿Qué es Hi! Doctor?</h2>
                <p>
                    Hi! Doctor es un proyecto de desarrollo de software enfocado en la telemedicina.
                    Facilita el acceso a servicios médicos mediante herramientas digitales que
                    mejoran la comunicación entre pacientes y profesionales de la salud.
                </p>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <div class="doc-card doc-about">
                        <h3><span class="doc-icon"><i class="bi bi-activity"></i></span>Nuestra propuesta</h3>
                        <p>
                            Desarrollamos una aplicación móvil que permite consultar información médica,
                            gestionar recetas electrónicas, revisar historiales clínicos y comunicarse
                            con médicos y especialistas.
                        </p>
                        <p>
                            Complementamos la experiencia con una plataforma web para la
                            administración y gestión de los recursos del sistema.
                        </p>
                        <p>
                            Buscamos una experiencia intuitiva, accesible y organizada,
                            que mantenga la seguridad y confidencialidad de la información médica.
                        </p>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="doc-card doc-objective">
                        <span class="doc-icon"><i class="bi bi-bullseye"></i></span>
                        <h3>Nuestro objetivo</h3>
                        <p>
                            Integrar tecnología y servicios de salud en una plataforma que facilite
                            la gestión de consultas, información clínica y comunicación médica,
                            para una atención más accesible y eficiente.
                        </p>
                        <div class="doc-divider"></div>
                        <h4><i class="bi bi-shield-check"></i>Seguridad y confianza</h4>
                        <p class="small-copy">
                            La protección de los datos y la privacidad son aspectos fundamentales
                            de nuestra solución.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FUNCIONALIDADES -->
    <section class="doc-section alt" id="funcionalidades">
        <div class="container doc-container">
            <div class="doc-heading">
                <h2>Funcionalidades de la plataforma</h2>
                <p>
                    Herramientas para facilitar la interacción entre pacientes y profesionales
                    médicos, y la gestión de la información de salud.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-person-lock"></i></span>
                        <h3>Autenticación y perfiles</h3>
                        <p>Registro, inicio de sesión y administración de la información personal y médica de los usuarios.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-calendar2-check"></i></span>
                        <h3>Consultas y citas médicas</h3>
                        <p>Búsqueda de médicos por especialidad, ubicación, disponibilidad e idioma, y programación de consultas virtuales.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-prescription2"></i></span>
                        <h3>Recetas electrónicas</h3>
                        <p>Consulta, descarga y gestión de recetas electrónicas, con opción de compartirlas con farmacias.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-folder2-open"></i></span>
                        <h3>Historial médico digital</h3>
                        <p>Registros de consultas, recetas y resultados de pruebas organizados en un solo espacio digital.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-chat-square-dots"></i></span>
                        <h3>Chat con especialistas</h3>
                        <p>Comunicación en tiempo real entre pacientes y profesionales de la salud, con notificaciones y recordatorios.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-people"></i></span>
                        <h3>Foro de salud</h3>
                        <p>Espacio donde los usuarios comparten experiencias y reciben apoyo de la comunidad.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-geo-alt"></i></span>
                        <h3>Integración con Google Maps</h3>
                        <p>Ubicación de médicos y centros de atención médica para facilitar su localización.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-bell"></i></span>
                        <h3>Notificaciones</h3>
                        <p>Avisos de mensajes, recordatorios de citas y alertas sobre la actividad de los usuarios.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="doc-card doc-feature">
                        <span class="doc-icon"><i class="bi bi-bar-chart"></i></span>
                        <h3>Visualización de información</h3>
                        <p>Presentación clara de datos médicos mediante recursos gráficos.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- TECNOLOGÍAS -->
    <section class="doc-section" id="tecnologias">
        <div class="container doc-container">
            <div class="doc-heading">
                <h2>Tecnologías utilizadas</h2>
                <p>
                    Herramientas con las que construimos la aplicación móvil, la plataforma web
                    y la gestión de la información.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-phone"></i></span><div><h3>React Native</h3><p>Aplicación móvil</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-code-slash"></i></span><div><h3>Laravel</h3><p>Framework web y backend</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-filetype-php"></i></span><div><h3>PHP</h3><p>Lenguaje de programación</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-box-seam"></i></span><div><h3>Composer</h3><p>Gestión de dependencias</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-database"></i></span><div><h3>SQLite</h3><p>Almacenamiento local</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-database-check"></i></span><div><h3>MySQL</h3><p>Gestión de datos web</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-bootstrap"></i></span><div><h3>Bootstrap</h3><p>Diseño de interfaz web</p></div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="doc-card doc-tech"><span class="doc-icon"><i class="bi bi-map"></i></span><div><h3>Google Maps</h3><p>Servicios de ubicación</p></div></div></div>
            </div>

            <div class="doc-note">
                <i class="bi bi-shield-lock-fill"></i>
                Seguridad, protección de datos y optimización forman parte de las consideraciones del proyecto.
            </div>
        </div>
    </section>

    <!-- EQUIPO -->
    <section class="doc-section alt" id="equipo">
        <div class="container doc-container">
            <div class="doc-heading">
                <h2>Equipo de desarrollo</h2>
                <p>
                    Somos estudiantes de Ingeniería de Desarrollo de Software y trabajamos
                    en conjunto para diseñar y desarrollar una solución tecnológica orientada
                    a los servicios médicos digitales.
                </p>
            </div>

            @php
                $integrantes = [
                    ['nombre' => 'Diego Mauricio Mejía Pérez', 'carnet' => '230519'],
                    ['nombre' => 'Tatiana Esmeralda Mejía Martínez', 'carnet' => '112322'],
                    ['nombre' => 'Karina Michelle Leiva Ochoa', 'carnet' => '087523'],
                    ['nombre' => 'Carlos José Alfaro Jiménez', 'carnet' => '084023'],
                    ['nombre' => 'Daniela Fernanda Morales Corea', 'carnet' => '113623'],
                ];
            @endphp

            <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-5">
                @foreach($integrantes as $integrante)
                    <div class="col">
                        <article class="doc-card doc-team-card">
                            <span class="doc-icon"><i class="bi bi-person-fill"></i></span>
                            <h3>{{ $integrante['nombre'] }}</h3>
                            <p>Carnet: {{ $integrante['carnet'] }}</p>
                            <span class="doc-team-role">Equipo de desarrollo</span>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- DESCARGA -->
    <section class="doc-download" id="descargar">
        <div class="container doc-container">
            <div class="row align-items-center gy-4">
                <div class="col-lg-8">
                    <span class="doc-icon"><i class="bi bi-android2"></i></span>
                    <h2>Hi! Doctor en tu dispositivo móvil</h2>
                    <p>
                        Descarga la aplicación y conoce los servicios médicos digitales
                        desde tu celular. Accede a las funcionalidades desarrolladas por nuestro equipo.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <!-- Colocar DocStor.apk en public/apk/ -->
                    <a href="{{ asset('apk/DocStor.apk') }}" class="doc-btn doc-btn-primary" download>
                        <i class="bi bi-download"></i> Descargar APK
                    </a>
                    <small>Aplicación para Android</small>
                </div>
            </div>
        </div>
    </section>

    <!-- INFORMACIÓN ACADÉMICA -->
    <section class="doc-section">
        <div class="container doc-container">
            <div class="doc-heading">
                <h2>Desarrollo del proyecto</h2>
            </div>

            <div class="doc-card doc-academic">
                <div class="row g-0">
                    <div class="col-md-4">
                        <div class="doc-academic-item">
                            <span class="doc-icon"><i class="bi bi-mortarboard"></i></span>
                            <div>
                                <h3>Institución</h3>
                                <p>Escuela de Ingeniería en Computación</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-academic-item">
                            <span class="doc-icon"><i class="bi bi-book"></i></span>
                            <div>
                                <h3>Carrera y materia</h3>
                                <p>Ingeniería de Desarrollo de Software<br>Diseño de Aplicaciones Móviles Avanzadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-academic-item">
                            <span class="doc-icon"><i class="bi bi-person-badge"></i></span>
                            <div>
                                <h3>Docente</h3>
                                <p>Ing. Jefferson Pineda</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
<?php
/**
 * Canonical editorial renderer for the patient cases page.
 *
 * Replaces inherited database layouts that can collapse into narrow columns or
 * create uncontrolled vertical whitespace. Publication governance remains in
 * nvx-page-hygiene.php.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

/** Whether the current request is the clinical cases page. */
function nvxCasesPageIsCurrent(): bool {
    if ( ! is_page() ) {
        return false;
    }

    $page_id = (int) get_queried_object_id();
    $slug    = (string) get_post_field( 'post_name', $page_id );

    return 2645 === $page_id || in_array( $slug, array( 'casos-de-pacientes', 'casos-clinicos' ), true );
}

/** Enqueue the isolated cases stylesheet only on the canonical route. */
function nvxCasesPageEnqueueAssets(): void {
    if ( ! nvxCasesPageIsCurrent() ) {
        return;
    }

    $relative = 'assets/css/nvx-cases-editorial.css';
    wp_enqueue_style(
        'nvx-cases-editorial',
        get_template_directory_uri() . '/' . $relative,
        array( 'nvx-components' ),
        nvx_asset_version( $relative )
    );
}
add_action( 'wp_enqueue_scripts', 'nvxCasesPageEnqueueAssets', 35 );

/** Add a stable page-state class for scoped layout ownership. */
function nvxCasesPageBodyClass( array $classes ): array {
    if ( nvxCasesPageIsCurrent() ) {
        $classes[] = 'nvx-cases-editorial-page';
    }

    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvxCasesPageBodyClass' );

/** Build the canonical cases page markup. */
function nvxCasesPageMarkup(): string {
    $area_corporal = 'CONTORNO CORPORAL';
    $evolutions = array(
        array(
            'image' => content_url( '/uploads/2026/07/Endolift-Papada.webp' ),
            'alt'   => 'Evolución clínica documentada de perfil, papada y cuello',
            'area'  => 'ROSTRO Y CUELLO',
            'title' => 'Perfil, papada y definición mandibular',
        ),
        array(
            'image' => content_url( '/uploads/2026/07/Endolift-Full-Face.webp' ),
            'alt'   => 'Evolución clínica documentada de arquitectura facial integral',
            'area'  => 'ARQUITECTURA FACIAL',
            'title' => 'Calidad cutánea y armonía facial',
        ),
        array(
            'image' => content_url( '/uploads/2026/07/Endolift-Brazos.webp' ),
            'alt'   => 'Evolución clínica documentada de brazos',
            'area'  => $area_corporal,
            'title' => 'Brazos y continuidad con la axila',
        ),
        array(
            'image' => content_url( '/uploads/2026/07/Endolift-Abdomen.webp' ),
            'alt'   => 'Evolución clínica documentada de abdomen y flancos',
            'area'  => $area_corporal,
            'title' => 'Abdomen y flancos',
        ),
        array(
            'image' => content_url( '/uploads/2026/07/Endolift-Espalda-Flancos-y-Sujetador.webp' ),
            'alt'   => 'Evolución clínica documentada de espalda y zona del sujetador',
            'area'  => $area_corporal,
            'title' => 'Espalda y zona del sujetador',
        ),
    );

    $principles = array(
        array(
            'number' => '01',
            'title'  => 'Misma paciente',
            'copy'   => 'La comparación corresponde a la misma persona y al área tratada.',
        ),
        array(
            'number' => '02',
            'title'  => 'Contexto temporal',
            'copy'   => 'La fecha y el momento del seguimiento condicionan la interpretación.',
        ),
        array(
            'number' => '03',
            'title'  => 'Captura coherente',
            'copy'   => 'Postura, encuadre, luz y distancia se controlan para reducir distorsiones.',
        ),
        array(
            'number' => '04',
            'title'  => 'Validación médica',
            'copy'   => 'Cada caso se revisa dentro de su diagnóstico, procedimiento y evolución clínica.',
        ),
    );

    ob_start();
    ?>
    <article class="nvx-cases-page" aria-labelledby="nvx-cases-title">
        <section class="nvx-cases-hero">
            <div class="nvx-cases-hero__copy">
                <p class="nvx-cases-eyebrow">CASOS CLÍNICOS · NUVANX MADRID</p>
                <h1 id="nvx-cases-title">La evolución necesita contexto, no una promesa.</h1>
                <p class="nvx-cases-hero__lead">Documentamos cambios reales con consentimiento, seguimiento médico y criterios de captura comparables. Ningún caso permite anticipar el resultado de otra persona.</p>
                <a class="nvx-btn nvx-btn--primary" href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">Solicitar valoración médica</a>
            </div>
            <div class="nvx-cases-hero__media">
                <img src="<?php echo esc_url( content_url( '/uploads/2026/07/proceso-medico-laser-nuvanx-madrid.webp' ) ); ?>" alt="Documentación editorial de la experiencia clínica NUVANX" fetchpriority="high" decoding="async">
            </div>
        </section>

        <section class="nvx-cases-method" aria-labelledby="nvx-cases-method-title">
            <header class="nvx-cases-section-header">
                <div>
                    <p class="nvx-cases-eyebrow">CÓMO LEER UN CASO</p>
                    <h2 id="nvx-cases-method-title">Cuatro condiciones antes de interpretar una imagen.</h2>
                </div>
                <p>La fotografía clínica es una herramienta de seguimiento. No sustituye la exploración, no elimina variables individuales y no funciona como garantía comercial.</p>
            </header>
            <div class="nvx-cases-method__grid">
                <?php foreach ( $principles as $principle ) : ?>
                    <article class="nvx-cases-method-card">
                        <p class="nvx-cases-method-card__number" aria-hidden="true"><?php echo esc_html( $principle['number'] ); ?></p>
                        <h3><?php echo esc_html( $principle['title'] ); ?></h3>
                        <p><?php echo esc_html( $principle['copy'] ); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="nvx-cases-evolution" aria-labelledby="nvx-cases-evolution-title">
            <header class="nvx-cases-section-header">
                <div>
                    <p class="nvx-cases-eyebrow">DOCUMENTACIÓN CLÍNICA DISPONIBLE</p>
                    <h2 id="nvx-cases-evolution-title">Evolución por zona</h2>
                </div>
                <p>La indicación, técnica, número de sesiones y tiempo de seguimiento varían. La valoración médica determina qué referencia es pertinente para cada caso.</p>
            </header>
            <div class="nvx-cases-evolution__grid">
                <?php foreach ( $evolutions as $evolution ) : ?>
                    <figure class="nvx-cases-evolution-card">
                        <div class="nvx-cases-evolution-card__media">
                            <img src="<?php echo esc_url( $evolution['image'] ); ?>" alt="<?php echo esc_attr( $evolution['alt'] ); ?>" loading="lazy" decoding="async">
                        </div>
                        <figcaption>
                            <span><?php echo esc_html( $evolution['area'] ); ?></span>
                            <strong><?php echo esc_html( $evolution['title'] ); ?></strong>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="nvx-cases-disclosure" aria-labelledby="nvx-cases-disclosure-title">
            <div>
                <p class="nvx-cases-eyebrow">CRITERIO MÉDICO</p>
                <h2 id="nvx-cases-disclosure-title">Una referencia visual no define tu tratamiento.</h2>
            </div>
            <div class="nvx-cases-disclosure__copy">
                <p>Durante la consulta se diferencia qué parte depende de grasa, piel, estructura, inflamación, cicatriz o calidad cutánea. A partir de esa lectura se explican alternativas, límites y recuperación esperable.</p>
                <p>Las imágenes se muestran con finalidad informativa y clínica. Los resultados son individuales y pueden variar.</p>
                <a class="nvx-cases-text-link" href="<?php echo esc_url( home_url( '/que-exigir-clinica-medicina-estetica-segura/' ) ); ?>">Qué exigir a una clínica médica <span aria-hidden="true">→</span></a>
            </div>
        </section>

        <section class="nvx-cases-closure" aria-labelledby="nvx-cases-closure-title">
            <p class="nvx-cases-eyebrow">VALORACIÓN INDIVIDUAL</p>
            <h2 id="nvx-cases-closure-title">Tu punto de partida se evalúa en consulta.</h2>
            <p>Confirmamos indicación, alternativas, tiempos y presupuesto después de estudiar tu caso.</p>
            <div class="nvx-cases-closure__actions">
                <a class="nvx-btn nvx-btn--primary" href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">Solicitar valoración médica</a>
                <a class="nvx-btn nvx-btn--secondary-on-dark" href="<?php echo esc_url( nvx_cta_whatsapp_url() ); ?>" target="_blank" rel="noopener noreferrer">Contactar por WhatsApp</a>
            </div>
        </section>
    </article>
    <?php
    return (string) ob_get_clean();
}

/** Replace the inherited page content after route-level presentation filters. */
function nvxCasesPageReplaceContent( $content ) {
    if ( is_admin() || ! nvxCasesPageIsCurrent() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    return nvxCasesPageMarkup();
}
add_filter( 'the_content', 'nvxCasesPageReplaceContent', 120 );

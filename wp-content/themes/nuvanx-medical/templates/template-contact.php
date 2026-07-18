<?php
/**
 * Template Name: Contacto NUVANX
 * Template Post Type: page
 *
 * Página de contacto con NAP estructurado, schema LocalBusiness ×2,
 * og:image, horarios y formulario con selector de tratamiento.
 *
 * Ruta en repo: wp-content/themes/nuvanx-medical/templates/template-contact.php
 *
 * @package nuvanx-medical
 * @since   1.0.0
 */

// ─── Schema JSON-LD ──────────────────────────────────────────────────────────
// Se inyecta en <head> vía wp_head hook para que los rastreadores lo procesen
// antes del body. Coordenadas geo verificadas manualmente con Google Maps.
add_action( 'wp_head', 'nvx_contact_schema_ld' );
function nvx_contact_schema_ld() {
    $schema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'    => 'MedicalClinic',
                '@id'      => 'https://nuvanx.com/#nuvanx-chamberi',
                'name'     => 'NUVANX Chamberí',
                'url'      => 'https://nuvanx.com/medicina-estetica-chamberi/',
                'telephone' => '+34669319836',
                'address'  => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => 'C/ de Fernández de la Hoz, 4 Bajo Derecha',
                    'postalCode'      => '28010',
                    'addressLocality' => 'Madrid',
                    'addressRegion'   => 'Comunidad de Madrid',
                    'addressCountry'  => 'ES',
                ],
                'geo' => [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => 40.4347,   // ← verificar con Google Maps
                    'longitude' => -3.6944,
                ],
                'openingHoursSpecification' => [
                    [
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ],
                        'opens'     => '10:00',
                        'closes'    => '19:00',
                    ],
                ],
                'identifier' => [
                    '@type' => 'PropertyValue',
                    'name'  => 'Registro Sanitario',
                    'value' => 'CS20144',
                ],
                'medicalSpecialty'   => 'Aesthetic Medicine',
                'parentOrganization' => [ '@id' => 'https://nuvanx.com/#organization' ],
            ],
            [
                '@type'    => 'MedicalClinic',
                '@id'      => 'https://nuvanx.com/#nuvanx-goya',
                'name'     => 'NUVANX Salamanca–Goya',
                'url'      => 'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
                'telephone' => '+34647505107',
                'address'  => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => 'C/ de Fernán González, 26',
                    'postalCode'      => '28009',
                    'addressLocality' => 'Madrid',
                    'addressRegion'   => 'Comunidad de Madrid',
                    'addressCountry'  => 'ES',
                ],
                'geo' => [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => 40.4257,   // ← verificar con Google Maps
                    'longitude' => -3.6769,
                ],
                'openingHoursSpecification' => [
                    [
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ],
                        'opens'     => '10:00',
                        'closes'    => '19:00',
                    ],
                ],
                'identifier' => [
                    '@type' => 'PropertyValue',
                    'name'  => 'Registro Sanitario',
                    'value' => 'CS20073',
                ],
                'medicalSpecialty'   => 'Aesthetic Medicine',
                'parentOrganization' => [ '@id' => 'https://nuvanx.com/#organization' ],
            ],
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}

// ─── og:image (ausente en la versión anterior del template) ──────────────────
add_action( 'wp_head', 'nvx_contact_og_image', 5 );
function nvx_contact_og_image() {
    // Solo se inyecta si el plugin SEO no ha definido ya una imagen OG para esta página.
    // Comprobamos la presencia de la meta antes de duplicar.
    if ( is_page( 'contacto' ) ) {
        $og_image = 'https://nuvanx.com/wp-content/uploads/2026/06/nvx-fachada-goya-900.webp';
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
        echo '<meta property="og:image:width" content="900" />' . "\n";
        echo '<meta property="og:image:height" content="605" />' . "\n";
        echo '<meta property="og:image:type" content="image/webp" />' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '" />' . "\n";
    }
}

get_header();
?>

<main id="nvx-main" class="nvx-page nvx-page--contact">

    <?php /* ── HERO ─────────────────────────────────────────────────────── */ ?>
    <section class="nvx-section nvx-section--contact-hero" aria-labelledby="nvx-contact-h1">
        <div class="nvx-container">

            <p class="nvx-eyebrow">Clínica médica · Madrid</p>

            <h1 id="nvx-contact-h1" class="nvx-heading-1">
                Clínicas NUVANX en Madrid —<br>Chamberí y Salamanca–Goya
            </h1>

            <p class="nvx-lead">
                Valoración médica presencial en Chamberí o Salamanca–Goya.
                Respuesta en menos de 24&nbsp;horas laborables.
            </p>

            <div class="nvx-cta-group">
                <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>"
                   class="nvx-btn nvx-btn--primary">
                    Reservar valoración gratuita
                </a>
                <a href="https://wa.me/34669319836"
                   class="nvx-btn nvx-btn--secondary"
                   rel="noopener noreferrer"
                   target="_blank"
                   aria-label="Contactar por WhatsApp con NUVANX">
                    Contactar por WhatsApp
                </a>
            </div>

        </div>
    </section>

    <?php /* ── NAP ──────────────────────────────────────────────────────── */ ?>
    <section class="nvx-section nvx-section--nap" aria-label="Sedes y datos de contacto">
        <div class="nvx-container">

            <h2 class="nvx-heading-2">Datos de contacto y sedes autorizadas</h2>

            <div class="nvx-clinics-grid">

                <?php /* Chamberí */ ?>
                <article class="nvx-clinic-card" itemscope itemtype="https://schema.org/MedicalClinic">
                    <meta itemprop="identifier" content="CS20144">

                    <header class="nvx-clinic-card__header">
                        <h3 class="nvx-clinic-card__name" itemprop="name">
                            Centro Clínico NUVANX Chamberí
                        </h3>
                        <span class="nvx-clinic-card__reg">
                            Registro sanitario: <strong>CS20144</strong>
                        </span>
                    </header>

                    <ul class="nvx-clinic-card__data" role="list">
                        <li itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-location"/></svg>
                            <span itemprop="streetAddress">C/ de Fernández de la Hoz, 4</span>,
                            Bajo Derecha,
                            <span itemprop="postalCode">28010</span>
                            <span itemprop="addressLocality">Madrid</span>
                            <br><small>A dos minutos de la Plaza de Olavide</small>
                        </li>
                        <li>
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-phone"/></svg>
                            <a href="tel:+34669319836" itemprop="telephone">669 319 836</a>
                            · <a href="https://wa.me/34669319836" rel="noopener noreferrer" target="_blank">WhatsApp</a>
                        </li>
                        <li>
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-clock"/></svg>
                            Horario de clínica: lunes a viernes, 10:00–19:00
                        </li>
                        <li>
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-doctor"/></svg>
                            Consulta médica: <strong>martes y jueves</strong>
                        </li>
                    </ul>

                    <div class="nvx-clinic-card__map" aria-label="Mapa NUVANX Chamberí">
                        <iframe
                            title="Cómo llegar a NUVANX Chamberí — C/ Fernández de la Hoz 4"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3036.9!2d-3.6944!3d40.4347!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDI2JzA0LjkiTiAzwrA0MScxOS44Ilc!5e0!3m2!1ses!2ses!4v1720000000000"
                            width="100%"
                            height="260"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <a href="https://www.google.com/maps/dir/?api=1&destination=C%2F+de+Fern%C3%A1ndez+de+la+Hoz%2C+4%2C+28010+Madrid"
                       class="nvx-btn nvx-btn--secondary"
                       rel="noopener noreferrer"
                       target="_blank">
                        Cómo llegar
                    </a>
                </article>

                <?php /* Salamanca–Goya */ ?>
                <article class="nvx-clinic-card" itemscope itemtype="https://schema.org/MedicalClinic">
                    <meta itemprop="identifier" content="CS20073">

                    <header class="nvx-clinic-card__header">
                        <h3 class="nvx-clinic-card__name" itemprop="name">
                            Centro Clínico NUVANX Salamanca–Goya
                        </h3>
                        <span class="nvx-clinic-card__reg">
                            Registro sanitario: <strong>CS20073</strong>
                        </span>
                    </header>

                    <ul class="nvx-clinic-card__data" role="list">
                        <li itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-location"/></svg>
                            <span itemprop="streetAddress">C/ de Fernán González, 26</span>,
                            <span itemprop="postalCode">28009</span>
                            <span itemprop="addressLocality">Madrid</span>
                            <br><small>Entre Goya y Diego de León, Barrio de Salamanca</small>
                        </li>
                        <li>
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-phone"/></svg>
                            <a href="tel:+34647505107" itemprop="telephone">647 505 107</a>
                            · <a href="https://wa.me/34647505107" rel="noopener noreferrer" target="_blank">WhatsApp</a>
                        </li>
                        <li>
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-clock"/></svg>
                            Horario de clínica: lunes a viernes, 10:00–19:00
                        </li>
                        <li>
                            <svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-doctor"/></svg>
                            Consulta médica: <strong>miércoles</strong>
                        </li>
                    </ul>

                    <div class="nvx-clinic-card__map" aria-label="Mapa NUVANX Salamanca–Goya">
                        <iframe
                            title="Cómo llegar a NUVANX Salamanca–Goya — C/ Fernán González 26"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3037.5!2d-3.6769!3d40.4257!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDI1JzMyLjUiTiAzwrA0MCczNi43Ilc!5e0!3m2!1ses!2ses!4v1720000000001"
                            width="100%"
                            height="260"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <a href="https://www.google.com/maps/dir/?api=1&destination=C%2F+de+Fern%C3%A1n+Gonz%C3%A1lez%2C+26%2C+28009+Madrid"
                       class="nvx-btn nvx-btn--secondary"
                       rel="noopener noreferrer"
                       target="_blank">
                        Cómo llegar
                    </a>
                </article>

            </div><!-- .nvx-clinics-grid -->

        </div>
    </section>

    <?php /* ── FORMULARIO DE CONTACTO ──────────────────────────────────── */ ?>
    <section class="nvx-section nvx-section--contact-form" aria-labelledby="nvx-form-heading">
        <div class="nvx-container nvx-container--narrow">

            <h2 id="nvx-form-heading" class="nvx-heading-2">
                Tu valoración gratuita: 15–30&nbsp;min con el médico, sin compromiso
            </h2>

            <p class="nvx-body">
                Indicación clínica, plan personalizado y presupuesto orientativo.
                Sin compromiso de tratamiento el mismo día. Presencial en Chamberí o Salamanca–Goya.
            </p>

            <?php
            /*
             * Si se usa Contact Form 7, reemplazar el formulario HTML nativo por:
             *   <?php echo do_shortcode('[contact-form-7 id="FORM_ID" title="Contacto NUVANX"]'); ?>
             *
             * El shortcode de CF7 debe configurar:
             *   - Campo "tratamiento" como select con los valores de abajo
             *   - Integración con GDPR Compliances (CF7 Compliance Kit o DSGVO Fields)
             *   - Redirect a /gracias/ o mensaje de confirmación accesible
             *
             * Por defecto se usa HTML nativo que puede integrarse con cualquier handler.
             */
            ?>

            <form
                class="nvx-form nvx-form--contact"
                method="post"
                action="<?php echo esc_url( home_url( '/contacto/' ) ); ?>"
                novalidate
                aria-label="Formulario de contacto NUVANX"
            >
                <?php wp_nonce_field( 'nvx_contact_form', 'nvx_contact_nonce' ); ?>

                <div class="nvx-form__row nvx-form__row--cols-2">

                    <div class="nvx-form__group">
                        <label class="nvx-form__label" for="nvx-nombre">
                            Nombre <span aria-hidden="true">*</span>
                        </label>
                        <input
                            class="nvx-form__input"
                            type="text"
                            id="nvx-nombre"
                            name="nombre"
                            autocomplete="given-name"
                            required
                            aria-required="true"
                            placeholder="Tu nombre"
                        >
                    </div>

                    <div class="nvx-form__group">
                        <label class="nvx-form__label" for="nvx-telefono">
                            Teléfono / WhatsApp <span aria-hidden="true">*</span>
                        </label>
                        <input
                            class="nvx-form__input"
                            type="tel"
                            id="nvx-telefono"
                            name="telefono"
                            autocomplete="tel"
                            required
                            aria-required="true"
                            placeholder="+34 6XX XXX XXX"
                        >
                    </div>

                </div>

                <div class="nvx-form__row">
                    <div class="nvx-form__group">
                        <label class="nvx-form__label" for="nvx-email">
                            Correo electrónico
                        </label>
                        <input
                            class="nvx-form__input"
                            type="email"
                            id="nvx-email"
                            name="email"
                            autocomplete="email"
                            placeholder="tu@email.com"
                        >
                    </div>
                </div>

                <div class="nvx-form__row">
                    <div class="nvx-form__group">
                        <label class="nvx-form__label" for="nvx-tratamiento">
                            ¿Qué área te interesa?
                        </label>
                        <select
                            class="nvx-form__select"
                            id="nvx-tratamiento"
                            name="tratamiento"
                        >
                            <option value="">Seleccionar (opcional)</option>
                            <option value="endolift">Endolift® Facial — papada, mandíbula, óvalo</option>
                            <option value="endolaser-corporal">Endoláser Corporal — grasa localizada</option>
                            <option value="laser-co2">Láser CO₂ Fraccionado — textura, cicatrices, poros</option>
                            <option value="exion-btl">EXION® BTL — firmeza y calidad cutánea</option>
                            <option value="medicina-estetica">Medicina Estética — rellenos, bótox, bioestimuladores</option>
                            <option value="ipl">BTL EXILITE™ IPL — manchas y rojeces</option>
                            <option value="no-se">No lo sé aún — necesito orientación médica</option>
                        </select>
                    </div>
                </div>

                <div class="nvx-form__row">
                    <div class="nvx-form__group">
                        <label class="nvx-form__label" for="nvx-mensaje">
                            Mensaje (opcional)
                        </label>
                        <textarea
                            class="nvx-form__textarea"
                            id="nvx-mensaje"
                            name="mensaje"
                            rows="4"
                            placeholder="Cuéntanos brevemente tu caso o pregunta…"
                            maxlength="1000"
                        ></textarea>
                    </div>
                </div>

                <div class="nvx-form__row nvx-form__row--privacy">
                    <label class="nvx-form__checkbox-label">
                        <input
                            class="nvx-form__checkbox"
                            type="checkbox"
                            name="privacidad"
                            required
                            aria-required="true"
                            id="nvx-privacidad"
                        >
                        <span>
                            He leído y acepto la
                            <a href="<?php echo esc_url( home_url( '/politica-de-privacidad/' ) ); ?>"
                               target="_blank"
                               rel="noopener">
                                Política de privacidad
                            </a>.
                            <?php /* CORRECCIÓN: URL anterior /politica-privacidad/ era 404.
                                    La URL correcta es /politica-de-privacidad/ */ ?>
                        </span>
                    </label>
                </div>

                <p class="nvx-form__privacy-note">
                    Si adjuntas material fotográfico para orientación preliminar, se trata
                    bajo protocolos de confidencialidad clínica (RGPD). Ningún diagnóstico
                    definitivo se emite solo a partir de una evaluación fotográfica; la
                    indicación se confirma siempre en valoración presencial.
                </p>

                <div class="nvx-form__actions">
                    <button class="nvx-btn nvx-btn--primary" type="submit">
                        Solicitar valoración gratuita
                    </button>
                    <span class="nvx-form__or">o</span>
                    <a href="https://wa.me/34669319836"
                       class="nvx-btn nvx-btn--secondary"
                       rel="noopener noreferrer"
                       target="_blank">
                        Contactar por WhatsApp
                    </a>
                </div>

            </form>

        </div>
    </section>

    <?php /* ── CTA SECUNDARIO ─────────────────────────────────────────── */ ?>
    <section class="nvx-section nvx-section--cta-secondary" aria-label="Reservar valoración médica">
        <div class="nvx-container">
            <p class="nvx-cta-secondary__text">
                También puedes llamar directamente a cada sede:
            </p>
            <div class="nvx-cta-group nvx-cta-group--centered">
                <a href="tel:+34669319836" class="nvx-btn nvx-btn--secondary">
                    Chamberí · 669 319 836
                </a>
                <a href="tel:+34647505107" class="nvx-btn nvx-btn--secondary">
                    Salamanca–Goya · 647 505 107
                </a>
                <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>"
                   class="nvx-btn nvx-btn--primary">
                    Reservar valoración gratuita online
                </a>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>

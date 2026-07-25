<?php
/**
 * GitHub-managed valoración landing.
 * WordPress stores only route metadata; visible copy and structure live here.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

$clinics = array(
    array( 'CHAMBERÍ · CS20144', 'NUVANX Chamberí', 'Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010 Madrid.', '669 319 836', 'tel:+34669319836', '/medicina-estetica-chamberi/' ),
    array( 'GOYA · BARRIO SALAMANCA · CS20073', 'NUVANX Goya', 'Calle de Fernán González, 26, 28009 Madrid.', '647 505 107', 'tel:+34647505107', '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' ),
);
?>
<div class="nvx-brand-page nvx-valoracion-page" id="nvx-valoracion-main" aria-labelledby="nvx-valoracion-h1">
    <header class="nvx-brand-hero nvx-editorial-hero nvx-canonical-page-hero" aria-labelledby="nvx-valoracion-h1">
        <div class="nvx-brand-hero__inner">
            <div class="nvx-editorial-hero__copy">
                <p class="nvx-eyebrow">VALORACIÓN MÉDICA · MADRID</p>
                <h1 id="nvx-valoracion-h1" class="nvx-heading">Valoración médica estética gratuita en Madrid</h1>
                <p class="nvx-brand-meta">Revisamos anatomía, calidad de piel, antecedentes y expectativas antes de indicar un tratamiento facial o corporal.</p>
                <div class="nvx-brand-actions">
                    <a class="nvx-brand-btn nvx-brand-btn--primary" href="#nvx-hubspot-form">Completar solicitud</a>
                    <a class="nvx-brand-btn nvx-brand-btn--secondary" href="https://wa.me/34669319836" target="_blank" rel="nofollow noopener">Contactar por WhatsApp</a>
                </div>
            </div>
        </div>
    </header>

    <section class="nvx-editorial-section nvx-valoracion-intro" id="nvx-valoracion-intro" aria-labelledby="nvx-valoracion-intro-title">
        <div class="nvx-editorial-section__inner">
            <p class="nvx-editorial-kicker">PRIMER PASO</p>
            <h2 id="nvx-valoracion-intro-title" class="nvx-editorial-heading">Entender tu caso antes de decidir</h2>
            <p class="nvx-editorial-body nvx-editorial-body--measure">La valoración médica permite confirmar si existe indicación, qué alternativas son proporcionadas y qué límites tiene cada opción. No empezamos por una máquina ni por un protocolo cerrado.</p>
            <ol class="nvx-editorial-timeline nvx-valoracion-steps">
                <li class="nvx-editorial-timeline__item"><span class="nvx-editorial-timeline__n">01</span><h3 class="nvx-editorial-timeline__title">Motivo y antecedentes</h3><p class="nvx-editorial-body">Revisamos lo que quieres mejorar, tratamientos previos, medicación, alergias y antecedentes relevantes.</p></li>
                <li class="nvx-editorial-timeline__item"><span class="nvx-editorial-timeline__n">02</span><h3 class="nvx-editorial-timeline__title">Exploración clínica</h3><p class="nvx-editorial-body">Valoramos calidad de piel, laxitud, grasa localizada, estructura, cicatrices y criterios de seguridad.</p></li>
                <li class="nvx-editorial-timeline__item"><span class="nvx-editorial-timeline__n">03</span><h3 class="nvx-editorial-timeline__title">Indicación y presupuesto</h3><p class="nvx-editorial-body">Si existe indicación, explicamos el plan, la evolución esperable, los cuidados, los riesgos y el presupuesto. Si no la hay, se informa con claridad.</p></li>
            </ol>
            <p class="nvx-contact-disclaimer"><em>La orientación por formulario o fotografías es preliminar. La indicación definitiva se confirma durante la valoración médica y el material enviado se trata conforme a la política de privacidad.</em></p>
        </div>
    </section>

    <section class="nvx-brand-section nvx-hubspot-form-section nvx-form-stage" id="nvx-hubspot-form" aria-labelledby="nvx-valoracion-form-title">
        <div class="nvx-brand-section__inner">
            <p class="nvx-brand-kicker">SOLICITUD DE VALORACIÓN</p>
            <h2 id="nvx-valoracion-form-title" class="nvx-brand-title">Cuéntanos qué quieres valorar</h2>
            <p class="nvx-brand-body">Completa tus datos, indica la zona o tratamiento de interés y selecciona tu sede preferida. El equipo de NUVANX te contactará para coordinar la cita.</p>
            <div class="nvx-form nvx-hs-native-section" aria-label="Formulario de valoración médica NUVANX">
                <div class="nvx-hs-native-box">
                    <div id="nvx-hubspot-native-form" class="nvx-hubspot-native-form-v2" data-nvx-hubspot-native="1" data-form-id="5042522a-0bc5-4381-ac3e-5aee8649b69c" data-portal-id="147416356" data-page-origin="valoración médica en Madrid" data-page-url="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">
                        <script src="https://js-eu1.hsforms.net/forms/embed/147416356.js" defer></script>
                        <div class="hs-form-frame" data-region="eu1" data-form-id="5042522a-0bc5-4381-ac3e-5aee8649b69c" data-portal-id="147416356"></div>
                        <p class="nvx-copy nvx-hubspot-privacy">Al facilitar tus datos aceptas la <a class="nvx-text-link" href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>">Política de privacidad</a>. La indicación definitiva se confirma siempre en valoración presencial.</p>
                    </div>
                    <p class="nvx-copy nvx-form-note">La información se utiliza para gestionar tu solicitud. La indicación y el presupuesto final dependen de la valoración médica.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="nvx-brand-section nvx-brand-section--soft" aria-labelledby="nvx-valoracion-scope">
        <div class="nvx-brand-section__inner">
            <p class="nvx-brand-kicker">QUÉ PODEMOS VALORAR</p>
            <h2 id="nvx-valoracion-scope" class="nvx-brand-title">Rostro, piel y contorno corporal</h2>
            <div class="nvx-brand-grid nvx-brand-grid--3">
                <article class="nvx-brand-card"><h3 class="nvx-brand-subtitle">Rostro y cuello</h3><p class="nvx-brand-body">Papada, cuello, línea mandibular, labios, ojeras, calidad de piel y proporción facial.</p></article>
                <article class="nvx-brand-card"><h3 class="nvx-brand-subtitle">Textura y calidad cutánea</h3><p class="nvx-brand-body">Cicatrices, poros, arrugas finas, manchas, rojeces, hidratación, firmeza y luminosidad.</p></article>
                <article class="nvx-brand-card"><h3 class="nvx-brand-subtitle">Contorno corporal</h3><p class="nvx-brand-body">Grasa localizada, laxitud y continuidad del contorno en abdomen, brazos, espalda, muslos u otras zonas.</p></article>
            </div>
            <p class="nvx-brand-body">La valoración no garantiza que exista indicación. Algunas alteraciones requieren otro enfoque o derivación.</p>
        </div>
    </section>

    <section class="nvx-brand-section" aria-labelledby="nvx-valoracion-locations">
        <div class="nvx-brand-section__inner">
            <p class="nvx-brand-kicker">SEDES AUTORIZADAS</p>
            <h2 id="nvx-valoracion-locations" class="nvx-brand-title">Valoración en Chamberí y Goya</h2>
            <div class="nvx-brand-grid nvx-brand-grid--2">
                <?php foreach ( $clinics as $clinic ) : ?>
                    <article class="nvx-brand-card"><p class="nvx-brand-kicker"><?php echo esc_html( $clinic[0] ); ?></p><h3 class="nvx-brand-subtitle"><?php echo esc_html( $clinic[1] ); ?></h3><p class="nvx-brand-body"><?php echo esc_html( $clinic[2] ); ?></p><p class="nvx-brand-body">Teléfono y WhatsApp: <a class="nvx-brand-inline-link" href="<?php echo esc_url( $clinic[4] ); ?>"><?php echo esc_html( $clinic[3] ); ?></a>.</p><a class="nvx-brand-btn nvx-brand-btn--secondary" href="<?php echo esc_url( home_url( $clinic[5] ) ); ?>">Ver sede</a></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="nvx-brand-section nvx-brand-section--soft" aria-labelledby="nvx-valoracion-faq">
        <div class="nvx-brand-section__inner">
            <p class="nvx-brand-kicker">INFORMACIÓN PRÁCTICA</p>
            <h2 id="nvx-valoracion-faq" class="nvx-brand-title">Preguntas frecuentes</h2>
            <div class="nvx-brand-faq-accordion">
                <details class="nvx-brand-faq-item"><summary><span>¿La valoración médica es gratuita?</span></summary><div class="nvx-brand-faq-content"><p>Sí. Permite revisar el caso, confirmar si existe indicación y explicar las opciones antes de decidir.</p></div></details>
                <details class="nvx-brand-faq-item"><summary><span>¿Puedo enviar fotografías?</span></summary><div class="nvx-brand-faq-content"><p>El equipo puede solicitarlas para una orientación preliminar. No sustituyen la exploración ni permiten emitir un diagnóstico definitivo por sí solas.</p></div></details>
                <details class="nvx-brand-faq-item"><summary><span>¿Cuánto dura la consulta?</span></summary><div class="nvx-brand-faq-content"><p>La duración habitual es de 15 a 30 minutos, aunque puede variar según la complejidad del caso.</p></div></details>
                <details class="nvx-brand-faq-item"><summary><span>¿Recibiré un presupuesto?</span></summary><div class="nvx-brand-faq-content"><p>Cuando existe indicación, se entrega un plan con tratamiento propuesto, número de sesiones o fases, cuidados y presupuesto.</p></div></details>
            </div>
        </div>
    </section>
</div>

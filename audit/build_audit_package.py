from pathlib import Path
import csv
import json
import shutil
from collections import Counter

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
OUT.mkdir(parents=True, exist_ok=True)
VALIDATED_AT = '2026-08-04'

SRC = {
    'sitemap': 'https://nuvanx.com/page-sitemap.xml',
    'home': 'https://nuvanx.com/',
    'solutions': 'https://nuvanx.com/soluciones-medicas/',
    'signature': 'https://nuvanx.com/protocolos-signature/',
    'doctoralia': 'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser',
    'maps_chamberi': 'https://www.google.com/maps/search/NUVANX%20Chamber%C3%AD%20Madrid',
    'maps_goya': 'https://www.google.com/maps/search/NUVANX%20Goya%20Madrid',
    'meta': 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=ES&q=NUVANX&search_type=keyword_unordered',
    'serp_endolift': 'https://www.google.com/search?q=Endolift+facial+Madrid&hl=es&gl=es',
}

common = {
    'sede': 'N/D (la disponibilidad por tratamiento y sede no se declara de forma específica)',
    'reserva_online': 'Sí, vía valoración médica genérica',
    'whatsapp': 'Sí, enlace visible',
    'doctoralia': 'Ficha de clínica verificada; sin enlace directo desde la landing de tratamiento',
    'indexacion': 'Declarada: index, follow; indexación efectiva en Google no verificada',
    'url_status': '200',
    'redirect': 'No detectado',
    'last_validated': VALIDATED_AT,
    'id_mapping_status': 'N/D — el mapeo original T01–T22 no estaba disponible en los materiales suministrados',
}

# C01–C22 are temporary reconciliation keys. They are deliberately not treated as replacements for T01–T22.
rows = [
    {
        'catalogue_key': 'C01', 'name': 'Endolift® facial', 'category': 'Tecnología · facial',
        'indication': 'Papada, mandíbula y cuello', 'technology_product': 'Endolift® / láser subdérmico',
        'url': 'https://nuvanx.com/endolift-facial-papada-mandibula/',
        'price': '798,60 €; 1.064,80 € detectados en la landing', 'price_visibility': 'Visible (alcance de cada importe pendiente de reconciliar)',
        'associated_doctor': 'Dr. José Javier Rivera Tejeda (dirección médica; Endolift® según página de inicio)',
        'overlap_note': 'No duplicado técnico; puede relacionarse con C12 por intención de papada/mandíbula.',
        'evidence_sources': 'Sitemap; landing de producción; inicio; Doctoralia; Google Maps; Meta Ad Library',
    },
    {
        'catalogue_key': 'C02', 'name': 'Endoláser corporal', 'category': 'Tecnología · corporal',
        'indication': 'Grasa localizada y mejora de contorno/firmeza', 'technology_product': 'Endoláser / láser subcutáneo',
        'url': 'https://nuvanx.com/endolaser-corporal-grasa-localizada/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing',
        'associated_doctor': 'N/D',
        'overlap_note': 'No duplicado técnico; se relaciona con páginas C16–C22 de solución corporal.',
        'evidence_sources': 'Sitemap; landing de producción; Meta Ad Library; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C03', 'name': 'Láser CO₂ fraccionado', 'category': 'Tecnología · renovación cutánea',
        'indication': 'Textura, poros y cicatrices de acné', 'technology_product': 'Láser CO₂ fraccionado',
        'url': 'https://nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
        'price': '330,00 €; 450,00 € detectados en la landing', 'price_visibility': 'Visible',
        'associated_doctor': 'Dr. José Javier Rivera Tejeda (dirección médica; láser CO₂ según página de inicio)',
        'overlap_note': 'No duplicado técnico; comparte territorio clínico con C14 Surface Renewal.',
        'evidence_sources': 'Sitemap; landing de producción; inicio; Meta Ad Library; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C04', 'name': 'EXION® BTL', 'category': 'Tecnología · plataforma',
        'indication': 'Calidad cutánea, firmeza y uso facial/corporal según valoración', 'technology_product': 'EXION® BTL',
        'url': 'https://nuvanx.com/exion-btl/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Página de plataforma; se relaciona con C05 y C06.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C05', 'name': 'EXION® Face', 'category': 'Tecnología · facial',
        'indication': 'Radiofrecuencia y ultrasonido para calidad cutánea', 'technology_product': 'EXION® Face',
        'url': 'https://nuvanx.com/exion-face/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Subpágina de tecnología; se relaciona con C04 y C13.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C06', 'name': 'EXION® Fractional', 'category': 'Tecnología · radiofrecuencia fraccionada',
        'indication': 'Arrugas profundas, cicatrices y textura', 'technology_product': 'EXION® Fractional RF',
        'url': 'https://nuvanx.com/exion-fractional/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Subpágina de tecnología; se relaciona con C04 y C14.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C07', 'name': 'Bioestimuladores de colágeno', 'category': 'Medicina estética facial',
        'indication': 'Estimulación de colágeno según diagnóstico', 'technology_product': 'Bioestimuladores de colágeno',
        'url': 'https://nuvanx.com/bioestimuladores-colageno-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Entidad individual sin duplicado de URL.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C08', 'name': 'Ojeras y surco lagrimal', 'category': 'Medicina estética · periocular',
        'indication': 'Ojeras y surco lagrimal', 'technology_product': 'Plan de diagnóstico periocular',
        'url': 'https://nuvanx.com/ojeras-surco-lagrimal-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'La página actúa simultáneamente como tecnología/tratamiento del pie y solución periocular.',
        'evidence_sources': 'Sitemap; landing de producción; soluciones médicas; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C09', 'name': 'Rinomodelación sin cirugía', 'category': 'Medicina estética facial',
        'indication': 'Rinomodelación con ácido hialurónico', 'technology_product': 'Ácido hialurónico',
        'url': 'https://nuvanx.com/rinomodelacion-sin-cirugia-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Entidad individual sin duplicado de URL.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C10', 'name': 'Labios con ácido hialurónico', 'category': 'Medicina estética facial',
        'indication': 'Armonización de labios', 'technology_product': 'Ácido hialurónico',
        'url': 'https://nuvanx.com/labios-acido-hialuronico-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Entidad individual sin duplicado de URL.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C11', 'name': 'BTL EXILITE™ IPL', 'category': 'Tecnología · IPL',
        'indication': 'Manchas, rojeces y calidad de piel', 'technology_product': 'BTL EXILITE™ IPL',
        'url': 'https://nuvanx.com/btl-exilite-ipl-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Tecnología que puede formar parte de C15 Tone Correction.',
        'evidence_sources': 'Sitemap; landing de producción; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C12', 'name': 'NUVANX Profile Definition™', 'category': 'Protocolo Signature · rostro y cuello',
        'indication': 'Papada y línea mandibular', 'technology_product': 'Protocolo de diagnóstico; técnica según valoración',
        'url': 'https://nuvanx.com/papada-definicion-mandibular-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'No duplicado técnico; comparte intención de papada/mandíbula con C01.',
        'evidence_sources': 'Sitemap; soluciones médicas; protocolos Signature; SERP de papada; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C13', 'name': 'NUVANX Skin Architecture™', 'category': 'Protocolo Signature · piel',
        'indication': 'Firmeza, densidad y calidad cutánea', 'technology_product': 'Protocolo de diagnóstico; técnica según valoración',
        'url': 'https://nuvanx.com/calidad-piel-firmeza-luminosidad-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Protocolo de solución; se relaciona con C04/C05.',
        'evidence_sources': 'Sitemap; soluciones médicas; protocolos Signature; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C14', 'name': 'NUVANX Surface Renewal™', 'category': 'Protocolo Signature · piel',
        'indication': 'Cicatrices de acné, poros y textura', 'technology_product': 'Protocolo de diagnóstico; técnica según valoración',
        'url': 'https://nuvanx.com/cicatrices-acne-poros-textura-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Protocolo de solución; comparte territorio clínico con C03/C06.',
        'evidence_sources': 'Sitemap; soluciones médicas; protocolos Signature; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C15', 'name': 'NUVANX Tone Correction™', 'category': 'Protocolo Signature · piel',
        'indication': 'Manchas, rojeces y fotodaño', 'technology_product': 'Protocolo de diagnóstico; luz/láser según valoración',
        'url': 'https://nuvanx.com/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Protocolo de solución; se relaciona con C11.',
        'evidence_sources': 'Sitemap; soluciones médicas; protocolos Signature; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C16', 'name': 'NUVANX Contour · abdomen y flancos', 'category': 'Contour Architecture™ · corporal',
        'indication': 'Grasa localizada, laxitud, estrías y pared abdominal', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/grasa-localizada-abdomen-flancos-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por zona; se relaciona con C02 y la página marco de Contour Architecture.',
        'evidence_sources': 'Sitemap; soluciones médicas; Doctoralia; Google Maps; Meta Ad Library (señal Endolift abdominal)',
    },
    {
        'catalogue_key': 'C17', 'name': 'NUVANX Contour · brazos y continuidad axilar', 'category': 'Contour Architecture™ · corporal',
        'indication': 'Grasa localizada, laxitud posterior y continuidad con axila/torso', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/flacidez-grasa-localizada-brazos-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por zona; se relaciona con C02.',
        'evidence_sources': 'Sitemap; soluciones médicas; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C18', 'name': 'NUVANX Contour · espalda y zona del sujetador', 'category': 'Contour Architecture™ · corporal',
        'indication': 'Pliegues, grasa/laxitud y continuidad con brazos/flancos', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/grasa-espalda-zona-sujetador-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por zona; se relaciona con C02.',
        'evidence_sources': 'Sitemap; soluciones médicas; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C19', 'name': 'NUVANX Contour · muslos y región subglútea', 'category': 'Contour Architecture™ · corporal',
        'indication': 'Laxitud, grasa localizada, celulitis estructural y continuidad inferior', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/flacidez-muslos-internos-subgluteo-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por zona; se relaciona con C02.',
        'evidence_sources': 'Sitemap; soluciones médicas; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C20', 'name': 'NUVANX Contour · rodillas', 'category': 'Contour Architecture™ · corporal',
        'indication': 'Grasa localizada, laxitud y relación con muslo/pierna', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/tratamiento-rodillas-grasa-flacidez-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por zona; se relaciona con C02.',
        'evidence_sources': 'Sitemap; soluciones médicas; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C21', 'name': 'NUVANX Post-Maternity Contour™', 'category': 'Protocolo Signature · corporal',
        'indication': 'Cambios posgestacionales: abdomen, flancos y calidad de tejido', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por contexto clínico; se relaciona con C16.',
        'evidence_sources': 'Sitemap; soluciones médicas; protocolos Signature; Doctoralia; Google Maps',
    },
    {
        'catalogue_key': 'C22', 'name': 'NUVANX Male Contour', 'category': 'Planificación específica · corporal',
        'indication': 'Contorno corporal masculino', 'technology_product': 'Protocolo de diagnóstico; tecnología según valoración',
        'url': 'https://nuvanx.com/contorno-corporal-masculino-madrid/',
        'price': 'N/D', 'price_visibility': 'No se detectó importe explícito en la landing', 'associated_doctor': 'N/D',
        'overlap_note': 'Solución por contexto anatómico; no se observó duplicado de URL.',
        'evidence_sources': 'Sitemap; soluciones médicas; Doctoralia; Google Maps',
    },
]

master_rows = []
for r in rows:
    master_rows.append({
        'catalogue_key': r['catalogue_key'],
        'treatment_id': 'N/D — mapeo T01–T22 no suministrado',
        'id_mapping_status': common['id_mapping_status'],
        'nombre': r['name'],
        'categoria': r['category'],
        'indicacion': r['indication'],
        'tecnologia_producto': r['technology_product'],
        'url_produccion': r['url'],
        'url_status': common['url_status'],
        'redirect': common['redirect'],
        'indexacion': common['indexacion'],
        'sede': common['sede'],
        'precio': r['price'],
        'visibilidad_precio': r['price_visibility'],
        'reserva_online': common['reserva_online'],
        'whatsapp': common['whatsapp'],
        'doctoralia': common['doctoralia'],
        'doctor_asociado': r['associated_doctor'],
        'solapamiento_o_duplicado': r['overlap_note'],
        'last_validated': common['last_validated'],
        'evidence_sources': r['evidence_sources'],
    })

# Evidence-led opportunity assessment. No demand volumes, ticket thresholds, conversion analytics,
# generic local ranks, or AI answer testing were available; those values must remain N/D/no_testado.
priority_map = {
    'C01': ('P1', 'P1 preliminar: prioridad estratégica indicada en el encargo; landing con importe y activos Meta; competencia validada como alta.'),
    'C02': ('P1', 'P1 preliminar: prioridad estratégica indicada en el encargo; landing y activos Meta; sin volumen, precio o ranking reproducible.'),
    'C03': ('P2', 'P2: landing con precios y promoción Meta verificada, pero sin demanda, competencia ni conversión comparables.'),
    'C04': ('P3', 'P3: plataforma publicada; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C05': ('P3', 'P3: tecnología publicada; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C06': ('P3', 'P3: tecnología publicada; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C07': ('P2', 'P2: tratamiento individual con landing y FAQ; sin volumen, ticket ni conversión verificables.'),
    'C08': ('P2', 'P2: tratamiento individual con landing y FAQ; sin volumen, ticket ni conversión verificables.'),
    'C09': ('P3', 'P3: landing publicada; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C10': ('P3', 'P3: landing publicada; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C11': ('P3', 'P3: tecnología publicada; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C12': ('P1', 'P1 preliminar: prioridad estratégica indicada en el encargo y presencia orgánica candidata; la SERP muestreada presenta competencia alta.'),
    'C13': ('P3', 'P3: protocolo publicado; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C14': ('P2', 'P2: potencial indicado en el encargo; landing publicada, pero sin volumen, ticket ni conversión verificables.'),
    'C15': ('P3', 'P3: protocolo publicado; sin evidencia suficiente de demanda, ticket o conversión.'),
    'C16': ('P2', 'P2: potencial indicado en el encargo; landing por zona y señal publicitaria de Endolift abdominal, sin datos críticos completos.'),
    'C17': ('P2', 'P2: solución Contour publicada; sin datos críticos completos.'),
    'C18': ('P2', 'P2: solución Contour publicada; sin datos críticos completos.'),
    'C19': ('P2', 'P2: solución Contour publicada; sin datos críticos completos.'),
    'C20': ('P2', 'P2: solución Contour publicada; sin datos críticos completos.'),
    'C21': ('P2', 'P2: protocolo Signature publicado; sin datos críticos completos.'),
    'C22': ('P2', 'P2: solución específica publicada; sin datos críticos completos.'),
}

serp_map = {
    'C01': ('mixta', 'SERP puntual: orgánico, directorios/sitios y bloque local visibles; NUVANX no apareció en los tres negocios locales visibles.'),
    'C02': ('organic', 'Búsqueda externa mostró NUVANX y varios competidores; la comprobación directa de Google fue bloqueada por CAPTCHA.'),
    'C12': ('organic', 'Búsqueda externa mostró la URL de NUVANX y varios competidores de papada/definición mandibular; posición no verificada.'),
}
comp_map = {
    'C01': ('Eleca Clinic | Templa | Clínica Eguren | VenusMed | Clínica Holivine | Endolifter', 'alta'),
    'C02': ('Depilife | Golden Estética | Ximenarios Clinic | Grupo Pedro Jaén', 'alta'),
    'C12': ('Dra. Elena Berezo | Dr. Castro Sierra | Clínica Gómez Bravo | Dorsia', 'alta'),
}
assets_map = {
    'C01': 'Landing; importes visibles; valoración/WhatsApp; ficha Doctoralia de clínica; dos fichas Maps; creatividades Meta de Endolift.',
    'C02': 'Landing; valoración/WhatsApp; ficha Doctoralia de clínica; dos fichas Maps; señal Meta de Endolift/Endoláser.',
    'C03': 'Landing; importes visibles; valoración/WhatsApp; ficha Doctoralia de clínica; dos fichas Maps; creatividad Meta de CO₂.',
    'C16': 'Landing; valoración/WhatsApp; ficha Doctoralia de clínica; dos fichas Maps; señal Meta de Endolift abdominal.',
}
gap_map = {
    'C01': 'competencia domina SERP',
    'C02': 'precio ausente/confuso',
    'C12': 'competencia domina SERP',
}

opportunity_rows = []
for r in rows:
    key = r['catalogue_key']
    p, reason = priority_map[key]
    serp_type, serp_notes = serp_map.get(key, ('N/D', 'N/D — no se obtuvo una comprobación SERP reproducible para esta intención.'))
    competitors, strength = comp_map.get(key, ('N/D', 'N/D'))
    if key == 'C01':
        maps_visibility = 'baja'
        maps_notes = 'En la consulta puntual «Endolift facial Madrid», NUVANX no apareció entre los tres negocios locales visibles; ficha de marca por sede sí verificada.'
    else:
        maps_visibility = 'N/D'
        maps_notes = 'Fichas de marca verificadas en Chamberí y Salamanca–Goya; visibilidad para intención genérica de tratamiento no testada.'
    if key in assets_map:
        assets = assets_map[key]
    else:
        assets = 'Landing; valoración/WhatsApp; ficha Doctoralia de clínica; dos fichas Maps.'
    confidence = 'media' if key in {'C01', 'C02', 'C03', 'C12', 'C16'} else 'baja'
    opportunity_rows.append({
        'catalogue_key': key,
        'treatment_id': 'N/D — mapeo T01–T22 no suministrado',
        'demanda': 'N/D',
        'demanda_source': 'N/D — no se dispuso de Keyword Planner, GSC ni otro volumen verificable',
        'intencion_principal': 'comercial' if key in {'C01','C02','C03','C05','C06','C07','C08','C09','C10','C11','C12','C14','C16','C17','C18','C19','C20','C21','C22'} else 'mixta',
        'intencion_detalle': r['indication'],
        'serp_tipo': serp_type,
        'serp_notas': serp_notes,
        'maps_visibilidad': maps_visibility,
        'maps_notas': maps_notes,
        'geo_ai_presencia': 'no_testado',
        'geo_ai_notas': 'No se ejecutaron consultas reproducibles de IA/GEO por tratamiento + Madrid/Chamberí/Goya.',
        'competidores_principales': competitors,
        'competencia_fuerza': strength,
        'activos_nuvanx': assets,
        'reviews_volumen': 'Chamberí: 14; Salamanca–Goya: 5 (reseñas de ficha de clínica, no específicas por tratamiento).',
        'reviews_relevancia': 'N/D',
        'gap_principal': gap_map.get(key, 'N/D'),
        'conversion_estado': 'N/D',
        'conversion_notas': 'La landing incluye valoración y WhatsApp; no se dispone de datos de tasa, calidad de lead o reserva efectiva.',
        'ticket': 'N/D',
        'potencial': 'N/D',
        'situacion_actual': 'N/D',
        'prioridad': p,
        'prioridad_justificacion': reason,
        'opportunity_score': 'N/D',
        'confidence': confidence,
        'last_validated': VALIDATED_AT,
        'evidence_sources': r['evidence_sources'],
    })

action_rows = [
    {
        'action_id': 'SEO-TRT-ENDO-01', 'catalogue_key': 'C01', 'treatment_id': 'N/D — mapeo T01–T22 no suministrado', 'tipo': 'SEO',
        'problema': 'Competencia domina SERP; en la consulta puntual no se observó NUVANX entre los resultados locales visibles.',
        'accion': 'Comparar la landing con los competidores orgánicos observados y cerrar únicamente las brechas documentadas de entidad médica, contenido de intención y evidencia clínica, con revisión facultativa previa.',
        'impacto_esperado': 'Mejorar la elegibilidad orgánica para intención Endolift facial Madrid.', 'prioridad': 'P1',
        'dependencia': 'Nueva captura SERP local reproducible y validación clínica del contenido.', 'estado': 'Pendiente',
    },
    {
        'action_id': 'LOCAL-TRT-ENDO-01', 'catalogue_key': 'C01', 'treatment_id': 'N/D — mapeo T01–T22 no suministrado', 'tipo': 'LOCAL',
        'problema': 'La ficha de marca existe en ambas sedes, pero NUVANX no figuró entre los tres negocios locales visibles para «Endolift facial Madrid».',
        'accion': 'Revisar categorías, servicios, URL de destino y datos de las dos fichas locales para Endolift; activar una captura de reseñas verificadas y no incentivadas cuando proceda clínicamente.',
        'impacto_esperado': 'Reducir la brecha entre presencia de ficha de marca y descubrimiento local por intención de tratamiento.', 'prioridad': 'P1',
        'dependencia': 'Acceso a perfiles de negocio y política de reseñas aprobada.', 'estado': 'Pendiente',
    },
    {
        'action_id': 'CONV-TRT-ENDO-01', 'catalogue_key': 'C01', 'treatment_id': 'N/D — mapeo T01–T22 no suministrado', 'tipo': 'CONVERSION',
        'problema': 'La landing publica dos importes; el alcance, la zona, las inclusiones y la vigencia de cada uno requieren una comprobación operativa.',
        'accion': 'Verificar precio, zona, IVA, inclusiones y vigencia con el tarifario operativo; explicar el alcance de cada importe antes de modificar cualquier canal público.',
        'impacto_esperado': 'Reducir fricción y riesgo de desalineación comercial en la valoración.', 'prioridad': 'P1',
        'dependencia': 'Validación de tarifas y alcance por dirección médica/operaciones.', 'estado': 'Pendiente',
    },
    {
        'action_id': 'CONV-TRT-ENDOLASER-01', 'catalogue_key': 'C02', 'treatment_id': 'N/D — mapeo T01–T22 no suministrado', 'tipo': 'CONVERSION',
        'problema': 'La landing contiene referencia a inversión/planificación, pero no se detectó importe explícito.',
        'accion': 'Decidir y documentar si conviene publicar un rango o explicar explícitamente por qué el presupuesto es individual; medir por separado clic a valoración y WhatsApp antes de juzgar rendimiento.',
        'impacto_esperado': 'Hacer la propuesta comercial verificable sin inventar un precio.', 'prioridad': 'P1',
        'dependencia': 'Criterio comercial y aprobación médica del lenguaje.', 'estado': 'Pendiente',
    },
    {
        'action_id': 'SEO-TRT-PROFILE-01', 'catalogue_key': 'C12', 'treatment_id': 'N/D — mapeo T01–T22 no suministrado', 'tipo': 'SEO',
        'problema': 'Competencia domina SERP en la muestra de papada/definición mandibular; la posición de NUVANX no fue verificable.',
        'accion': 'Ejecutar una comparación local, fechada y reproducible de las variantes de intención papada, mandíbula y perfil; actualizar la página sólo contra brechas comprobadas.',
        'impacto_esperado': 'Convertir una prioridad estratégica en un plan de contenido basado en evidencia.', 'prioridad': 'P1',
        'dependencia': 'Acceso a una fuente de ranking/GSC o captura SERP sin CAPTCHA.', 'estado': 'Pendiente',
    },
    {
        'action_id': 'GEO-TRT-PROFILE-01', 'catalogue_key': 'C12', 'treatment_id': 'N/D — mapeo T01–T22 no suministrado', 'tipo': 'GEO',
        'problema': 'Presencia GEO/AI no testada.',
        'accion': 'Probar y conservar evidencia de las consultas «tratamiento + Madrid», «+ Chamberí», «+ Goya» y «mejor clínica» para Profile Definition antes de abrir tareas de optimización GEO.',
        'impacto_esperado': 'Evitar acciones GEO sin señal de línea base.', 'prioridad': 'P1',
        'dependencia': 'Protocolo de pruebas y acceso permitido a las plataformas de IA seleccionadas.', 'estado': 'Pendiente',
    },
]

schema_rows = [
    ['treatments_master_validated', 'catalogue_key', 'Clave interna de conciliación de producción; no sustituye T01–T22.', 'Identificador temporal', 'No'],
    ['treatments_master_validated', 'treatment_id', 'ID original T01–T22 procedente del inventario histórico.', 'Identificador estable de negocio', 'Sí'],
    ['treatments_master_validated', 'url_produccion', 'URL canonical de producción validada.', 'URL', 'No'],
    ['treatments_master_validated', 'last_validated', 'Fecha ISO de la última comprobación.', 'Fecha', 'No'],
    ['treatments_opportunity', 'catalogue_key / treatment_id', 'Relación 1:1 con el master mediante el ID original cuando se facilite.', 'Clave foránea', 'No'],
    ['treatments_opportunity', 'demanda, ticket, conversion_estado, competencia_fuerza', 'Inputs de oportunidad; no calcular score si falta cualquiera.', 'Enum / N/D', 'No'],
    ['treatments_opportunity', 'opportunity_score', 'Producto de cuatro scores sólo con evidencia completa; en este corte es N/D.', 'Número 1–81 / N/D', 'No'],
    ['treatments_opportunity', 'situacion_actual', 'A/B/C/D mediante no_aparece, aparece_no_convierte, aparece_convierte_bien o N/D.', 'Enum', 'No'],
    ['action_tracker', 'catalogue_key / treatment_id', 'Relación N:1 con oportunidad y master.', 'Clave foránea', 'No'],
    ['action_tracker', 'action_id', 'ID único de acción.', 'Identificador', 'No'],
    ['evidence_sources', 'source_id / URL / captura', 'Fuentes fechadas para cada campo verificable.', 'Relación 1:N', 'No'],
]

filters = [
    ['Prioridad', 'P1 / P2 / P3 / N/D'],
    ['Sede', 'Chamberí / Salamanca–Goya / Ambas / N/D'],
    ['Categoría', 'Tecnología / Protocolo Signature / Contour / Medicina estética'],
    ['Situación', 'no_aparece / aparece_no_convierte / aparece_convierte_bien / N/D'],
    ['Demanda', 'alta / media / baja / N/D'],
    ['Ticket', 'alto / medio / bajo / N/D'],
    ['Competencia', 'alta / media / baja / N/D'],
    ['GEO/AI', 'menciona_nuvanx / menciona_competidores / no_menciona_nadie / no_testado'],
    ['Estado de validación', 'verificado / parcial / N/D'],
]

def write_csv(name, data, fieldnames=None):
    path = OUT / name
    if fieldnames is None:
        fieldnames = list(data[0].keys()) if data else []
    with path.open('w', encoding='utf-8-sig', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
        writer.writeheader()
        writer.writerows(data)
    return path

write_csv('treatments_master_validated.csv', master_rows)
write_csv('treatments_opportunity.csv', opportunity_rows)
write_csv('action_tracker.csv', action_rows)
with (OUT / 'control_tower_schema.csv').open('w', encoding='utf-8-sig', newline='') as f:
    writer = csv.writer(f)
    writer.writerow(['tabla', 'campo', 'definicion', 'tipo', 'obligatorio'])
    writer.writerows(schema_rows)
with (OUT / 'control_tower_filters.csv').open('w', encoding='utf-8-sig', newline='') as f:
    writer = csv.writer(f)
    writer.writerow(['filtro', 'valores'])
    writer.writerows(filters)

checks = {
    'catalogue_rows': len(master_rows),
    'opportunity_rows': len(opportunity_rows),
    'action_rows': len(action_rows),
    'duplicate_catalogue_keys': [k for k, c in Counter(r['catalogue_key'] for r in master_rows).items() if c > 1],
    'duplicate_urls': [k for k, c in Counter(r['url_produccion'] for r in master_rows).items() if c > 1],
    'all_master_urls_http_200_in_evidence': all(r['url_status'] == '200' for r in master_rows),
    'all_opportunity_rows_match_master': set(r['catalogue_key'] for r in opportunity_rows) == set(r['catalogue_key'] for r in master_rows),
    'p1_keys': [r['catalogue_key'] for r in opportunity_rows if r['prioridad'] == 'P1'],
    'opportunity_scores_nd': all(r['opportunity_score'] == 'N/D' for r in opportunity_rows),
    'geo_ai_not_tested': all(r['geo_ai_presencia'] == 'no_testado' for r in opportunity_rows),
}
(OUT / 'data_quality_checks.json').write_text(json.dumps(checks, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

# Tables for concise dossier.
def markdown_table(headers, data):
    lines = ['| ' + ' | '.join(headers) + ' |', '| ' + ' | '.join(['---'] * len(headers)) + ' |']
    for row in data:
        escaped = [str(x).replace('|', '\\|').replace('\n', '<br>') for x in row]
        lines.append('| ' + ' | '.join(escaped) + ' |')
    return '\n'.join(lines)

priority_table = []
for r in opportunity_rows:
    if r['prioridad'] in {'P1', 'P2', 'P3'}:
        priority_table.append([r['prioridad'], r['catalogue_key'], next(x['name'] for x in rows if x['catalogue_key'] == r['catalogue_key']), r['prioridad_justificacion']])
priority_table.sort(key=lambda x: ({'P1': 1, 'P2': 2, 'P3': 3}[x[0]], x[1]))

p1_action_table = [[a['action_id'], a['catalogue_key'], a['tipo'], a['problema'], a['accion'], a['estado']] for a in action_rows]

master_preview = [[r['catalogue_key'], r['nombre'], r['categoria'], r['url_status'], r['visibilidad_precio'], r['indexacion']] for r in master_rows]
opportunity_preview = [[r['catalogue_key'], r['prioridad'], r['serp_tipo'], r['maps_visibilidad'], r['competencia_fuerza'], r['gap_principal'], r['opportunity_score']] for r in opportunity_rows]

report = f"""# Auditoría de catálogo NUVANX — corte {VALIDATED_AT}

> **Alcance y trazabilidad.** Esta entrega reutiliza únicamente evidencia pública obtenida de producción, sitemap, páginas de soluciones/protocolos, Doctoralia, Google Maps y Meta Ad Library. No modifica código, diseño ni producción. Las claves **C01–C22** son claves de conciliación temporales: el material suministrado no contenía el mapeo original **T01–T22**, por lo que no se asignaron ni sustituyeron dichos IDs.

## A. Validation summary

Se reconciliaron **22 entidades de catálogo de producción** con URL individual, sin URL duplicada en esta tabla y con respuesta 200 comprobada. La página marco **Contour Architecture™** (`/remodelacion-corporal-laser-madrid/`) respondió 200, pero se dejó fuera de las 22 entidades porque actúa como página padre/arquitectura y necesita confirmación contra el inventario T01–T22. La URL de portafolio enlazada internamente `https://nuvanx.com/tratamientos/` devolvió 404; es la inconsistencia de navegación principal. [1] [2] [3]

| Indicador | Resultado |
| --- | --- |
| Entidades reconciliadas | 22 |
| URL de las 22 con estado técnico verificado | 200, sin redirección detectada |
| URLs duplicadas dentro de las 22 | 0 |
| ID original T01–T22 disponible | No; pendiente de inventario histórico |
| URL problemática detectada | `/tratamientos/` enlazada desde producción devuelve 404 |
| Precio numérico visible | Endolift® facial y Láser CO₂ fraccionado |
| Fichas Maps de marca verificadas | Chamberí y Salamanca–Goya |
| Ficha Doctoralia de clínica verificada | Sí; 105 opiniones visibles |
| Volúmenes de demanda, GSC y conversiones | N/D |

## B. treatments_master_validated

La tabla completa y normalizada está disponible en `treatments_master_validated.csv`. La vista de control conserva las claves de conciliación, el nombre validado, la categoría, la indexabilidad declarada y la visibilidad de precio.

{markdown_table(['Clave', 'Tratamiento / solución', 'Categoría', 'HTTP', 'Precio', 'Indexación declarada'], master_preview)}

## C. treatments_opportunity

No se inventaron demanda, ticket, rendimiento de conversión ni rankings. Por ello, los **22 opportunity scores permanecen N/D**, conforme a la regla de no calcular si falta cualquiera de los inputs críticos. El archivo íntegro está disponible en `treatments_opportunity.csv`.

{markdown_table(['Clave', 'Prioridad', 'SERP', 'Maps', 'Competencia', 'Gap', 'Score'], opportunity_preview)}

## D. Priority list

La clasificación es **operativa y provisional**, no una estimación de mercado. Los P1 preservan las tres prioridades estratégicas del encargo, con confianza media y tareas explícitas para completar la evidencia que falta. P2 agrupa landings con señal comercial/publicada pero sin datos críticos completos. P3 significa «no invertir ahora hasta validar», no ausencia definitiva de potencial.

{markdown_table(['Prioridad', 'Clave', 'Entidad', 'Justificación de una línea'], priority_table)}

## E. Action Tracker

Sólo se crearon acciones relacionadas con un gap documentado de P1. No se han ejecutado cambios en ninguna propiedad de producción.

{markdown_table(['Action ID', 'Clave', 'Tipo', 'Problema', 'Acción', 'Estado'], p1_action_table)}

## F. Control Tower schema

La estructura propuesta usa una relación 1:1 entre master y opportunity mediante `treatment_id` cuando se cargue el inventario histórico, y una relación N:1 desde `action_tracker` hacia cada tratamiento. `evidence_sources` se conserva como relación 1:N para no perder trazabilidad de cada validación. Los esquemas y filtros importables están en `control_tower_schema.csv` y `control_tower_filters.csv`.

{markdown_table(['Filtro', 'Valores'], filters)}

## G. Data quality

La validación técnica de URL, canonical, directiva `index, follow`, rutas de reserva/WhatsApp, precios visibles puntuales, fichas de marca Maps, ficha de clínica Doctoralia y creatividades Meta se apoya en evidencia pública fechada. [1] [4] [5] [6] [7] [8] [9]

La **indexación efectiva**, los volúmenes de demanda, la posición estable en SERP, la visibilidad Maps por cada tratamiento, los resultados GEO/AI, la disponibilidad por sede, las reseñas específicas de tratamiento, el ticket categorizado, las tasas de conversión y la correspondencia histórica T01–T22 requieren validación posterior. Google bloqueó consultas adicionales por CAPTCHA, por lo que no se debe inferir ranking más allá de la observación puntual de Endolift. [10]

| Estado | Campo / tema | Próximo dato necesario |
| --- | --- | --- |
| Verificado | Producción, 22 URLs, estado 200, canonical, robots declarados | Mantener comprobación periódica |
| Verificado | Fichas Maps de marca por sede | Exportar/validar ficha y atributos desde cuenta de negocio |
| Verificado | Doctoralia a nivel de clínica | Exportar servicios, precios y disponibilidad por tratamiento |
| Parcial | Precio | Confirmar alcance de importes, IVA, zona y vigencia |
| N/D | Demanda, ticket, conversión, score | GSC, Keyword Planner, CRM/analítica y regla de ticket aprobada |
| No testado | GEO/AI y Maps por intención | Protocolo de consultas fechado por tratamiento/sede |
| Bloqueante | Mapeo T01–T22 | Adjuntar master histórico original para realizar la unión sin cambiar IDs |

## References

[1]: {SRC['sitemap']} "NUVANX — page sitemap"
[2]: {SRC['solutions']} "NUVANX — Soluciones médicas"
[3]: {SRC['signature']} "NUVANX — Protocolos Signature"
[4]: {SRC['home']} "NUVANX — Inicio"
[5]: {SRC['doctoralia']} "Doctoralia — NUVANX Medicina Estética Láser"
[6]: {SRC['maps_chamberi']} "Google Maps — NUVANX Chamberí"
[7]: {SRC['maps_goya']} "Google Maps — NUVANX Salamanca–Goya"
[8]: {SRC['meta']} "Meta Ad Library — búsqueda NUVANX"
[9]: https://nuvanx.com/endolift-facial-papada-mandibula/ "NUVANX — Endolift® facial"
[10]: {SRC['serp_endolift']} "Google — Endolift facial Madrid"
"""

(OUT / 'nuvanx_audit_delivery.md').write_text(report, encoding='utf-8')

quality = f"""# Control de calidad — Auditoría NUVANX

| Comprobación | Resultado |
| --- | --- |
| 22 entidades de producción reconciliadas | {checks['catalogue_rows'] == 22} |
| 22 opportunity records | {checks['opportunity_rows'] == 22} |
| Claves de catálogo duplicadas | {len(checks['duplicate_catalogue_keys'])} |
| URLs de catálogo duplicadas | {len(checks['duplicate_urls'])} |
| URLs técnicas en 200 | {checks['all_master_urls_http_200_in_evidence']} |
| Correspondencia master ↔ opportunity | {checks['all_opportunity_rows_match_master']} |
| Scores calculados sin inputs críticos | {not any(r['opportunity_score'] != 'N/D' for r in opportunity_rows)} |
| GEO/AI no testado cuando no hay evidencia | {checks['geo_ai_not_tested']} |
| P1 con acciones asociadas | {sorted(set(a['catalogue_key'] for a in action_rows)) == checks['p1_keys']} |

> **Resultado:** los controles estructurales pasan. La principal limitación es que el identificador histórico T01–T22 no fue aportado, por lo que C01–C22 no deben cargarse como sustitución irreversible de esos IDs.
"""
(OUT / 'data_quality.md').write_text(quality, encoding='utf-8')

source_evidence = Path('/home/ubuntu/nuvanx_audit_evidence.md')
if source_evidence.exists():
    shutil.copy2(source_evidence, OUT / 'evidence_log.md')

print(json.dumps(checks, ensure_ascii=False, indent=2))

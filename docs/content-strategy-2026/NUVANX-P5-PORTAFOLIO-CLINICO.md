# NUVANX — PÁGINA 5: PORTAFOLIO CLÍNICO

## Estructura de 13 campos · Copy verificado

**Fuentes verificadas:** `inc/nvx-portfolio-hub.php` · `inc/nvx-treatment-hub-schema.php` · Estrategia maestra v2 (Sección 6.C)
**Validación:** RD 1907/1996 · julio 2026

---

## 01. IDENTIFICACIÓN

- **URL canónica:** https://nuvanx.com/tratamientos/
- **Módulo WordPress:** `inc/nvx-portfolio-hub.php`
- **Estado:** En producción (actualizado con enfoque anatómico y Couture Sculpt™)

---

## 02. SEO META

- **SEO Title (60 car.):** Portafolio Clínico y Medicina Estética Avanzada | NUVANX
- **Meta description (150 car.):** Descubre nuestro portafolio clínico. En NUVANX el diagnóstico precede a la tecnología. Protocolos Couture Sculpt™, Endolift® y medicina láser de precisión.
- **Canonical:** https://nuvanx.com/tratamientos/
- **OG Title:** Portafolio Clínico | Diagnóstico y Medicina Estética NUVANX

---

## 03. JERARQUÍA DE ENCABEZADOS

**H1:** Portafolio Clínico.
**H2:** La anatomía dicta el plan; la tecnología lo ejecuta
**H2:** Áreas Anatómicas y Protocolos
**H3:** Contorno y Proporción Facial
**H3:** Arquitectura Corporal y Couture Sculpt™
**H3:** Calidad de Piel, Tono y Superficie
**H2:** Nuestro Arsenal Tecnológico (Plataformas Médicas)
**H2:** Tu primera valoración clínica

---

## 04. HERO — COPY COMPLETO

KICKER: MEDICINA ESTÉTICA LÁSER

H1: Portafolio Clínico.

LEAD:
Invertimos en las plataformas de precisión más avanzadas del mercado, pero ninguna
máquina sustituye al criterio médico. Si no hay indicación clínica, no hay tratamiento.

DESCRIPTION:
Navega por nuestras áreas de actuación clínica. La selección de la tecnología,
los parámetros y el protocolo exacto se determinan única y exclusivamente
tras una exploración presencial exhaustiva en nuestras sedes de Madrid.

CTA PRIMARIO: Solicitar valoración médica

---

## 05. SECCIONES INTERNAS — COPY COMPLETO

### SECCIÓN A — LA FILOSOFÍA

H2: La anatomía dicta el plan; la tecnología lo ejecuta

TEXTO:
Tener el mejor láser del mundo no garantiza un resultado excepcional si se aplica
para el problema equivocado. En NUVANX, las plataformas no mandan; manda el médico.
Nuestro portafolio se organiza por necesidades anatómicas, asegurando que cada
intervención esté justificada, sea proporcionada y mantenga la máxima discreción.

---

### SECCIÓN B — ÁREAS ANATÓMICAS Y PROTOCOLOS

H2: Áreas Anatómicas y Protocolos

H3: Contorno y Proporción Facial
TEXTO:
Abordamos el rostro como una estructura arquitectónica. Tratamos la laxitud y
los depósitos grasos del tercio inferior mediante protocolos como **NUVANX Profile Definition™**,
redefiniendo la línea mandibular y eliminando la papada con precisión láser.
[Enlace a Papada y Mandíbula →]

H3: Arquitectura Corporal y Couture Sculpt™
TEXTO:
La remodelación corporal exige comprender la continuidad entre zonas.
Nuestro protocolo estrella **Couture Sculpt™** aborda la grasa localizada rebelde
y la flacidez en abdomen, flancos, brazos y piernas. Un sistema de diagnóstico y
tratamiento térmico intersticial que esculpe el contorno sin imponer formas estándar.
[Enlace a Remodelación Corporal (Couture Sculpt) →]

H3: Calidad de Piel, Tono y Superficie
TEXTO:
Una estructura perfecta requiere un lienzo impecable. Tratamos la matriz dérmica
para recuperar firmeza, borrar el daño solar y renovar la superficie cicatricial
mediante los protocolos **Skin Architecture™**, **Tone Correction™** y **Surface Renewal™**.
[Enlace a Calidad de Piel →]

---

### SECCIÓN C — PLATAFORMAS MÉDICAS

H2: Nuestro Arsenal Tecnológico (Plataformas Médicas)

TEXTO INTRODUCTORIO:
Las herramientas que hacen posible nuestros protocolos. Plataformas de grado médico
operadas exclusivamente por facultativos colegiados tras establecer una indicación clínica.

TARJETA: Láser Intersticial 1470nm (Endolift® / Endoláser)
Mecanismo: Tecnología subdérmica mediante microfibras ópticas para retracción tisular
y reducción de grasa focalizada, pilar fundamental de nuestro protocolo Couture Sculpt™.

TARJETA: Láser CO₂ Fraccionado
Descripción: El estándar de oro en dermatología para el *resurfacing* ablativo.
Vaporiza fracciones microscópicas de piel dañada para forzar una renovación epidérmica severa.
Ideal para cicatrices profundas, fotoenvejecimiento avanzado y discromías rebeldes.

TARJETA: BTL EXILITE™ IPL (Luz Pulsada Intensa)
Descripción: Fotorejuvenecimiento de precisión. A diferencia de equipos convencionales,
nuestra plataforma EXILITE™ permite aislar longitudes de onda específicas para
atacar la diana exacta, coagulando lesiones vasculares (rojeces, rosácea) y
fragmentando pigmentaciones solares (léntigos) con un control térmico absoluto.

TARJETA: Radiofrecuencia Fraccionada e Inducción (EXION®)
Mecanismo: Sistemas diseñados para incrementar la densidad de la matriz dérmica y
estimular la producción endógena de ácido hialurónico y colágeno sin cirugía.

---

### SECCIÓN D — CTA GLOBAL

H2: Tu primera valoración clínica
TEXTO: No vendemos bonos de máquinas. Elaboramos planes médicos personalizados
basados en evidencia anatómica.
CTA: Iniciar mi valoración

---

## 06. SCHEMA IMPLEMENTADO

El grafo canónico se amplía exclusivamente mediante el filtro `wpseo_schema_graph`
en `inc/nvx-treatment-hub-schema.php`; la plantilla no imprime JSON-LD adicional.

- **Nodo principal de catálogo:** `ItemList`
- **`ItemList.@id`:** `https://nuvanx.com/tratamientos/#treatments-list`
- **`ItemList.name`:** `Protocolos e indicaciones médicas NUVANX`
- **`ItemList.numberOfItems`:** calculado dinámicamente con el número de elementos visibles
- **`ItemList.itemListElement`:** lista ordenada de `ListItem`, cada uno enlazado con su `MedicalProcedure` o `Service`
- **`WebPage.mainEntity`:** `{ "@id": "https://nuvanx.com/tratamientos/#treatments-list" }`
- **Proveedor de cada servicio/procedimiento:** referencia al `MedicalOrganization` canónico de NUVANX

---

## 07. KEYWORDS PRIMARIAS

- portafolio clinico medicina estetica madrid
- protocolos remodelacion corporal madrid
- clinica medicina estetica laser avanzado

---

## 08. KEYWORDS LONG-TAIL

- clinica con protocolo couture sculpt madrid
- diagnostico medico para flacidez y grasa
- laser co2 fraccionado y btl exilite madrid

---

## 11. DIFERENCIACIÓN COMPETITIVA INTEGRADA

El H1 "Portafolio Clínico" y la organización por "Áreas Anatómicas" elevan a NUVANX
por encima de las clínicas que funcionan como "supermercados de máquinas".
La introducción de Couture Sculpt™ le da un nombre propio premium a la remodelación corporal.

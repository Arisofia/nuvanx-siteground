# Global governance release checklist

## Before deploy

- Branch is based on current `master`
- Target SHA is a full 40-character commit contained in `master`
- Staging2 deploy confirmation is `DEPLOY_STAGING2`

## Staging2 deployment

- Deploy one full 40-character SHA already contained in `master`
- Verify the theme deployment marker
- Purge WordPress caches
- Deployment job runs complete rendered acceptance with `EXPECTED_SHA` equal to `DEPLOY_SHA`
- Do not treat deploy as successful hasta que la verificación de SHA exacta termine en éxito
- Use `Staging2 Rendered Acceptance` only for independent revalidation with the full deployed SHA

## Rendered acceptance

- Home, Contacto, Soluciones, Valoración, medical hubs, Equipo y Clínicas devuelven 2xx
- Cada ruta renderiza exactamente un `<title>`, una descripción, un canonical y un viewport
- Cada ruta sirve el SHA de despliegue esperado
- Staging2 permanece protegido por meta y HTTP `noindex`
- Site Kit consent bootstrap sobrevive la normalización del documento cuando está presente
- No hay FacebookSignal ni marcadores de estrategia CMS sin resolver en el HTML público
- HubSpot está ausente de los scripts iniciales del HTML y solo carga tras intención del usuario

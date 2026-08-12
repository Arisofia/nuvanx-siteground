#!/usr/bin/env node
/**
 * scripts/seo/setup-ads-conversions.js
 *
 * Creates the two Google Ads conversion actions required for Fase 0:
 *   1. "Formulario de valoración enviado"  → triggered by generate_lead event
 *   2. "Clic en teléfono"                  → triggered by phone_click / whatsapp_click
 *
 * After running this script:
 *   - Copy the printed AW-XXXXXXXXXX/LABEL strings into your wp-config.php or
 *     server environment as NVX_GADS_CONVERSION_ID_FORM and NVX_GADS_CONVERSION_ID_CALL.
 *   - nvx-conversion-events.js will pick them up automatically via window.nvxConversionEvents.
 *
 * Usage:
 *   source .env.local && node scripts/seo/setup-ads-conversions.js
 *
 * Requirements:
 *   GOOGLE_ADS_CLIENT_ID, GOOGLE_ADS_CLIENT_SECRET, GOOGLE_ADS_DEVELOPER_TOKEN,
 *   GOOGLE_ADS_CUSTOMER_ID, GOOGLE_ADS_REFRESH_TOKEN
 */

'use strict';

const { GoogleAdsApi } = require('google-ads-api');

const CONVERSION_ACTIONS = [
  {
    envKey: 'NVX_GADS_CONVERSION_ID_FORM',
    name: 'Formulario de valoración enviado',
    category: 'LEAD',
    type: 'WEBPAGE',
    countingType: 'ONE_PER_CLICK',
    valueSettings: { defaultValue: 0, alwaysUseDefaultValue: true },
    // GTM trigger: dataLayer event = nvx_conversion_signal, nvx_event_name = generate_lead
    notes: 'GTM trigger: nvx_conversion_signal where nvx_event_name equals generate_lead',
  },
  {
    envKey: 'NVX_GADS_CONVERSION_ID_CALL',
    name: 'Clic en teléfono o WhatsApp',
    category: 'PHONE_CALL_LEAD',
    type: 'WEBPAGE',
    countingType: 'ONE_PER_CLICK',
    valueSettings: { defaultValue: 0, alwaysUseDefaultValue: true },
    // GTM trigger: dataLayer event = nvx_conversion_signal, nvx_event_name = phone_click OR whatsapp_click
    notes: 'GTM trigger: nvx_conversion_signal where nvx_event_name equals phone_click or whatsapp_click',
  },
];

async function main() {
  const client = new GoogleAdsApi({
    client_id: process.env.GOOGLE_ADS_CLIENT_ID,
    client_secret: process.env.GOOGLE_ADS_CLIENT_SECRET,
    developer_token: process.env.GOOGLE_ADS_DEVELOPER_TOKEN,
  });

  const customer = client.Customer({
    customer_id: (process.env.GOOGLE_ADS_CUSTOMER_ID || '').replace(/-/g, ''),
    refresh_token: process.env.GOOGLE_ADS_REFRESH_TOKEN,
  });

  // Check for existing conversion actions to avoid duplicates.
  let existing = [];
  try {
    existing = await customer.query(`
      SELECT
        conversion_action.id,
        conversion_action.name,
        conversion_action.status,
        conversion_action.tag_snippets
      FROM conversion_action
    `);
  } catch (err) {
    console.warn('Warning: could not list existing conversions:', err.message);
  }

  const existingNames = new Set(existing.map(r => r.conversion_action.name));

  for (const action of CONVERSION_ACTIONS) {
    if (existingNames.has(action.name)) {
      const existing_action = existing.find(r => r.conversion_action.name === action.name);
      const adId = existing_action?.conversion_action?.id;
      const snippet = existing_action?.conversion_action?.tag_snippets?.[0];
      const conversionId = snippet?.global_site_tag?.match(/AW-[0-9]+/)?.[0];
      const label = snippet?.event_snippet?.match(/\'send_to\': \'AW-[^/]+\/([^']+)\'/)?.[1];

      console.log(`\n✅ Ya existe: "${action.name}"`);
      if (conversionId && label) {
        const fullId = `${conversionId}/${label}`;
        console.log(`   ID de conversión: ${fullId}`);
        console.log(`   → Añade a wp-config.php:`);
        console.log(`     define('${action.envKey}', '${fullId}');`);
      } else if (adId) {
        console.log(`   Google Ads ID: ${adId} (ve a Google Ads UI para copiar el conversion tag)`);
      }
      continue;
    }

    try {
      const result = await customer.conversionActions.create([
        {
          name: action.name,
          category: action.category,
          type: action.type,
          counting_type: action.countingType,
          value_settings: {
            default_value: action.valueSettings.defaultValue,
            always_use_default_value: action.valueSettings.alwaysUseDefaultValue,
          },
          status: 'ENABLED',
        },
      ]);

      const resourceName = result?.results?.[0]?.resource_name || '';
      const newId = resourceName.split('/').pop();

      console.log(`\n✅ Creada: "${action.name}"`);
      console.log(`   Resource: ${resourceName}`);
      console.log(`   Nota: ${action.notes}`);
      console.log(`\n   ⚠️  Ve a Google Ads → Herramientas → Conversiones → "${action.name}"`);
      console.log(`      Copia el ID de conversión con formato AW-XXXXXXXXXX/YYYYYYYYYYYY`);
      console.log(`      Luego añade a wp-config.php:`);
      console.log(`        define('${action.envKey}', 'AW-XXXXXXXXXX/YYYYYYYYYYYY');`);
    } catch (err) {
      console.error(`\n❌ Error creando "${action.name}":`, err?.message || err);
    }
  }

  console.log('\n─────────────────────────────────────────────────');
  console.log('Próximos pasos:');
  console.log('1. Crea el contenedor GTM en tagmanager.google.com');
  console.log('   → Copia el ID (formato GTM-XXXXXXX)');
  console.log('   → Añade a wp-config.php: define("NVX_GTM_ID", "GTM-XXXXXXX");');
  console.log('2. En GTM crea 2 triggers:');
  console.log('   Trigger A — Custom Event: nvx_conversion_signal');
  console.log('            — Condition: nvx_event_name equals generate_lead');
  console.log('   Trigger B — Custom Event: nvx_conversion_signal');
  console.log('            — Condition: nvx_event_name equals phone_click OR whatsapp_click');
  console.log('3. En GTM crea 2 tags de Google Ads Conversion Tracking:');
  console.log('   Tag A → Conversion ID de NVX_GADS_CONVERSION_ID_FORM → Trigger A');
  console.log('   Tag B → Conversion ID de NVX_GADS_CONVERSION_ID_CALL → Trigger B');
  console.log('4. Publica el contenedor GTM');
  console.log('5. Verifica en staging2.nuvanx.com con GTM Preview Mode');
  console.log('─────────────────────────────────────────────────\n');
}

main().catch(err => {
  console.error('Fatal error:', err?.message || err);
  process.exit(1);
});

#!/usr/bin/env node

/**
 * Análisis de filtros y hooks que pueden inyectar contenido
 * Identifica plugins de WordPress y SiteGround que afectan el layout
 */

import { readFileSync, readdirSync, statSync } from 'fs';
import { join } from 'path';

const THEME_DIR =
  '/Users/MARIA/Desktop/nuvanx-siteground/wp-content/themes/nuvanx-medical';

const GREEN = '\x1b[32m';
const RED = '\x1b[31m';
const YELLOW = '\x1b[33m';
const RESET = '\x1b[0m';

function log(message, color = RESET) {
  console.log(`${color}${message}${RESET}`);
}

function success(message) {
  log('✓ ' + message, GREEN);
}

function error(message) {
  log('✗ ' + message, RED);
}

function warning(message) {
  log('⚠ ' + message, YELLOW);
}

function readPHPFile(filePath) {
  try {
    return readFileSync(filePath, 'utf8');
  } catch (e) {
    return null;
  }
}

// Buscar todas las referencias a add_filter y add_action
function analyzeHooksAndFilters() {
  log('\n=== Análisis de Hooks y Filtros ===', YELLOW);

  const phpFiles = [];

  function searchInDir(dir) {
    const entries = readdirSync(dir);
    for (const entry of entries) {
      const fullPath = join(dir, entry);
      const stat = statSync(fullPath);

      if (
        stat.isDirectory() &&
        !entry.includes('.') &&
        !entry.includes('vendor')
      ) {
        searchInDir(fullPath);
      } else if (entry.endsWith('.php') && !entry.includes('vendor')) {
        phpFiles.push(fullPath);
      }
    }
  }

  searchInDir(THEME_DIR);

  let addFilterCount = 0;
  let addActionCount = 0;
  let theContentFilterCount = 0;
  let wpHeadFilterCount = 0;
  let wpFooterFilterCount = 0;

  for (const file of phpFiles) {
    const content = readPHPFile(file);
    if (content) {
      const filters = (content.match(/add_filter\(/g) || []).length;
      const actions = (content.match(/add_action\(/g) || []).length;

      addFilterCount += filters;
      addActionCount += actions;

      if (content.includes('the_content')) {
        theContentFilterCount++;
      }
      if (content.includes('wp_head')) {
        wpHeadFilterCount++;
      }
      if (content.includes('wp_footer')) {
        wpFooterFilterCount++;
      }
    }
  }

  success(`Total add_filter encontrados: ${addFilterCount}`);
  success(`Total add_action encontrados: ${addActionCount}`);
  success(`Filtros que afectan the_content: ${theContentFilterCount}`);
  success(`Filtros que afectan wp_head: ${wpHeadFilterCount}`);
  success(`Filtros que afectan wp_footer: ${wpFooterFilterCount}`);
}

// Buscar referencias a SiteGround
function analyzeSiteGroundReferences() {
  log('\n=== Referencias a SiteGround ===', YELLOW);

  const phpFiles = [];

  function searchInDir(dir) {
    const entries = readdirSync(dir);
    for (const entry of entries) {
      const fullPath = join(dir, entry);
      const stat = statSync(fullPath);

      if (
        stat.isDirectory() &&
        !entry.includes('.') &&
        !entry.includes('vendor')
      ) {
        searchInDir(fullPath);
      } else if (entry.endsWith('.php') && !entry.includes('vendor')) {
        phpFiles.push(fullPath);
      }
    }
  }

  searchInDir(THEME_DIR);

  let siteGroundCount = 0;
  const filesWithSiteGround = [];

  for (const file of phpFiles) {
    const content = readPHPFile(file);
    if (content && content.toLowerCase().includes('siteground')) {
      siteGroundCount++;
      filesWithSiteGround.push(file.replace(THEME_DIR, ''));
    }
  }

  if (siteGroundCount > 0) {
    warning(`Encontradas ${siteGroundCount} referencias a SiteGround`);
    filesWithSiteGround.forEach((f) => {
      log(`  - ${f}`, YELLOW);
    });
  } else {
    success('No se encontraron referencias directas a SiteGround en el theme');
  }
}

// Buscar referencias a Complianz
function analyzeComplianzReferences() {
  log('\n=== Referencias a Complianz ===', YELLOW);

  const phpFiles = [];

  function searchInDir(dir) {
    const entries = readdirSync(dir);
    for (const entry of entries) {
      const fullPath = join(dir, entry);
      const stat = statSync(fullPath);

      if (
        stat.isDirectory() &&
        !entry.includes('.') &&
        !entry.includes('vendor')
      ) {
        searchInDir(fullPath);
      } else if (entry.endsWith('.php') && !entry.includes('vendor')) {
        phpFiles.push(fullPath);
      }
    }
  }

  searchInDir(THEME_DIR);

  let complianzCount = 0;
  const filesWithComplianz = [];

  for (const file of phpFiles) {
    const content = readPHPFile(file);
    if (content && content.toLowerCase().includes('complianz')) {
      complianzCount++;
      filesWithComplianz.push(file.replace(THEME_DIR, ''));
      }
    }
  }

  if (complianzCount > 0) {
    warning(`Encontradas ${complianzCount} referencias a Complianz`);
    filesWithComplianz.forEach((f) => {
      log(`  - ${f}`, YELLOW);
    });
  } else {
    success('No se encontraron referencias directas a Complianz en el theme');
  }
}

// Buscar buffer rewrites
function analyzeBufferRewrites() {
  log('\n=== Análisis de Buffer Rewrites ===', YELLOW);

  const headerPHP = readPHPFile(join(THEME_DIR, 'header.php'));
  if (headerPHP) {
    if (headerPHP.includes('ob_start') || headerPHP.includes('ob_get_clean')) {
      warning('header.php usa buffer functions (ob_start/ob_get_clean)');
    } else {
      success('header.php NO usa buffer functions');
    }

    if (
      headerPHP.includes('SiteGround') ||
      headerPHP.includes('SG Optimizer')
    ) {
      error('header.php tiene referencias a SiteGround Optimizer');
    } else {
      success('header.php NO tiene referencias a SiteGround Optimizer');
    }
  }
}

// Analizar MU plugins
function analyzeMUPlugins() {
  log('\n=== Análisis de MU Plugins ===', YELLOW);

  const muDir = '/Users/MARIA/Desktop/nuvanx-siteground/wp-content/mu-plugins';

  try {
    const stat = statSync(muDir);
    if (stat.isDirectory()) {
      const plugins = readdirSync(muDir).filter((f) => f.endsWith('.php'));

      log(`MU Plugins encontrados: ${plugins.length}`, YELLOW);
      plugins.forEach((p) => {
        log(`  - ${p}`, YELLOW);
      });

      // Analizar cada MU plugin
      for (const plugin of plugins) {
        const content = readPHPFile(join(muDir, plugin));
        if (content && (content.includes('add_filter') || content.includes('add_action'))) {
            warning(
              `${plugin} contiene hooks/filtros que pueden inyectar contenido`
            );
          }
        }
      }
    } else {
      warning('Directorio mu-plugins no existe');
    }
  } catch (e) {
    warning('No se puede acceder a mu-plugins');
  }
}

// Ejecutar análisis
function main() {
  log(
    '╔════════════════════════════════════════════════════════════════════╗',
    YELLOW
  );
  log(
    '║   Análisis de Inyección de Contenido por WordPress/SiteGround          ║',
    YELLOW
  );
  log(
    '╚════════════════════════════════════════════════════════════════════╝',
    YELLOW
  );

  analyzeHooksAndFilters();
  analyzeSiteGroundReferences();
  analyzeComplianzReferences();
  analyzeBufferRewrites();
  analyzeMUPlugins();

  log('\n=== Análisis Completado ===', GREEN);
  log(
    '\nNOTA: Este análisis solo cubre el theme. Para información completa sobre',
    YELLOW
  );
  log(
    'plugins activos de WordPress/SiteGround, revisa el panel de administración.',
    YELLOW
  );
}

main();

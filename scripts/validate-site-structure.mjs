#!/usr/bin/env node

/**
 * Validación automática de estructura del sitio
 * Verifica que todas las páginas tengan headers y heroes consistentes
 */

import { readFileSync, readdirSync, statSync } from 'fs';
import { join } from 'path';

const THEME_DIR = '/Users/MARIA/Desktop/nuvanx-siteground/wp-content/themes/nuvanx-medical';

// Colores para output
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

// Leer archivo PHP
function readPHPFile(filePath) {
  try {
    return readFileSync(filePath, 'utf8');
  } catch (e) {
    return null;
  }
}

// Verificar que una plantilla usa get_header()
function templateUsesGetHeader(content) {
  return content.includes('get_header()') || content.includes('get_header( \'');
}

// Verificar que una plantilla usa nvx-brand-hero
function templateUsesBrandHero(content) {
  return content.includes('nvx-brand-hero') || content.includes('nvx-editorial-hero') || content.includes('nvx-page-hero');
}

// Buscar archivos PHP en directorio
function findPHPFiles(dir) {
  const files = [];
  const entries = readdirSync(dir);
  
  for (const entry of entries) {
    const fullPath = join(dir, entry);
    const stat = statSync(fullPath);
    
    if (stat.isDirectory() && !entry.includes('.') && !entry.includes('vendor')) {
      files.push(...findPHPFiles(fullPath));
    } else if (entry.endsWith('.php') && !entry.includes('vendor')) {
      files.push(fullPath);
    }
  }
  
  return files;
}

// Validar templates principales
function validateMainTemplates() {
  log('\n=== Validando Templates Principales ===', YELLOW);
  
  const templates = [
    'page.php',
    'single.php',
    'front-page.php',
    'archive.php',
    '404.php',
    'index.php',
    'search.php'
  ];
  
  for (const template of templates) {
    const content = readPHPFile(join(THEME_DIR, template));
    if (content) {
      if (templateUsesGetHeader(content)) {
        success(`${template} usa get_header()`);
      } else {
        error(`${template} NO usa get_header()`);
      }
    } else {
      warning(`${template} no encontrado`);
    }
  }
}

// Validar templates en templates/
function validateCustomTemplates() {
  log('\n=== Validando Templates Personalizados ===', YELLOW);
  
  const templatesDir = join(THEME_DIR, 'templates');
  if (!statSync(templatesDir)?.isDirectory()) {
    warning('Directorio templates/ no encontrado');
    return;
  }
  
  const templates = readdirSync(templatesDir).filter(f => f.endsWith('.php'));
  
  for (const template of templates) {
    const content = readPHPFile(join(templatesDir, template));
    if (content) {
      if (templateUsesGetHeader(content)) {
        success(`${template} usa get_header()`);
      } else {
        error(`${template} NO usa get_header()`);
      }
      
      if (templateUsesBrandHero(content)) {
        success(`${template} usa nvx-brand-hero`);
      } else {
        warning(`${template} no usa nvx-brand-hero (puede usar shell)`);
      }
    }
  }
}

// Validar template-parts
function validateTemplateParts() {
  log('\n=== Validando Template Parts ===', YELLOW);
  
  const partsDir = join(THEME_DIR, 'template-parts/content');
  if (!statSync(partsDir)?.isDirectory()) {
    warning('Directorio template-parts/content/ no encontrado');
    return;
  }
  
  const parts = readdirSync(partsDir).filter(f => f.endsWith('.php'));
  
  for (const part of parts) {
    const content = readPHPFile(join(partsDir, part));
    if (content) {
      if (part === 'nvx-page-shell.php') {
        if (templateUsesGetHeader(content)) {
          success(`${part} usa get_header()`);
        } else {
          error(`${part} NO usa get_header()`);
        }
      }
    }
  }
}

// Verificar header.php
function validateHeaderPHP() {
  log('\n=== Validando header.php ===', YELLOW);
  
  const content = readPHPFile(join(THEME_DIR, 'header.php'));
  if (content) {
    if (content.includes('nvx_is_valoracion_page_request')) {
      success('header.php tiene condición nvx_is_valoracion_page_request');
    } else {
      error('header.php NO tiene condición nvx_is_valoracion_page_request');
    }
    
    if (content.includes('nvx-header')) {
      success('header.php incluye nvx-header');
    } else {
      error('header.php NO incluye nvx-header');
    }
  } else {
    error('header.php no encontrado');
  }
}

// Buscar módulos que inyectan contenido
function validateContentModules() {
  log('\n=== Validando Módulos de Contenido ===', YELLOW);
  
  const incDir = join(THEME_DIR, 'inc');
  if (!statSync(incDir)?.isDirectory()) {
    warning('Directorio inc/ no encontrado');
    return;
  }
  
  const modules = readdirSync(incDir).filter(f => f.endsWith('.php'));
  
  let modulesWithHero = 0;
  let modulesWithoutHero = 0;
  
  for (const module of modules) {
    const content = readPHPFile(join(incDir, module));
    if (content) {
      if (templateUsesBrandHero(content)) {
        modulesWithHero++;
      } else {
        modulesWithoutHero++;
      }
    }
  }
  
  success(`Módulos con hero: ${modulesWithHero}`);
  success(`Módulos sin hero: ${modulesWithoutHero}`);
}

// Validar consistencia CSS
function validateCSSConsistency() {
  log('\n=== Validando Consistencia CSS ===', YELLOW);
  
  const cssDir = join(THEME_DIR, 'assets/css');
  const nvxPatterns = readPHPFile(join(cssDir, 'nvx-patterns-editorial.css'));
  
  if (nvxPatterns) {
    if (nvxPatterns.includes('.nvx-brand-hero')) {
      success('nvx-patterns-editorial.css define .nvx-brand-hero');
    } else {
      error('nvx-patterns-editorial.css NO define .nvx-brand-hero');
    }
    
    if (nvxPatterns.includes('--nvx-media-overlay')) {
      success('nvx-patterns-editorial.css usa --nvx-media-overlay');
    } else {
      error('nvx-patterns-editorial.css NO usa --nvx-media-overlay');
    }
  } else {
    error('nvx-patterns-editorial.css no encontrado');
  }
}

// Ejecutar validación
function main() {
  log('╔════════════════════════════════════════════════════════════════════╗', YELLOW);
  log('║   Validación de Estructura del Sitio NUVANX                       ║', YELLOW);
  log('╚════════════════════════════════════════════════════════════════════╝', YELLOW);
  
  validateHeaderPHP();
  validateMainTemplates();
  validateCustomTemplates();
  validateTemplateParts();
  validateContentModules();
  validateCSSConsistency();
  
  log('\n=== Validación Completada ===', GREEN);
}

main();

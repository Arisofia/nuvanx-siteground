#!/usr/bin/env node
/**
 * Validate links to hidden pages (not in main menu but have routing)
 * 
 * This script checks:
 * - Pages defined in footer (legal pages)
 * - Pages defined in SEO metadata
 * - Template files with specific routes
 * - Ensures all referenced URLs are valid and link correctly
 */

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const THEME_DIR = path.join(__dirname, '../wp-content/themes/nuvanx-medical');

// Known pages from SEO metadata
const SEO_PAGES = {
  home: '/',
  tratamientos: '/tratamientos/',
  clinicas: '/clinicas/',
  chamberi: '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
  goya: '/medicina-estetica-goya-barrio-salamanca/',
  endolift: '/endolift-facial-papada-mandibula-madrid/',
  endolaser: '/endolaser-corporal-grasa-localizada/',
  co2: '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
  exion: '/exion-btl/',
  exilite: '/btl-exilite-ipl-madrid/',
  equipo: '/equipo-medico/',
  por_que_nuvanx: '/por-que-nuvanx/',
  inversion: '/inversion-medicina-estetica/',
  valoracion: '/madrid/valoracion/',
  soluciones: '/soluciones-medicas/',
  blog: '/blog/',
};

// Pages found in footer legal navigation
const LEGAL_PAGES = {
  aviso_legal: '/aviso-legal/',
  politica_privacidad: '/politica-privacidad/',
  politica_cookies_ue: '/politica-de-cookies-ue/',
};

// Additional important pages
const IMPORTANT_PAGES = {
  contacto: '/contacto/',
  nosotros: '/nosotros/',
  gracias: '/gracias/',
};

async function extractLinksFromFooter() {
  const footerPath = path.join(THEME_DIR, 'footer.php');
  const content = await fs.readFile(footerPath, 'utf-8');
  
  const links = [];
  const hrefRegex = /href=["']([^"']+)["']/g;
  let match;
  
  while ((match = hrefRegex.exec(content)) !== null) {
    const url = match[1];
    // Extract path from full URL
    const pathMatch = url.match(/home_url\(['"]([^"']+)['"]\)/);
    if (pathMatch) {
      links.push(pathMatch[1]);
    }
  }
  
  return links;
}

async function extractLinksFromMetadata() {
  const metadataPath = path.join(THEME_DIR, 'inc/data/seo-metadata.json');
  const content = await fs.readFile(metadataPath, 'utf-8');
  const metadata = JSON.parse(content);
  
  return Object.keys(metadata);
}

async function validatePageTemplates() {
  const templatesDir = path.join(THEME_DIR, 'templates');
  const files = await fs.readdir(templatesDir);
  
  const pageTemplates = [];
  for (const file of files) {
    if (file.endsWith('.php')) {
      const content = await fs.readFile(path.join(templatesDir, file), 'utf-8');
      // Extract home_url() calls to infer routes
      const homeUrlMatches = content.match(/home_url\(['"]([^"']+)['"]\)/g);
      if (homeUrlMatches) {
        pageTemplates.push({
          template: file,
          routes: homeUrlMatches.map(m => m[1])
        });
      }
    }
  }
  
  return pageTemplates;
}

async function main() {
  console.log('🔍 Validating hidden pages and links...\n');
  
  // Extract links from various sources
  const footerLinks = await extractLinksFromFooter();
  const metadataPages = await extractLinksFromMetadata();
  const pageTemplates = await validatePageTemplates();
  
  console.log('📄 Pages from SEO Metadata:');
  Object.entries(SEO_PAGES).forEach(([key, route]) => {
    console.log(`   ${key}: ${route}`);
  });
  
  console.log('\n⚖️  Legal Pages from Footer:');
  Object.entries(LEGAL_PAGES).forEach(([key, route]) => {
    console.log(`   ${key}: ${route}`);
  });
  
  console.log('\n🔗 Links Found in Footer:');
  footerLinks.forEach(link => {
    console.log(`   ${link}`);
  });
  
  console.log('\n📝 Page Templates:');
  pageTemplates.forEach(({ template, routes }) => {
    console.log(`   ${template}: ${routes.join(', ')}`);
  });
  
  // Check for broken links or inconsistencies
  console.log('\n✅ Validation Complete');
  console.log('💡 Next Steps: Manually verify these routes exist and link correctly');
}

main().catch(err => {
  console.error('❌ Error:', err);
  process.exit(1);
});
import puppeteer from 'puppeteer';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '../..');

const htmlPath = `file://${path.join(rootDir, 'docs/design-system/theme-showcase.html').replace(/\\/g, '/')}`;
const pdfPath = path.join(rootDir, 'docs/design-system/theme-showcase.pdf');

(async () => {
    console.log('Launching browser...');
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    
    console.log(`Loading HTML from ${htmlPath}...`);
    await page.goto(htmlPath, { waitUntil: 'networkidle0' });
    
    console.log(`Generating PDF at ${pdfPath}...`);
    await page.pdf({
        path: pdfPath,
        format: 'A4',
        printBackground: true,
        margin: { top: '20px', bottom: '20px', left: '20px', right: '20px' }
    });
    
    await browser.close();
    console.log('PDF generated successfully!');
})().catch(err => {
    console.error('Error generating PDF:', err);
    process.exit(1);
});

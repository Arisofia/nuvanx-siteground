import requests
from bs4 import BeautifulSoup
import csv
import json
from pathlib import Path
import re
import concurrent.futures
import urllib3
from contextlib import suppress
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
OUT.mkdir(parents=True, exist_ok=True)

with open(OUT / 'staging_routes.json', 'r') as f:
    SLUGS = json.load(f)

PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Cache-Control': 'no-cache'
}

def _parse_html_content(text, soup):
    """Extract and parse HTML content fields."""
    res = {
        'canonical': can['href'] if (can := soup.find('link', rel='canonical')) else 'N/D',
        'robots': rob['content'] if (rob := soup.find('meta', attrs={'name': 'robots'})) else 'N/D'
    }
    
    # H1
    h1 = soup.find('h1')
    res['h1'] = h1.get_text(strip=True) if h1 else 'N/D'
    
    # Price (heuristic)
    prices = re.findall(r'\d+(?:[.,]\d+)?\s*€', text)
    res['price'] = '; '.join(sorted(list(set(prices)))) if prices else 'N/D'
    
    # FAQ Schema
    res['faq'] = 'Sí' if 'FAQPage' in text else 'No'
    
    # Doctor (heuristic)
    res['doctor'] = 'Dr. José Javier Rivera Tejeda' if 'Rivera' in text else 'N/D'
    
    # Schema Type
    scripts = soup.find_all('script', type='application/ld+json')
    types = []
    for s in scripts:
        with suppress(Exception):
            data = json.loads(s.string)
            if isinstance(data, dict):
                if '@type' in data: 
                    t = data['@type']
                    if isinstance(t, list): types.extend(t)
                    else: types.append(t)
                if '@graph' in data:
                    for item in data['@graph']:
                        if '@type' in item: 
                            t = item['@type']
                            if isinstance(t, list): types.extend(t)
                            else: types.append(t)
    res['schema_type'] = '; '.join(sorted(list(set(types)))) if types else 'N/D'
    
    return res

def audit_url(base_url, slug):
    url = f"{base_url.rstrip('/')}/{slug.lstrip('/')}"
    res = {
        'status': 'N/D', 
        'canonical': 'N/D', 
        'robots': 'N/D', 
        'h1': 'N/D', 
        'price': 'N/D', 
        'faq': 'No', 
        'doctor': 'N/D', 
        'schema_type': 'N/D'
    }
    
    try:
        response = requests.get(url, headers=HEADERS, timeout=10, allow_redirects=True, verify=False)
        res['status'] = str(response.status_code)
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')
            res.update(_parse_html_content(response.text, soup))
            
    except Exception as e:
        res['status'] = "Error"
    
    return res

def get_gap_tipo(prod_val, stag_val):
    if prod_val == 'N/D':
        return 'contenido_falta_ambos' if stag_val == 'N/D' else 'drift_falta_produccion'
    if stag_val == 'N/D':
        return 'drift_falta_staging'
    if prod_val == stag_val:
        return 'coincide'
    return 'diferente'

def get_page_existence_gap(prod_status, stag_status):
    if prod_status == '404' and stag_status == '404': return 'contenido_falta_ambos'
    if prod_status == '404' and stag_status == '200': return 'drift_falta_produccion'
    if prod_status == '200' and stag_status == '404': return 'drift_falta_staging'
    return 'coincide'

results = []
print(f"Iniciando auditoría comparativa de {len(SLUGS)} URLs...")

def process_slug(slug):
    print(f"Auditando: /{slug}")
    prod = audit_url(PROD_BASE, slug)
    stag = audit_url(STAG_BASE, slug)
    
    return {
        'slug': slug,
        'gap_tipo_existencia': get_page_existence_gap(prod['status'], stag['status']),
        'prod_status': prod['status'],
        'stag_status': stag['status'],
        'gap_tipo_h1': get_gap_tipo(prod['h1'], stag['h1']),
        'prod_h1': prod['h1'],
        'stag_h1': stag['h1'],
        'gap_tipo_price': get_gap_tipo(prod['price'], stag['price']),
        'prod_price': prod['price'],
        'stag_price': stag['price'],
        'gap_tipo_faq': get_gap_tipo(prod['faq'], stag['faq']),
        'prod_faq': prod['faq'],
        'stag_faq': stag['faq'],
        'prod_canonical': prod['canonical'],
        'stag_canonical': stag['canonical'],
        'prod_robots': prod['robots'],
        'stag_robots': stag['robots'],
        'prod_doctor': prod['doctor'],
        'stag_doctor': stag['doctor'],
        'prod_schema': prod['schema_type'],
        'stag_schema': stag['schema_type']
    }

with concurrent.futures.ThreadPoolExecutor(max_workers=5) as executor:
    results = list(executor.map(process_slug, SLUGS))

# Guardar CSV
fieldnames = results[0].keys()
with (OUT / 'nuvanx_drift_full_comparison.csv').open('w', encoding='utf-8-sig', newline='') as f:
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(results)

print("Auditoría completada exitosamente.")

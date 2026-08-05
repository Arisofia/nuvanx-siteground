import requests
from bs4 import BeautifulSoup
import csv
import json
from pathlib import Path
import re

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
OUT.mkdir(parents=True, exist_ok=True)

ENTITIES = [
    ('C01', 'endolift-facial-papada-mandibula/'),
    ('C02', 'endolaser-corporal-grasa-localizada/'),
    ('C03', 'laser-co2-fraccionado-madrid-textura-cicatrices-poro/'),
    ('C04', 'exion-btl/'),
    ('C05', 'exion-face/'),
    ('C06', 'exion-fractional/'),
    ('C07', 'bioestimuladores-colageno-madrid/'),
    ('C08', 'ojeras-surco-lagrimal-madrid/'),
    ('C09', 'rinomodelacion-sin-cirugia-madrid/'),
    ('C10', 'labios-acido-hialuronico-madrid/'),
    ('C11', 'btl-exilite-ipl-madrid/'),
    ('C12', 'papada-definicion-mandibular-madrid/'),
    ('C13', 'calidad-piel-firmeza-luminosidad-madrid/'),
    ('C14', 'cicatrices-acne-poros-textura-madrid/'),
    ('C15', 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid/'),
    ('C16', 'grasa-localizada-abdomen-flancos-madrid/'),
    ('C17', 'flacidez-grasa-localizada-brazos-madrid/'),
    ('C18', 'grasa-espalda-zona-sujetador-madrid/'),
    ('C19', 'flacidez-muslos-internos-subgluteo-madrid/'),
    ('C20', 'tratamiento-rodillas-grasa-flacidez-madrid/'),
    ('C21', 'tratamiento-postparto-abdomen-contorno-corporal-madrid/'),
    ('C22', 'contorno-corporal-masculino-madrid/'),
]

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Cache-Control': 'no-cache'
}

def audit_url(base_url, slug):
    url = f"{base_url.rstrip('/')}/{slug}"
    res = {'url': url, 'status': 'N/D', 'canonical': 'N/D', 'robots': 'N/D', 'h1': 'N/D', 'price': 'N/D', 'faq': 'No', 'doctor': 'N/D', 'schema_type': 'N/D'}
    
    try:
        response = requests.get(url, headers=HEADERS, timeout=15, allow_redirects=True)
        res['status'] = str(response.status_code)
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Canonical
            can = soup.find('link', rel='canonical')
            res['canonical'] = can['href'] if can else 'N/D'
            
            # Robots
            rob = soup.find('meta', attrs={'name': 'robots'})
            res['robots'] = rob['content'] if rob else 'N/D'
            
            # H1
            h1 = soup.find('h1')
            res['h1'] = h1.get_text(strip=True) if h1 else 'N/D'
            
            # Price (heuristic)
            text = response.text
            prices = re.findall(r'\d+(?:[.,]\d+)?\s*€', text)
            res['price'] = '; '.join(list(set(prices))) if prices else 'N/D'
            
            # FAQ Schema
            res['faq'] = 'Sí' if 'FAQPage' in text else 'No'
            
            # Doctor (heuristic)
            if 'Rivera' in text:
                res['doctor'] = 'Dr. José Javier Rivera Tejeda'
            
            # Schema Type
            scripts = soup.find_all('script', type='application/ld+json')
            types = []
            for s in scripts:
                try:
                    data = json.loads(s.string)
                    if isinstance(data, dict):
                        if '@type' in data: types.append(data['@type'])
                        if '@graph' in data:
                            for item in data['@graph']:
                                if '@type' in item: types.append(item['@type'])
                except: continue
            res['schema_type'] = '; '.join(list(set(types))) if types else 'N/D'
            
    except Exception as e:
        res['status'] = f"Error: {str(e)}"
    
    return res

def get_gap_tipo(prod_val, stag_val):
    if prod_val == 'N/D' and stag_val == 'N/D': return 'contenido_falta_ambos'
    if prod_val == 'N/D' and stag_val != 'N/D': return 'drift_falta_produccion'
    if prod_val != 'N/D' and stag_val == 'N/D': return 'drift_falta_staging'
    if prod_val == stag_val: return 'coincide'
    return 'diferente'

# Cargar datos previos de producción si existen
PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"

results = []
print(f"Iniciando auditoría comparativa para {len(ENTITIES)} entidades...")

for key, slug in ENTITIES:
    print(f"Procesando {key}...")
    prod = audit_url(PROD_BASE, slug)
    stag = audit_url(STAG_BASE, slug)
    
    diff = {
        'catalogue_key': key,
        'slug': slug,
        'prod_status': prod['status'],
        'stag_status': stag['status'],
        'prod_h1': prod['h1'],
        'stag_h1': stag['h1'],
        'prod_price': prod['price'],
        'stag_price': stag['price'],
        'prod_faq': prod['faq'],
        'stag_faq': stag['faq'],
        'gap_tipo_h1': get_gap_tipo(prod['h1'], stag['h1']),
        'gap_tipo_price': get_gap_tipo(prod['price'], stag['price']),
        'gap_tipo_faq': get_gap_tipo(prod['faq'], stag['faq']),
        'drift_status': 'coincide' if prod['status'] == stag['status'] else 'drift'
    }
    results.append(diff)

# Escribir resultados
fieldnames = results[0].keys()
with (OUT / 'nuvanx_drift_audit.csv').open('w', encoding='utf-8-sig', newline='') as f:
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(results)

print("Auditoría completada. Archivo generado: nuvanx_drift_audit.csv")

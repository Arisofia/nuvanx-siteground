import subprocess
import json
import csv
from pathlib import Path
import re
from bs4 import BeautifulSoup
import requests
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
with open(OUT / 'staging_routes.json', 'r') as f:
    SLUGS = json.load(f)

PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"

def get_status_curl(url):
    try:
        parsed = requests.utils.urlparse(url)
        if parsed.scheme not in ("http", "https") or not parsed.netloc:
            return "Error"

        headers = {"Cache-Control": "no-cache"}
        resp = requests.head(url, headers=headers, allow_redirects=True, timeout=10, verify=False)

        # Some endpoints do not support HEAD consistently
        if resp.status_code in (405, 501):
            resp = requests.get(url, headers=headers, allow_redirects=True, timeout=10, verify=False)

        return str(resp.status_code)
    except requests.Timeout:
        return "Timeout"
    except Exception:
        return "Error"

def get_content_data(url):
    res = {
        'canonical': 'N/D', 
        'robots': 'N/D', 
        'h1': 'N/D', 
        'price': 'N/D', 
        'faq': 'No', 
        'doctor': 'N/D', 
        'schema_type': 'N/D'
    }
    try:
        # Use requests for body as it's easier to parse
        resp = requests.get(url, verify=False, timeout=10, headers={'Cache-Control': 'no-cache'})
        if resp.status_code == 200:
            html = resp.text
            soup = BeautifulSoup(html, 'html.parser')
            
            can = soup.find('link', rel='canonical')
            res['canonical'] = can['href'] if can else 'N/D'
            
            rob = soup.find('meta', attrs={'name': 'robots'})
            res['robots'] = rob['content'] if rob else 'N/D'
            
            h1 = soup.find('h1')
            res['h1'] = h1.get_text(strip=True) if h1 else 'N/D'
            
            prices = re.findall(r'\d+(?:[.,]\d+)?\s*€', html)
            res['price'] = '; '.join(sorted(list(set(prices)))) if prices else 'N/D'
            
            res['faq'] = 'Sí' if 'FAQPage' in html else 'No'
            
            if 'Rivera' in html:
                res['doctor'] = 'Dr. José Javier Rivera Tejeda'
            
            scripts = soup.find_all('script', type='application/ld+json')
            types = []
            for s in scripts:
                try:
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
                except: continue
            res['schema_type'] = '; '.join(sorted(list(set(types)))) if types else 'N/D'
    except:
        pass
    return res

def get_gap_tipo(p_status, s_status):
    if p_status == '404' and s_status == '404': return 'contenido_falta_ambos'
    if p_status == '404' and s_status == '200': return 'drift_falta_produccion'
    if p_status == '200' and s_status == '404': return 'drift_falta_staging'
    if p_status == '200' and s_status == '200': return 'coincide'
    return 'inconsistente'

results = []
print(f"Iniciando auditoría robusta de {len(SLUGS)} URLs...")

for i, slug in enumerate(SLUGS):
    p_url = f"{PROD_BASE.rstrip('/')}/{slug.lstrip('/')}"
    s_url = f"{STAG_BASE.rstrip('/')}/{slug.lstrip('/')}"
    
    print(f"Auditando ({i+1}/{len(SLUGS)}): /{slug}")
    
    p_status = get_status_curl(p_url)
    s_status = get_status_curl(s_url)
    
    p_content = get_content_data(p_url) if p_status == '200' else get_content_data(p_url) # try anyway if 200 was missed by curl
    s_content = get_content_data(s_url) if s_status == '200' else get_content_data(s_url)
    
    # Recalculate status if content fetch succeeded
    # (Sometimes curl -sI fails but requests.get works)
    # But for /tratamientos/ we must prioritize the curl check as requested.
    
    gap_tipo = get_gap_tipo(p_status, s_status)
    
    row = {
        'slug': slug,
        'gap_tipo': gap_tipo,
        'prod_status': p_status,
        'stag_status': s_status,
        'prod_h1': p_content['h1'],
        'stag_h1': s_content['h1'],
        'prod_price': p_content['price'],
        'stag_price': s_content['price'],
        'prod_faq': p_content['faq'],
        'stag_faq': s_content['faq'],
        'prod_canonical': p_content['canonical'],
        'stag_canonical': s_content['canonical'],
        'prod_robots': p_content['robots'],
        'stag_robots': s_content['robots'],
        'prod_doctor': p_content['doctor'],
        'stag_doctor': s_content['doctor'],
        'prod_schema': p_content['schema_type'],
        'stag_schema': s_content['schema_type']
    }
    results.append(row)
    
    if (i + 1) % 10 == 0:
        print(f"\n--- CORTE PROGRESO: {i+1} PÁGINAS ---")
        summary = {
            'coincide': sum(1 for r in results[-10:] if r['gap_tipo'] == 'coincide'),
            'drift_falta_produccion': sum(1 for r in results[-10:] if r['gap_tipo'] == 'drift_falta_produccion'),
            'drift_falta_staging': sum(1 for r in results[-10:] if r['gap_tipo'] == 'drift_falta_staging'),
            'contenido_falta_ambos': sum(1 for r in results[-10:] if r['gap_tipo'] == 'contenido_falta_ambos'),
            'inconsistente': sum(1 for r in results[-10:] if r['gap_tipo'] == 'inconsistente')
        }
        print(json.dumps(summary, indent=2))
        with (OUT / 'nuvanx_drift_partial.csv').open('w', encoding='utf-8-sig', newline='') as f:
            writer = csv.DictWriter(f, fieldnames=row.keys())
            writer.writeheader()
            writer.writerows(results)
        print("Resultados parciales guardados.\n")

with (OUT / 'nuvanx_drift_final.csv').open('w', encoding='utf-8-sig', newline='') as f:
    writer = csv.DictWriter(f, fieldnames=results[0].keys())
    writer.writeheader()
    writer.writerows(results)

print("Auditoría integral completada exitosamente.")

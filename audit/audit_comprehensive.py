import subprocess
import json
import csv
from pathlib import Path
import re
from bs4 import BeautifulSoup
import requests
import urllib3
import ssl
from urllib3.exceptions import InsecureRequestWarning, SSLError

# Only disable warnings for specific cases, not globally
# urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
with open(OUT / 'staging_routes.json', 'r') as f:
    SLUGS = json.load(f)

PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"

def get_http_status_curl(url):
    """
    Get HTTP status using curl with SSL verification bypass for internal audit purposes.
    Note: -k flag bypasses SSL verification - this is intentional for internal audit scripts
    where SSL certificates may be self-signed or in development environments.
    """
    try:
        # Validate URL to prevent command injection
        parsed = requests.utils.urlparse(url)
        if parsed.scheme not in ("http", "https") or not parsed.netloc:
            return "Error"

        # User requested: curl -sI -H "Cache-Control: no-cache"
        # Using curl with -k flag for SSL verification bypass for internal audit
        cmd = ['curl', '-sI', '-L', '-k', '-m', '10', '-H', 'Cache-Control: no-cache', url]
        proc = subprocess.run(cmd, capture_output=True, text=True, timeout=12)
        if proc.returncode == 0:
            lines = proc.stdout.replace('\r', '').splitlines()
            if status_lines := [l for l in lines if l.startswith("HTTP/")]:
                return status_lines[-1].split()[1]
        return "Error"
    except:
        return "Timeout"

def get_content_data(url):
    """
    Get content data from URL with proper SSL verification.
    Falls back to no verification only if SSL fails, with warning.
    """
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
        # Try with SSL verification first (security best practice)
        try:
            resp = requests.get(url, verify=True, timeout=10, headers={'Cache-Control': 'no-cache'})
        except SSLError as ssl_error:
            # Fall back to no verification only for internal audit if SSL fails
            # This is a controlled fallback for development/staging environments
            print(f"Warning: SSL verification failed for {url}, falling back to no verification: {ssl_error}")
            resp = requests.get(url, verify=False, timeout=10, headers={'Cache-Control': 'no-cache'})

        if resp.status_code == 200:
            html = resp.text

            # Limit HTML size to prevent ReDoS on large pages
            if len(html) > 1000000:  # 1MB limit
                html = html[:1000000]

            soup = BeautifulSoup(html, 'html.parser')

            can = soup.find('link', rel='canonical')
            res['canonical'] = can['href'] if can else 'N/D'

            rob = soup.find('meta', attrs={'name': 'robots'})
            res['robots'] = rob['content'] if rob else 'N/D'

            h1 = soup.find('h1')
            res['h1'] = h1.get_text(strip=True) if h1 else 'N/D'

            # Use regex with controlled input size
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
    except Exception as e:
        print(f"Error fetching content from {url}: {e}")
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

    p_status = get_http_status_curl(p_url)
    s_status = get_http_status_curl(s_url)

    # Always fetch content regardless of status
    # This helps catch cases where curl status check might be incorrect
    p_content = get_content_data(p_url)
    s_content = get_content_data(s_url)
    
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

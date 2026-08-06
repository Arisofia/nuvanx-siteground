"""
Comprehensive Audit Script for NUVANX Production & Staging Route Synchronization

Environment Variables:
  AUDIT_VERIFY_SSL (default: "true")
    Set to "false" (e.g., AUDIT_VERIFY_SSL=false python3 audit/audit_comprehensive.py)
    to bypass SSL certificate verification if auditing staging environments with
    self-signed or untrusted TLS certificates.
  AUDIT_TRUST_ENV (default: "false")
    Set to "true" to enable reading system proxies (HTTP_PROXY, HTTPS_PROXY) and .netrc credentials.
"""
import json
import csv
from pathlib import Path
import re
from bs4 import BeautifulSoup
import requests
import os

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
with open(OUT / 'staging_routes.json', 'r') as f:
    SLUGS = json.load(f)

PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"
ALLOWED_AUDIT_HOSTS = {"nuvanx.com", "staging2.nuvanx.com"}
VERIFY_SSL = os.environ.get("AUDIT_VERIFY_SSL", "true").lower() == "true"
if not VERIFY_SSL:
    print("WARNING: AUDIT_VERIFY_SSL=false is active. TLS certificate verification is disabled for this audit run.")

SESSION = requests.Session()
# Disable implicit authentication from .netrc and unvetted proxies by default for security
SESSION.trust_env = os.environ.get("AUDIT_TRUST_ENV", "false").lower() == "true"

def is_safe_audit_url(url):
    parsed = requests.utils.urlparse(url)
    if parsed.scheme not in ("http", "https"):
        return False
    if not parsed.netloc or not parsed.hostname:
        return False
    if parsed.username or parsed.password:
        return False
    host = parsed.hostname.lower()
    if host not in ALLOWED_AUDIT_HOSTS:
        return False
    return True

TYPE_KEY = '@type'
GRAPH_KEY = '@graph'

def _extract_types_from_obj(data):
    types = []
    if not isinstance(data, dict):
        return types
    if TYPE_KEY in data:
        t = data[TYPE_KEY]
        types.extend(t if isinstance(t, list) else [t])
    if GRAPH_KEY in data and isinstance(data[GRAPH_KEY], list):
        for item in data[GRAPH_KEY]:
            if isinstance(item, dict) and TYPE_KEY in item:
                t = item[TYPE_KEY]
                types.extend(t if isinstance(t, list) else [t])
    return types

def _parse_schema_types(soup):
    scripts = soup.find_all('script', type='application/ld+json')
    types = []
    for s in scripts:
        if not s.string:
            continue
        try:
            data = json.loads(s.string)
            types.extend(_extract_types_from_obj(data))
        except Exception:
            continue
    return '; '.join(sorted(set(types))) if types else 'N/D'

def _follow_redirects_safely(session, url, method="get", stream=False):
    session = session or SESSION
    fetch = session.get if method == "get" else session.head
    kwargs = {"verify": VERIFY_SSL, "timeout": 10, "headers": {'Cache-Control': 'no-cache'}, "allow_redirects": False}
    if stream:
        kwargs["stream"] = True

    resp = fetch(url, **kwargs)
    max_redirects = 5
    redirect_count = 0
    current_url = url

    while resp.is_redirect and redirect_count < max_redirects:
        redirect_count += 1
        location = resp.headers['Location']

        loc_parsed = requests.utils.urlparse(location)
        if not loc_parsed.scheme or not loc_parsed.netloc:
            location = requests.utils.urljoin(current_url, location)

        if not is_safe_audit_url(location):
            if stream:
                resp.close()
            return None

        if stream:
            resp.close()

        current_url = location
        resp = fetch(current_url, **kwargs)

    return resp

def _parse_html_fields(html):
    if len(html) > 1000000:
        html = html[:1000000]
    soup = BeautifulSoup(html, 'html.parser')
    can = soup.find('link', rel='canonical')
    rob = soup.find('meta', attrs={'name': 'robots'})
    h1 = soup.find('h1')
    prices = re.findall(r'\b\d{1,7}(?:[.,]\d{1,3}){0,3}\s*€', html)

    return {
        'canonical': can['href'] if can else 'N/D',
        'robots': rob['content'] if rob else 'N/D',
        'h1': h1.get_text(strip=True) if h1 else 'N/D',
        'price': '; '.join(sorted(set(prices))) if prices else 'N/D',
        'faq': 'Sí' if 'FAQPage' in html else 'No',
        'doctor': 'Dr. José Javier Rivera Tejeda' if 'Rivera' in html else 'N/D',
        'schema_type': _parse_schema_types(soup)
    }

def fetch_url_audit_data(url, session=None):
    """
    Fetches status and content data in a single HTTP pass per URL.
    Returns (status_code_str, content_dict).
    """
    session = session or SESSION
    default_content = {
        'canonical': 'N/D',
        'robots': 'N/D',
        'h1': 'N/D',
        'price': 'N/D',
        'faq': 'No',
        'doctor': 'N/D',
        'schema_type': 'N/D'
    }
    if not is_safe_audit_url(url):
        return "Error", dict.fromkeys(default_content, 'Error')

    try:
        resp = _follow_redirects_safely(session, url, method="get", stream=True)
        if not resp:
            return "Error", dict.fromkeys(default_content, 'Error')

        try:
            status = str(resp.status_code)
            if resp.status_code == 200:
                content = _parse_html_fields(resp.text)
            else:
                content = dict(default_content)
        finally:
            resp.close()

        return status, content
    except requests.exceptions.Timeout:
        return "Timeout", dict.fromkeys(default_content, 'Error')
    except Exception as e:
        print(f"Error fetching {url}: {e}")
        return "Error", dict.fromkeys(default_content, 'Error')

def get_http_status(url, session=None):
    """
    Get HTTP status using a lightweight HEAD request with SSL verification.
    """
    session = session or SESSION
    if not is_safe_audit_url(url):
        return "Error"
    try:
        resp = _follow_redirects_safely(session, url, method="head", stream=False)
        if not resp:
            return "Error"
        try:
            return str(resp.status_code)
        finally:
            resp.close()
    except requests.exceptions.Timeout:
        return "Timeout"
    except Exception:
        return "Error"

def get_content_data(url, session=None):
    session = session or SESSION
    _, content = fetch_url_audit_data(url, session=session)
    return content

def get_gap_tipo(p_status, s_status):
    if p_status == '404' and s_status == '404': return 'contenido_falta_ambos'
    if p_status == '404' and s_status == '200': return 'drift_falta_produccion'
    if p_status == '200' and s_status == '404': return 'drift_falta_staging'
    if p_status == '200' and s_status == '200': return 'coincide'
    return 'inconsistente'

def run_audit():
    results = []
    print(f"Iniciando auditoría robusta de {len(SLUGS)} URLs...")

    for i, slug in enumerate(SLUGS):
        p_url = f"{PROD_BASE.rstrip('/')}/{slug.lstrip('/')}"
        s_url = f"{STAG_BASE.rstrip('/')}/{slug.lstrip('/')}"
        
        print(f"Auditando ({i+1}/{len(SLUGS)}): /{slug}")

        p_status, p_content = fetch_url_audit_data(p_url)
        s_status, s_content = fetch_url_audit_data(s_url)
        
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
            last_10 = results[-10:]
            summary = {
                'coincide': sum(1 for r in last_10 if r['gap_tipo'] == 'coincide'),
                'drift_falta_produccion': sum(1 for r in last_10 if r['gap_tipo'] == 'drift_falta_produccion'),
                'drift_falta_staging': sum(1 for r in last_10 if r['gap_tipo'] == 'drift_falta_staging'),
                'contenido_falta_ambos': sum(1 for r in last_10 if r['gap_tipo'] == 'contenido_falta_ambos'),
                'inconsistente': sum(1 for r in last_10 if r['gap_tipo'] == 'inconsistente')
            }
            print(json.dumps(summary, indent=2))
            with (OUT / 'nuvanx_drift_partial.csv').open('w', encoding='utf-8-sig', newline='') as f:
                writer = csv.DictWriter(f, fieldnames=row.keys())
                writer.writeheader()
                writer.writerows(results)
            print("Resultados parciales guardados.\n")

    if results:
        with (OUT / 'nuvanx_drift_final.csv').open('w', encoding='utf-8-sig', newline='') as f:
            writer = csv.DictWriter(f, fieldnames=results[0].keys())
            writer.writeheader()
            writer.writerows(results)
        print("Auditoría integral completada exitosamente.")
    else:
        print("No se procesaron rutas (la lista de rutas estaba vacía). Auditoría finalizada sin crear CSV.")

if __name__ == '__main__':
    run_audit()

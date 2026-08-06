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

SESSION = requests.Session()
SESSION.trust_env = False  # Disable implicit authentication from .netrc globally for audit

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

def get_http_status(url, session=SESSION):
    """
    Get HTTP status using requests with SSL verification for internal audit purposes.
    """
    try:
        # Strict validation to prevent unsafe URL usage
        if not is_safe_audit_url(url):
            return "Error"

        resp = session.head(
            url,
            headers={"Cache-Control": "no-cache"},
            allow_redirects=False,  # Disable automatic redirects for SSRF protection
            timeout=10,
            verify=VERIFY_SSL,
        )
        
        # Follow redirects manually with SSRF protection
        max_redirects = 5
        redirect_count = 0
        current_url = url
        current_status = str(resp.status_code)
        
        while resp.is_redirect and redirect_count < max_redirects:
            redirect_count += 1
            location = resp.headers.get('Location')
            if not location:
                break
            
            # Make location absolute if relative
            loc_parsed = requests.utils.urlparse(location)
            if not loc_parsed.scheme or not loc_parsed.netloc:
                location = requests.utils.urljoin(current_url, location)
            
            # Validate redirect destination against allowlist
            if not is_safe_audit_url(location):
                return "Error"  # Redirect to unsafe destination
            
            # Follow redirect
            current_url = location
            resp = session.head(
                current_url,
                headers={"Cache-Control": "no-cache"},
                allow_redirects=False,
                timeout=10,
                verify=VERIFY_SSL,
            )
            current_status = str(resp.status_code)
        
        return current_status
    except requests.exceptions.Timeout:
        return "Timeout"
    except requests.exceptions.RequestException:
        return "Error"
    except Exception:
        return "Error"  # Catch any unexpected errors but label as "Error" not "Timeout"

def get_content_data(url, session=SESSION):
    """
    Get content data from URL for internal audit purposes.
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
    if not is_safe_audit_url(url):
        print(f"Error: URL rejected by safe audit allowlist: {url}")
        return dict.fromkeys(res, 'Error')
    try:
        resp = session.get(
            url,
            verify=VERIFY_SSL,
            timeout=10,
            headers={'Cache-Control': 'no-cache'},
            allow_redirects=False,
        )

        max_redirects = 5
        redirect_count = 0
        current_url = url

        while resp.is_redirect and redirect_count < max_redirects:
            redirect_count += 1
            location = resp.headers.get('Location')
            if not location:
                break

            loc_parsed = requests.utils.urlparse(location)
            if not loc_parsed.scheme or not loc_parsed.netloc:
                location = requests.utils.urljoin(current_url, location)

            if not is_safe_audit_url(location):
                print(f"Error: Redirect target rejected by safe audit allowlist: {location}")
                return dict.fromkeys(res, 'Error')

            current_url = location
            resp = session.get(
                current_url,
                verify=VERIFY_SSL,
                timeout=10,
                headers={'Cache-Control': 'no-cache'},
                allow_redirects=False,
            )

        if resp.is_redirect:
            if redirect_count >= max_redirects:
                print(f"Error: Redirect limit ({max_redirects}) exceeded for {url}")
            else:
                print(f"Error: Redirect response missing Location header for {url}")
            return dict.fromkeys(res, 'Error')

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

            # Extract prices safely using bounded quantifiers to prevent ReDoS (CWE-1333 / py/polynomial-redos)
            prices = re.findall(r'\b\d{1,7}(?:[.,]\d{1,3})*\s*€', html)
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

    p_status = get_http_status(p_url)
    s_status = get_http_status(s_url)

    # Always fetch content regardless of status
    # This helps catch cases where HTTP status check might be incomplete
    p_content = get_content_data(p_url)
    s_content = get_content_data(s_url)
    
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

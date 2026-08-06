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

DEFAULT_OUT_DIR = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
SAFE_AUDIT_BASE_DIR = Path('/home/ubuntu').resolve()

def _resolve_safe_out_dir():
    raw_out_dir = os.environ.get("AUDIT_OUT_DIR")
    if not raw_out_dir:
        return DEFAULT_OUT_DIR

    raw_out_dir = raw_out_dir.strip()
    # Rechazar entradas claramente peligrosas antes de construir paths.
    # Solo permitir rutas con caracteres esperados para este contexto.
    if '\x00' in raw_out_dir or not re.fullmatch(r"[A-Za-z0-9._/\-~]+", raw_out_dir):
        print(
            f"WARNING: Ignorando AUDIT_OUT_DIR con formato no permitido: {raw_out_dir}. "
            f"Usando valor por defecto: {DEFAULT_OUT_DIR}"
        )
        return DEFAULT_OUT_DIR

    if '..' in Path(raw_out_dir).parts:
        print(
            f"WARNING: Ignorando AUDIT_OUT_DIR con segmentos no permitidos: {raw_out_dir}. "
            f"Usando valor por defecto: {DEFAULT_OUT_DIR}"
        )
        return DEFAULT_OUT_DIR

    candidate = Path(raw_out_dir).expanduser().resolve()
    if candidate == SAFE_AUDIT_BASE_DIR or SAFE_AUDIT_BASE_DIR in candidate.parents:
        return candidate

    print(
        f"WARNING: Ignorando AUDIT_OUT_DIR no seguro: {raw_out_dir}. "
        f"Usando valor por defecto: {DEFAULT_OUT_DIR}"
    )
    return DEFAULT_OUT_DIR

OUT = _resolve_safe_out_dir()

def load_slugs():
    routes_file = OUT / 'staging_routes.json'
    if not routes_file.exists():
        print(f"Aviso: El archivo de rutas {routes_file} no existe. No hay rutas para auditar.")
        return []
    with open(routes_file, 'r', encoding='utf-8') as f:
        return json.load(f)

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

def _get_type_list(node):
    if not isinstance(node, dict) or TYPE_KEY not in node:
        return []
    val = node[TYPE_KEY]
    return val if isinstance(val, list) else [val]

def _extract_types_from_obj(data):
    types = []
    if not isinstance(data, dict):
        return types
    types.extend(_get_type_list(data))
    graph = data.get(GRAPH_KEY)
    if isinstance(graph, list):
        for item in graph:
            types.extend(_get_type_list(item))
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
        location = resp.headers.get('Location')
        if not location:
            break

        loc_parsed = requests.utils.urlparse(location)
        if not loc_parsed.scheme or not loc_parsed.netloc:
            location = requests.utils.urljoin(current_url, location)

        if not is_safe_audit_url(location):
            resp.close()
            return None

        resp.close()
        current_url = location
        resp = fetch(current_url, **kwargs)

    return resp

def _read_bounded_response_text(resp, max_bytes=1000000):
    chunks = []
    total_bytes = 0
    for chunk in resp.iter_content(chunk_size=16384, decode_unicode=False):
        chunks.append(chunk)
        total_bytes += len(chunk)
        if total_bytes >= max_bytes:
            break
    raw_bytes = b''.join(chunks)[:max_bytes]
    encoding = resp.encoding
    header_encoding = encoding
    if not encoding or encoding.lower() in ('iso-8859-1', 'latin-1'):
        encoding = None
        # Sniff HTML meta charset without polynomial regex by checking head_bytes directly
        head_bytes = raw_bytes[:4096].lower()
        idx = head_bytes.find(b'charset=')
        if idx != -1:
            snippet = head_bytes[idx + 8:idx + 40]
            # Strip quotes if present
            snippet = snippet.lstrip(b'"\'')
            # Extract ascii charset chars linearly
            charset_bytes = bytearray()
            for b in snippet:
                if (ord('a') <= b <= ord('z')) or (ord('0') <= b <= ord('9')) or b in (ord('-'), ord('_')):
                    charset_bytes.append(b)
                else:
                    break
            if charset_bytes:
                try:
                    encoding = charset_bytes.decode('ascii')
                except Exception:
                    encoding = None
        if not encoding:
            encoding = 'utf-8' if header_encoding else 'utf-8'
    try:
        return raw_bytes.decode(encoding, errors='replace')
    except Exception:
        return raw_bytes.decode('utf-8', errors='replace')

def _extract_prices_linearly(html):
    prices = []
    start = 0
    while True:
        euro_idx = html.find('€', start)
        if euro_idx == -1:
            break
        snippet = html[max(0, euro_idx - 25):euro_idx].rstrip()
        num_chars = []
        for char in reversed(snippet):
            if char.isdigit() or char in '.,':
                num_chars.append(char)
            else:
                break
        if num_chars:
            num_str = ''.join(reversed(num_chars)).strip('.,')
            if num_str:
                prices.append(f"{num_str} €")
        start = euro_idx + 1
    return prices

def _parse_html_fields(html):
    soup = BeautifulSoup(html, 'html.parser')
    can = soup.find('link', rel='canonical')
    rob = soup.find('meta', attrs={'name': 'robots'})
    h1 = soup.find('h1')
    prices = _extract_prices_linearly(html)

    canonical_val = 'N/D'
    if can is not None:
        canonical_val = can.get('href') or 'N/D'

    robots_val = 'N/D'
    if rob is not None:
        robots_val = rob.get('content') or 'N/D'

    h1_val = 'N/D'
    if h1 is not None:
        h1_val = h1.get_text(strip=True) or 'N/D'

    return {
        'canonical': canonical_val,
        'robots': robots_val,
        'h1': h1_val,
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
        return "Error", dict(default_content)

    try:
        resp = _follow_redirects_safely(session, url, method="get", stream=True)
        if resp is None:
            return "Error", dict(default_content)

        try:
            status = str(resp.status_code)
            if resp.status_code == 200:
                html = _read_bounded_response_text(resp)
                content = _parse_html_fields(html)
            else:
                content = dict(default_content)
        finally:
            resp.close()

        return status, content
    except requests.exceptions.Timeout:
        return "Timeout", dict(default_content)
    except Exception as e:
        print(f"Error fetching {url}: {e}")
        return "Error", dict(default_content)

def get_gap_tipo(p_status, s_status):
    if p_status == '404' and s_status == '404': return 'contenido_falta_ambos'
    if p_status == '404' and s_status == '200': return 'drift_falta_produccion'
    if p_status == '200' and s_status == '404': return 'drift_falta_staging'
    if p_status == '200' and s_status == '200': return 'coincide'
    return 'inconsistente'

def run_audit():
    slugs = load_slugs()
    results = []
    print(f"Iniciando auditoría robusta de {len(slugs)} URLs...")

    for i, slug in enumerate(slugs):
        p_url = f"{PROD_BASE.rstrip('/')}/{slug.lstrip('/')}"
        s_url = f"{STAG_BASE.rstrip('/')}/{slug.lstrip('/')}"
        
        print(f"Auditando ({i+1}/{len(slugs)}): /{slug}")

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
            print(f"\n--- CORTE PROGRESO ({i+1}/{len(slugs)} PÁGINAS) ---")
            last_10 = results[-10:]
            summary = {
                'acumulado_total': {
                    'coincide': sum(1 for r in results if r['gap_tipo'] == 'coincide'),
                    'drift_falta_produccion': sum(1 for r in results if r['gap_tipo'] == 'drift_falta_produccion'),
                    'drift_falta_staging': sum(1 for r in results if r['gap_tipo'] == 'drift_falta_staging'),
                    'contenido_falta_ambos': sum(1 for r in results if r['gap_tipo'] == 'contenido_falta_ambos'),
                    'inconsistente': sum(1 for r in results if r['gap_tipo'] == 'inconsistente')
                },
                'ultimo_bloque_10': {
                    'coincide': sum(1 for r in last_10 if r['gap_tipo'] == 'coincide'),
                    'drift_falta_produccion': sum(1 for r in last_10 if r['gap_tipo'] == 'drift_falta_produccion'),
                    'drift_falta_staging': sum(1 for r in last_10 if r['gap_tipo'] == 'drift_falta_staging'),
                    'contenido_falta_ambos': sum(1 for r in last_10 if r['gap_tipo'] == 'contenido_falta_ambos'),
                    'inconsistente': sum(1 for r in last_10 if r['gap_tipo'] == 'inconsistente')
                }
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

"""
Comprehensive Audit Script for NUVANX Production & Staging Route Synchronization

Environment Variables:
  AUDIT_OUT_DIR (default: "/home/ubuntu/nuvanx_audit_2026-08-04")
    Directory containing 'staging_routes.json' and destination for output CSV files.
  AUDIT_VERIFY_SSL (default: "true")
    Set to "false" (e.g., AUDIT_VERIFY_SSL=false python3 audit/audit_comprehensive.py)
    to bypass SSL certificate verification if auditing staging environments with
    self-signed or untrusted TLS certificates.
  AUDIT_TRUST_ENV (default: "false")
    Set to "true" to enable reading system proxies (HTTP_PROXY, HTTPS_PROXY) and .netrc credentials.
"""
import json
import csv
import sys
from pathlib import Path
from bs4 import BeautifulSoup
import requests
import os
import re
from contextlib import suppress

DEFAULT_OUT_DIR = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
SAFE_AUDIT_BASE_DIR = Path('/home/ubuntu').resolve()

def _resolve_safe_out_dir():
    raw_out_dir = os.environ.get("AUDIT_OUT_DIR")
    if not raw_out_dir:
        return DEFAULT_OUT_DIR

    raw_out_dir = raw_out_dir.strip()
    # Rechazar entradas claramente peligrosas antes de construir paths.
    # Solo permitir rutas con caracteres esperados para este contexto.
    if '\x00' in raw_out_dir:
        print(
            f"WARNING: Ignorando AUDIT_OUT_DIR con caracteres no permitidos. "
            f"Usando valor por defecto: {DEFAULT_OUT_DIR}"
        )
        return DEFAULT_OUT_DIR

    # Permitir únicamente caracteres esperados en rutas locales.
    # Evita caracteres de control y otros símbolos inesperados.
    if not re.fullmatch(r"[A-Za-z0-9._/\-~]+", raw_out_dir):
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

    try:
        candidate = Path(raw_out_dir).expanduser().resolve()
    except (OSError, RuntimeError, ValueError):
        print(
            f"WARNING: Ignorando AUDIT_OUT_DIR inválido: {raw_out_dir}. "
            f"Usando valor por defecto: {DEFAULT_OUT_DIR}"
        )
        return DEFAULT_OUT_DIR

    try:
        candidate.relative_to(SAFE_AUDIT_BASE_DIR)
        return candidate
    except ValueError:
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
    try:
        with open(routes_file, encoding='utf-8') as f:
            data = json.load(f)
    except (OSError, ValueError) as err:
        print(f"Aviso: No se pudo leer {routes_file}: {err}. No hay rutas para auditar.")
        return []
    if not isinstance(data, list) or not all(isinstance(s, str) for s in data):
        print(f"Aviso: Formato inesperado en {routes_file} (se esperaba lista de strings). No hay rutas para auditar.")
        return []
    return data

PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"
ALLOWED_AUDIT_HOSTS = {"nuvanx.com", "staging2.nuvanx.com"}
VERIFY_SSL = os.environ.get("AUDIT_VERIFY_SSL", "").strip().lower() != "false"
if not VERIFY_SSL:
    print("WARNING: AUDIT_VERIFY_SSL=false is active. TLS certificate verification is disabled for this audit run.")
    with suppress(Exception):
        import urllib3
        urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

SESSION = requests.Session()
# Disable implicit authentication from .netrc and unvetted proxies by default for security
SESSION.trust_env = os.environ.get("AUDIT_TRUST_ENV", "false").lower() == "true"

def is_safe_audit_url(url):
    """
    Determine whether a URL is permitted for the route audit.

    Parameters:
        url (str): URL to validate.

    Returns:
        bool: `True` if the URL uses HTTP or HTTPS, contains no credentials, parameters, query, or fragment, and targets an allowed audit host; `False` otherwise.
    """
    parsed = requests.utils.urlparse(url)
    if parsed.scheme not in ("http", "https"):
        return False
    if not parsed.netloc or not parsed.hostname:
        return False
    if parsed.username or parsed.password:
        return False
    if parsed.params or parsed.query or parsed.fragment:
        return False
    host = parsed.hostname.lower()
    return host in ALLOWED_AUDIT_HOSTS

TYPE_KEY = '@type'
GRAPH_KEY = '@graph'

def _get_type_list(node):
    if not isinstance(node, dict) or TYPE_KEY not in node:
        return []
    val = node[TYPE_KEY]
    return val if isinstance(val, list) else [val]

def _extract_types_from_obj(data):
    types = []
    if isinstance(data, list):
        # Top-level JSON-LD array: process each element
        for item in data:
            types.extend(_extract_types_from_obj(item))
        return types
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
        raw_text = s.get_text(strip=True)
        if not raw_text:
            continue
        try:
            data = json.loads(raw_text)
            types.extend(_extract_types_from_obj(data))
        except Exception:
            continue
    return '; '.join(sorted(set(types))) if types else 'N/D'

def _follow_redirects_safely(session, url, stream=True):
    session = session or SESSION
    kwargs = {"verify": VERIFY_SSL, "timeout": 10, "headers": {'Cache-Control': 'no-cache'}, "allow_redirects": False}
    if stream:
        kwargs["stream"] = True

    resp = session.get(url, **kwargs)
    max_redirects = 5
    redirect_count = 0
    current_url = url

    while resp.is_redirect and redirect_count < max_redirects:
        redirect_count += 1
        location = resp.headers.get('Location')
        if not location:
            # is_redirect guarantees Location exists in requests, so this is defensive only
            break

        loc_parsed = requests.utils.urlparse(location)
        # Only treat as absolute URL if scheme is explicitly http or https.
        # urlparse treats 'precios:2/page' as having scheme 'precios', so we
        # must not rely on the presence of any scheme — only known-safe ones.
        if loc_parsed.scheme not in ('http', 'https'):
            location = requests.utils.urljoin(current_url, location)

        if not is_safe_audit_url(location):
            resp.close()
            return None

        resp.close()
        current_url = location
        resp = session.get(current_url, **kwargs)

    if resp.is_redirect and redirect_count >= max_redirects:
        print(f"Aviso: Límite de redirecciones ({max_redirects}) alcanzado para {url}")

    return resp

def _parse_charset_from_tag(meta_tag):
    """
    Extracts the character encoding name from a meta tag byte string.

    Parameters:
        meta_tag (bytes): Meta tag content to inspect.

    Returns:
        str or None: The decoded character encoding name, or `None` when no valid encoding is found.
    """
    charset_idx = meta_tag.find(b'charset=')
    if charset_idx == -1:
        return None
    snippet = meta_tag[charset_idx + 8:charset_idx + 40].lstrip(b'"\'')
    charset_bytes = bytearray()
    for b in snippet:
        if (ord('a') <= b <= ord('z')) or (ord('0') <= b <= ord('9')) or b in (ord('-'), ord('_')):
            charset_bytes.append(b)
        else:
            break
    if charset_bytes:
        with suppress(Exception):
            return charset_bytes.decode('ascii')
    return None

def _sniff_meta_charset(head_bytes):
    """Extracts the first character encoding declared by an HTML meta tag.

    Parameters:
        head_bytes (bytes): The initial HTML bytes to inspect.

    Returns:
        str or None: The declared character encoding, or `None` when no encoding is found.
    """
    meta_idx = head_bytes.find(b'<meta')
    while meta_idx != -1:
        meta_end = head_bytes.find(b'>', meta_idx)
        if meta_end == -1:
            break
        meta_tag = head_bytes[meta_idx:meta_end]
        charset = _parse_charset_from_tag(meta_tag)
        if charset:
            return charset
        meta_idx = head_bytes.find(b'<meta', meta_end)
    return None

def _resolve_response_encoding(resp, raw_bytes):
    """
    Resolve the character encoding used to decode a response body.
    
    Parameters:
        resp: Response object containing the inferred encoding and headers.
        raw_bytes (bytes): Raw response content used for early HTML charset detection.
    
    Returns:
        str: The response-declared encoding, a detected HTML meta charset, or UTF-8.
    """
    if encoding := resp.encoding:
        enc_lower = encoding.lower()
        if enc_lower not in ('iso-8859-1', 'latin-1'):
            # Unambiguous non-default charset from server — trust it.
            return encoding
        # iso-8859-1/latin-1: could be an explicit server declaration or the
        # RFC HTTP/1.1 default that requests injects when no charset is present.
        # Distinguish by inspecting the raw Content-Type header directly.
        content_type = resp.headers.get('Content-Type', '')
        if 'charset=' in content_type.lower():
            # Server explicitly declared charset=iso-8859-1 — honor it.
            return encoding
    # No charset in Content-Type (requests defaulted to iso-8859-1): sniff <meta> then utf-8.
    sniffed = _sniff_meta_charset(raw_bytes[:4096].lower())
    if sniffed:
        return sniffed
    return 'utf-8'

def _read_bounded_response_text(resp, max_bytes=1000000):
    chunks = []
    total_bytes = 0
    for chunk in resp.iter_content(chunk_size=16384, decode_unicode=False):
        chunks.append(chunk)
        total_bytes += len(chunk)
        if total_bytes >= max_bytes:
            break
    raw_bytes = b''.join(chunks)[:max_bytes]
    encoding = _resolve_response_encoding(resp, raw_bytes)
    try:
        return raw_bytes.decode(encoding, errors='replace')
    except Exception:
        return raw_bytes.decode('utf-8', errors='replace')

def _parse_single_price(snippet):
    num_chars = []
    for char in reversed(snippet):
        if char.isdigit() or char in '.,':
            num_chars.append(char)
        else:
            break
    if not num_chars:
        return None
    num_str = ''.join(reversed(num_chars)).strip('.,')
    if not num_str:
        return None
    # Reject malformed runs with consecutive separators (e.g. "1,,2", "1..2", "1.,2")
    if any(a in num_str for a in (',,', '..', '.,', ',.')):
        return None
    dots_commas = num_str.count('.') + num_str.count(',')
    if dots_commas <= 2 and any(c.isdigit() for c in num_str):
        return f"{num_str} €"
    return None

def _extract_prices_linearly(html):
    prices = []
    start = 0
    while True:
        euro_idx = html.find('€', start)
        if euro_idx == -1:
            break
        snippet = html[max(0, euro_idx - 50):euro_idx].rstrip()
        price = _parse_single_price(snippet)
        if price:
            prices.append(price)
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
        resp = _follow_redirects_safely(session, url, stream=True)
        if resp is None:
            return "Error", dict(default_content)

        status = "Error"
        content = dict(default_content)
        try:
            status = str(resp.status_code)
            if resp.status_code == 200:
                try:
                    html = _read_bounded_response_text(resp)
                except requests.exceptions.ReadTimeout as rt_err:
                    print(f"Timeout leyendo cuerpo de {url}: {rt_err}")
                    return "Timeout", dict(default_content)
                except (requests.exceptions.RequestException, OSError) as net_err:
                    print(f"Error de red/streaming al leer cuerpo de {url}: {net_err}")
                    return "Error", dict(default_content)

                try:
                    content = _parse_html_fields(html)
                except Exception as parse_err:
                    print(f"Aviso: Error de estructura HTML al parsear {url}: {parse_err}")
            else:
                content = dict(default_content)
        finally:
            resp.close()

        return status, content
    except requests.exceptions.Timeout:
        return "Timeout", dict(default_content)
    except Exception as e:
        print(f"Error en la petición a {url}: {e}")
        return "Error", dict(default_content)

def get_gap_tipo(p_status, s_status):
    if p_status == '404' and s_status == '404': return 'contenido_falta_ambos'
    if p_status == '404' and s_status == '200': return 'drift_falta_produccion'
    if p_status == '200' and s_status == '404': return 'drift_falta_staging'
    if p_status == '200' and s_status == '200': return 'coincide'
    return 'inconsistente'

def run_audit():
    slugs = load_slugs()
    if not slugs:
        print("No se encontraron rutas para auditar. Verifique AUDIT_OUT_DIR y que staging_routes.json exista y sea válido.")
        sys.exit(1)
    results = []
    print(f"Iniciando auditoría robusta de {len(slugs)} URLs...")

    for i, slug in enumerate(slugs):
        p_url = f"{PROD_BASE.rstrip('/')}/{slug.lstrip('/')}"
        s_url = f"{STAG_BASE.rstrip('/')}/{slug.lstrip('/')}"
        
        print(f"Auditando ({i+1}/{len(slugs)}): /{slug}")

        p_status, p_content = fetch_url_audit_data(p_url)
        s_status, s_content = fetch_url_audit_data(s_url)
        
        gap_tipo = get_gap_tipo(p_status, s_status)
        
        def _sanitize_csv(val):
            s = str(val)
            if s.startswith(('=', '+', '-', '@', '\t', '\r')):
                return "'" + s
            return s
        row = {
            'slug': _sanitize_csv(slug),
            'gap_tipo': _sanitize_csv(gap_tipo),
            'prod_status': _sanitize_csv(p_status),
            'stag_status': _sanitize_csv(s_status),
            'prod_h1': _sanitize_csv(p_content['h1']),
            'stag_h1': _sanitize_csv(s_content['h1']),
            'prod_price': _sanitize_csv(p_content['price']),
            'stag_price': _sanitize_csv(s_content['price']),
            'prod_faq': _sanitize_csv(p_content['faq']),
            'stag_faq': _sanitize_csv(s_content['faq']),
            'prod_canonical': _sanitize_csv(p_content['canonical']),
            'stag_canonical': _sanitize_csv(s_content['canonical']),
            'prod_robots': _sanitize_csv(p_content['robots']),
            'stag_robots': _sanitize_csv(s_content['robots']),
            'prod_doctor': _sanitize_csv(p_content['doctor']),
            'stag_doctor': _sanitize_csv(s_content['doctor']),
            'prod_schema': _sanitize_csv(p_content['schema_type']),
            'stag_schema': _sanitize_csv(s_content['schema_type'])
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
            OUT.mkdir(parents=True, exist_ok=True)
            with (OUT / 'nuvanx_drift_partial.csv').open('w', encoding='utf-8-sig', newline='') as f:
                writer = csv.DictWriter(f, fieldnames=row.keys())
                writer.writeheader()
                writer.writerows(results)
            print("Resultados parciales guardados.\n")

    if results:
        OUT.mkdir(parents=True, exist_ok=True)
        with (OUT / 'nuvanx_drift_final.csv').open('w', encoding='utf-8-sig', newline='') as f:
            writer = csv.DictWriter(f, fieldnames=results[0].keys())
            writer.writeheader()
            writer.writerows(results)
        print("Auditoría integral completada exitosamente.")
    else:
        print("No se procesaron rutas (la lista de rutas estaba vacía). Auditoría finalizada sin crear CSV.")

if __name__ == '__main__':
    run_audit()

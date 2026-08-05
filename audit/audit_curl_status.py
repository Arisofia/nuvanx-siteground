import subprocess
import json
import csv
from pathlib import Path
import concurrent.futures

OUT = Path('/home/ubuntu/nuvanx_audit_2026-08-04')
with open(OUT / 'staging_routes.json', 'r') as f:
    SLUGS = json.load(f)

PROD_BASE = "https://nuvanx.com"
STAG_BASE = "https://staging2.nuvanx.com"

def get_status(url):
    try:
        # Usamos curl -sI para obtener solo cabeceras, forzando HTTP/1.1 y desactivando seguridad SSL si es necesario
        cmd = [
            'curl', '-sI', '-L', '--http1.1', '-k',
            '-H', 'Cache-Control: no-cache',
            '-A', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            url
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
        if result.returncode == 0:
            # Buscar el último código HTTP (en caso de redirecciones)
            lines = result.stdout.splitlines()
            status = "N/D"
            for line in reversed(lines):
                if line.startswith("HTTP/"):
                    status = line.split()[1]
                    break
            return status
        return "Error"
    except:
        return "Timeout"

def process_slug(slug):
    p_url = f"{PROD_BASE.rstrip('/')}/{slug.lstrip('/')}"
    s_url = f"{STAG_BASE.rstrip('/')}/{slug.lstrip('/')}"
    
    p_status = get_status(p_url)
    s_status = get_status(s_url)
    
    return {
        'slug': slug,
        'prod_status': p_status,
        'stag_status': s_status
    }

print(f"Auditando estados de {len(SLUGS)} URLs...")
with concurrent.futures.ThreadPoolExecutor(max_workers=10) as executor:
    results = list(executor.map(process_slug, SLUGS))

with (OUT / 'nuvanx_status_comparison.csv').open('w', encoding='utf-8-sig', newline='') as f:
    writer = csv.DictWriter(f, fieldnames=['slug', 'prod_status', 'stag_status'])
    writer.writeheader()
    writer.writerows(results)

print("Auditoría de estados completada.")

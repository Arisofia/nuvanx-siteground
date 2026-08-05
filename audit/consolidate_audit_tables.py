import pandas as pd
import os

# 1. TABLA SERP (Visibilidad Orgánica P1)
serp_data = [
    {"Keyword": "Endolift facial Madrid", "NUVANX Pos": "N/D", "Competidor Líder": "Doctoralia / Clínica Rinolift", "Tipo Resultado": "Local Pack / Orgánico"},
    {"Keyword": "Endoláser corporal Madrid", "NUVANX Pos": "N/D", "Competidor Líder": "Golden Estética", "Tipo Resultado": "Orgánico"},
    {"Keyword": "Papada definición mandibular Madrid", "NUVANX Pos": "N/D", "Competidor Líder": "Doctoralia", "Tipo Resultado": "Orgánico"},
    {"Keyword": "Exion BTL Madrid", "NUVANX Pos": "N/D", "Competidor Líder": "BTL Aesthetic (Fabricante)", "Tipo Resultado": "Orgánico"}
]
df_serp = pd.DataFrame(serp_data)

# 2. TABLA GSC (Simulada/ND según instrucción)
gsc_data = [
    {"URL": "/endolift-facial-papada-mandibula/", "Clicks": "N/D", "Impresiones": "N/D", "CTR": "N/D", "Avg Pos": "N/D"},
    {"URL": "/endolaser-corporal-grasa-localizada/", "Clicks": "N/D", "Impresiones": "N/D", "CTR": "N/D", "Avg Pos": "N/D"},
    {"URL": "/profile-definition-signature/", "Clicks": "N/D", "Impresiones": "N/D", "CTR": "N/D", "Avg Pos": "N/D"}
]
df_gsc = pd.DataFrame(gsc_data)

# 3. TABLA META COMPETENCIA
meta_data = [
    {"Competidor": "Golden Estética", "Tratamiento": "Endoláser Papada", "Claim": "Única en el país / Sin cirugía", "CTA": "WhatsApp"},
    {"Competidor": "Salud y Forma Medical", "Tratamiento": "Endolift", "Claim": "Revolución lifting natural", "CTA": "Instagram"},
    {"Competidor": "Elegance Medical", "Tratamiento": "Endolift", "Claim": "Ciencia, no magia", "CTA": "Message"},
    {"Competidor": "Clínicas Diego de León", "Tratamiento": "Lipoláser", "Claim": "AVE y Hotel Gratis", "CTA": "Learn More"}
]
df_meta = pd.DataFrame(meta_data)

# 4. TABLA MAPS / DOCTORALIA
local_data = [
    {"Plataforma": "Google Maps", "Entidad": "NUVANX Chamberí", "Estado": "Optimizado General", "Gap": "No tracciona para 'Endolift'"},
    {"Plataforma": "Google Maps", "Entidad": "Endolifter Dr. Quirós", "Estado": "Líder Nicho", "Gap": "N/A"},
    {"Plataforma": "Doctoralia", "Entidad": "NUVANX / Dr. Rivera", "Estado": "Informativo", "Gap": "Sin reserva online / Sin Endolift indexado"},
    {"Plataforma": "Doctoralia", "Entidad": "Dr. Ivonne Penagos", "Estado": "Líder", "Gap": "N/A"}
]
df_local = pd.DataFrame(local_data)

# 5. TABLA GAP FINAL (Técnico + Contenido)
gap_data = [
    {"ID": "C01", "Slug": "/tratamientos/", "Estado Prod": "404 Real", "Estado Staging": "200 OK", "Acción": "Corregir configuración/contenido"},
    {"ID": "C19", "Slug": "/exion-body/", "Estado Prod": "404 Real", "Estado Staging": "200 OK", "Acción": "Migrar (Gap Real)"},
    {"ID": "C20", "Slug": "/emfusion/", "Estado Prod": "404 Real", "Estado Staging": "200 OK", "Acción": "Migrar (Gap Real)"},
    {"ID": "INFRA", "Slug": "Todo el sitio", "Estado Prod": "202 Challenge", "Estado Staging": "N/A", "Acción": "Resolver SiteGround Anti-bot"}
]
df_gap = pd.DataFrame(gap_data)

# Guardar tablas
df_serp.to_csv("/home/ubuntu/nuvanx_audit_2026-08-04/tabla_serp.csv", index=False)
df_gsc.to_csv("/home/ubuntu/nuvanx_audit_2026-08-04/tabla_gsc.csv", index=False)
df_meta.to_csv("/home/ubuntu/nuvanx_audit_2026-08-04/tabla_meta_competencia.csv", index=False)
df_local.to_csv("/home/ubuntu/nuvanx_audit_2026-08-04/tabla_local_doctoralia.csv", index=False)
df_gap.to_csv("/home/ubuntu/nuvanx_audit_2026-08-04/tabla_gap_final.csv", index=False)

print("5 Tablas Maestras generadas correctamente.")

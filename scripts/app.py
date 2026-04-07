import os
import re
import shutil
import webbrowser
import requests
import pdfplumber
import openpyxl
from datetime import datetime

# ─── CONFIGURACIÓN ────────────────────────────────────────────────────────────

BASE_DIR    = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PDF_HOY     = os.path.join(BASE_DIR, "pdf_hoy")
PALABRAS    = os.path.join(BASE_DIR, "palabras.txt")
ARCHIVO     = os.path.join(BASE_DIR, "archivo")
RESULTADOS  = os.path.join(BASE_DIR, "resultados")
REGISTRO    = os.path.join(BASE_DIR, "registro")
EXCEL_PATH  = os.path.join(REGISTRO, "url_monitor.xlsx")
URL_BOLETIN = "https://www.boletinoficial.gob.ar/seccion/primera"

# ─── HELPERS ──────────────────────────────────────────────────────────────────

def encontrar_pdf():
    archivos = [f for f in os.listdir(PDF_HOY) if f.lower().endswith(".pdf")]
    if not archivos:
        print("ERROR: No hay ningún PDF en la carpeta pdf_hoy/")
        return None
    return os.path.join(PDF_HOY, archivos[0])


def leer_palabras():
    with open(PALABRAS, encoding="utf-8") as f:
        palabras = [l.strip() for l in f if l.strip()]
    if not palabras:
        print("ERROR: palabras.txt está vacío.")
    return palabras


def extraer_parrafos(pdf_path):
    """Extrae texto completo del PDF y lo divide en párrafos (split por punto)."""
    texto_completo = ""
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            texto = page.extract_text()
            if texto:
                texto_completo += " " + texto

    # Normalizar espacios y saltos de línea
    texto_completo = re.sub(r'\s+', ' ', texto_completo).strip()

    # Splitear por punto seguido de espacio o fin de cadena
    parrafos_raw = re.split(r'(?<=\.)\s+', texto_completo)

    # Limpiar párrafos vacíos o muy cortos (menos de 10 caracteres)
    parrafos = [p.strip() for p in parrafos_raw if len(p.strip()) >= 10]
    return parrafos


def buscar_palabras(parrafos, palabras):
    """Devuelve dict: {palabra: [lista de párrafos donde aparece]}"""
    resultados = {}
    for palabra in palabras:
        patron = re.compile(re.escape(palabra), re.IGNORECASE)
        matches = [p for p in parrafos if patron.search(p)]
        if matches:
            resultados[palabra] = matches
    return resultados


def archivar_pdf(pdf_path, fecha):
    año  = fecha.strftime("%Y")
    mes  = fecha.strftime("%m")
    dia  = fecha.strftime("%d")
    dest = os.path.join(ARCHIVO, año, mes, dia)
    os.makedirs(dest, exist_ok=True)
    nombre = f"boletin_{fecha.strftime('%Y-%m-%d')}.pdf"
    shutil.copy2(pdf_path, os.path.join(dest, nombre))
    print(f"PDF archivado en: {dest}/{nombre}")


def generar_html(resultados, fecha, palabras_buscadas):
    fecha_str = fecha.strftime("%Y-%m-%d")
    os.makedirs(RESULTADOS, exist_ok=True)
    ruta = os.path.join(RESULTADOS, f"{fecha_str}_reporte.html")

    total_parrafos = sum(len(v) for v in resultados.values())

    # Construir bloques por palabra
    bloques = ""
    for palabra, parrafos in resultados.items():
        items = "".join(f"<li>{p}</li>" for p in parrafos)
        bloques += f"""
        <div class="palabra-bloque">
            <h2>🔍 {palabra} <span class="badge">{len(parrafos)} párrafo(s)</span></h2>
            <ul>{items}</ul>
        </div>
        """

    html = f"""<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>scraping_BO — {fecha_str}</title>
    <style>
        body {{
            font-family: Arial, sans-serif;
            max-width: 960px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
            color: #222;
        }}
        h1 {{ color: #003366; border-bottom: 2px solid #003366; padding-bottom: 8px; }}
        .resumen {{
            background: #e8f0fe;
            border-left: 4px solid #003366;
            padding: 12px 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }}
        .palabra-bloque {{
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }}
        h2 {{ color: #003366; font-size: 1.1em; margin-top: 0; }}
        .badge {{
            background: #003366;
            color: white;
            font-size: 0.75em;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
            vertical-align: middle;
        }}
        ul {{ padding-left: 20px; }}
        li {{
            margin-bottom: 10px;
            line-height: 1.6;
            background: #fafafa;
            padding: 8px 12px;
            border-radius: 4px;
            border-left: 3px solid #ccc;
        }}
        .footer {{ margin-top: 40px; font-size: 0.8em; color: #888; text-align: center; }}
    </style>
</head>
<body>
    <h1>📋 scraping_BO — Boletín Oficial Primera Sección</h1>
    <div class="resumen">
        <strong>Fecha:</strong> {fecha_str} &nbsp;|&nbsp;
        <strong>Palabras buscadas:</strong> {len(palabras_buscadas)} &nbsp;|&nbsp;
        <strong>Palabras con match:</strong> {len(resultados)} &nbsp;|&nbsp;
        <strong>Párrafos encontrados:</strong> {total_parrafos}
    </div>
    {bloques}
    <div class="footer">Generado automáticamente por scraping_BO</div>
</body>
</html>"""

    with open(ruta, "w", encoding="utf-8") as f:
        f.write(html)

    print(f"Reporte generado: {ruta}")
    return ruta


def monitorear_url(fecha):
    """Hace GET a la URL del Boletín y registra disponibilidad en Excel."""
    try:
        r = requests.get(URL_BOLETIN, timeout=10)
        disponible = "Sí" if r.status_code == 200 else "No"
    except Exception:
        disponible = "No"

    os.makedirs(REGISTRO, exist_ok=True)

    if os.path.exists(EXCEL_PATH):
        wb = openpyxl.load_workbook(EXCEL_PATH)
        ws = wb.active
        ultimo_id = ws.cell(row=ws.max_row, column=1).value or 0
        nuevo_id = int(ultimo_id) + 1
    else:
        wb = openpyxl.Workbook()
        ws = wb.active
        ws.title = "Monitoreo URL"
        ws.append(["ID", "Fecha", "URL", "Disponible"])
        nuevo_id = 1

    ws.append([nuevo_id, fecha.strftime("%Y-%m-%d"), URL_BOLETIN, disponible])
    wb.save(EXCEL_PATH)
    print(f"URL monitoreada: {disponible} → registrado en url_monitor.xlsx")


# ─── MAIN ─────────────────────────────────────────────────────────────────────

def main():
    fecha = datetime.today()
    print(f"\n{'='*60}")
    print(f"  scraping_BO — {fecha.strftime('%d/%m/%Y')}")
    print(f"{'='*60}\n")

    # 1. Buscar PDF
    pdf_path = encontrar_pdf()
    if not pdf_path:
        monitorear_url(fecha)
        return

    print(f"PDF encontrado: {os.path.basename(pdf_path)}")

    # 2. Leer palabras
    palabras = leer_palabras()
    if not palabras:
        monitorear_url(fecha)
        return

    print(f"Palabras a buscar: {', '.join(palabras)}\n")

    # 3. Extraer párrafos
    print("Procesando PDF...")
    parrafos = extraer_parrafos(pdf_path)
    print(f"Párrafos extraídos: {len(parrafos)}")

    # 4. Buscar
    resultados = buscar_palabras(parrafos, palabras)

    # 5. Resultados
    if resultados:
        total = sum(len(v) for v in resultados.values())
        print(f"\n✔ Matches encontrados: {len(resultados)} palabra(s) — {total} párrafo(s)\n")
        for palabra, parrafos_match in resultados.items():
            print(f"  [{palabra}] → {len(parrafos_match)} párrafo(s)")
        archivar_pdf(pdf_path, fecha)
        ruta_reporte = generar_html(resultados, fecha, palabras)
        webbrowser.open(f"file:///{ruta_reporte.replace(os.sep, '/')}")
    else:
        print("\n✘ Sin resultados para las palabras buscadas.")
        print("  No se genera reporte ni se archiva el PDF.")

    # 6. Monitorear URL (siempre)
    print()
    monitorear_url(fecha)

    print(f"\n{'='*60}\n")


if __name__ == "__main__":
    main()
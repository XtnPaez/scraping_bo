import os
import re
import sys
import shutil
import sqlite3
import pdfplumber
from datetime import datetime

# ─── CONFIGURACION ────────────────────────────────────────────────────────────

BASE_DIR = r"C:\xampp\htdocs\scraping_bo"
DB_PATH  = os.path.join(BASE_DIR, "db", "boletin.db")
ARCHIVO  = os.path.join(BASE_DIR, "archivo")

# ─── BASE DE DATOS ────────────────────────────────────────────────────────────

def get_con():
    con = sqlite3.connect(DB_PATH)
    con.execute("PRAGMA foreign_keys = ON")
    return con

def leer_palabras(set_id):
    con = get_con()
    cur = con.execute("SELECT palabra FROM palabras WHERE set_id = ?", (set_id,))
    palabras = [row[0] for row in cur.fetchall()]
    con.close()
    return palabras

def ya_ejecutado(fecha_boletin, set_id):
    con = get_con()
    cur = con.execute(
        "SELECT id FROM ejecuciones WHERE fecha_boletin = ? AND set_id = ?",
        (fecha_boletin, set_id)
    )
    existe = cur.fetchone() is not None
    con.close()
    return existe

def guardar_ejecucion(fecha_boletin, ruta_pdf, set_id, tiene_resultados, cant_palabras, cant_parrafos):
    con = get_con()
    cur = con.execute(
        """INSERT INTO ejecuciones
           (fecha_boletin, ruta_pdf, set_id, fecha_ejecucion, tiene_resultados, cant_palabras, cant_parrafos)
           VALUES (?, ?, ?, ?, ?, ?, ?)""",
        (
            fecha_boletin,
            ruta_pdf,
            set_id,
            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "Si" if tiene_resultados else "No",
            cant_palabras,
            cant_parrafos
        )
    )
    ejecucion_id = cur.lastrowid
    con.commit()
    con.close()
    return ejecucion_id

def guardar_resultados(ejecucion_id, resultados):
    con = get_con()
    for palabra, parrafos in resultados.items():
        for parrafo in parrafos:
            con.execute(
                "INSERT INTO resultados (ejecucion_id, palabra, parrafo) VALUES (?, ?, ?)",
                (ejecucion_id, palabra, parrafo)
            )
    con.commit()
    con.close()

# ─── PDF ──────────────────────────────────────────────────────────────────────

def extraer_fecha_pdf(pdf_path):
    meses = {
        "enero": "01", "febrero": "02", "marzo": "03", "abril": "04",
        "mayo": "05", "junio": "06", "julio": "07", "agosto": "08",
        "septiembre": "09", "octubre": "10", "noviembre": "11", "diciembre": "12"
    }
    try:
        with pdfplumber.open(pdf_path) as pdf:
            texto = pdf.pages[0].extract_text() or ""
        patron = re.search(
            r'(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+de\s+(\d{4})',
            texto, re.IGNORECASE
        )
        if patron:
            dia  = patron.group(1).zfill(2)
            mes  = meses[patron.group(2).lower()]
            anio = patron.group(3)
            return "{}-{}-{}".format(anio, mes, dia)
    except Exception:
        pass
    return None

def archivar_pdf(pdf_path, fecha_str):
    anio, mes, dia_completo = fecha_str.split("-")
    dia       = str(int(dia_completo))
    dest_dir  = os.path.join(ARCHIVO, anio, mes)
    dest_path = os.path.join(dest_dir, "{}.pdf".format(dia))
    os.makedirs(dest_dir, exist_ok=True)
    if not os.path.exists(dest_path):
        shutil.copy2(pdf_path, dest_path)
        print("PDF archivado en: {}".format(dest_path))
    else:
        print("PDF ya archivado en: {}".format(dest_path))
    return dest_path

def extraer_parrafos(pdf_path):
    texto_completo = ""
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            texto = page.extract_text()
            if texto:
                texto_completo += " " + texto
    texto_completo = re.sub(r'\s+', ' ', texto_completo).strip()
    parrafos_raw   = re.split(r'(?<=\.)\s+', texto_completo)
    return [p.strip() for p in parrafos_raw if len(p.strip()) >= 10]

def buscar_palabras(parrafos, palabras):
    resultados = {}
    for palabra in palabras:
        patron  = re.compile(re.escape(palabra), re.IGNORECASE)
        matches = [p for p in parrafos if patron.search(p)]
        if matches:
            resultados[palabra] = matches
    return resultados

# ─── MODO: SOLO FECHA ─────────────────────────────────────────────────────────

def modo_solo_fecha(pdf_path):
    fecha = extraer_fecha_pdf(pdf_path)
    if fecha:
        print("FECHA:{}".format(fecha))
    else:
        print("FECHA:no_encontrada")

# ─── MAIN ─────────────────────────────────────────────────────────────────────

def main():
    if len(sys.argv) < 2:
        print("ERROR: uso -> app.py <pdf_path> <set_id>")
        print("             app.py --solo-fecha <pdf_path>")
        sys.exit(1)

    # Modo solo-fecha: extraer fecha y salir
    if sys.argv[1] == '--solo-fecha':
        if len(sys.argv) < 3:
            print("FECHA:no_encontrada")
            sys.exit(1)
        modo_solo_fecha(sys.argv[2])
        sys.exit(0)

    # Modo normal: pdf_path + set_id
    if len(sys.argv) < 3:
        print("ERROR: uso -> app.py <pdf_path> <set_id>")
        sys.exit(1)

    pdf_path      = sys.argv[1]
    set_id        = int(sys.argv[2])
    fecha_boletin = sys.argv[3] if len(sys.argv) > 3 else None

    print("\n" + "="*60)
    print("  scraping_BO — {}".format(datetime.now().strftime("%d/%m/%Y %H:%M")))
    print("="*60 + "\n")

    if not os.path.exists(pdf_path):
        print("ERROR: no se encuentra el PDF: {}".format(pdf_path))
        sys.exit(1)

    if not fecha_boletin:
        fecha_boletin = extraer_fecha_pdf(pdf_path)
        if fecha_boletin:
            print("Fecha extraida del PDF: {}".format(fecha_boletin))
        else:
            print("ERROR: no se pudo extraer la fecha del PDF.")
            sys.exit(1)

    if ya_ejecutado(fecha_boletin, set_id):
        print("DUPLICADO: esta combinacion PDF + set ya fue procesada.")
        print("fecha_boletin={} set_id={}".format(fecha_boletin, set_id))
        sys.exit(2)

    ruta_pdf = archivar_pdf(pdf_path, fecha_boletin)

    palabras = leer_palabras(set_id)
    if not palabras:
        print("ERROR: el set {} no tiene palabras cargadas.".format(set_id))
        sys.exit(1)
    print("Palabras en el set: {}".format(len(palabras)))

    print("Procesando PDF...")
    parrafos = extraer_parrafos(ruta_pdf)
    print("Parrafos extraidos: {}".format(len(parrafos)))

    resultados    = buscar_palabras(parrafos, palabras)
    cant_parrafos = sum(len(v) for v in resultados.values())
    ejecucion_id  = guardar_ejecucion(
        fecha_boletin, ruta_pdf, set_id,
        tiene_resultados=bool(resultados),
        cant_palabras=len(palabras),
        cant_parrafos=cant_parrafos
    )

    if resultados:
        guardar_resultados(ejecucion_id, resultados)
        print("\nMATCHES ENCONTRADOS: {} palabra(s) — {} parrafo(s)".format(
            len(resultados), cant_parrafos))
        for palabra, parrafos_match in resultados.items():
            print("  [{}] -> {} parrafo(s)".format(palabra, len(parrafos_match)))
    else:
        print("\nSIN RESULTADOS para las palabras del set.")

    print("\nejecucion_id={}".format(ejecucion_id))
    print("\n" + "="*60 + "\n")


if __name__ == "__main__":
    main()

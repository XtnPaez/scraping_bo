# scraping_BO

![Python](https://img.shields.io/badge/Python-3.12.10-blue?logo=python&logoColor=white)
![pdfplumber](https://img.shields.io/badge/pdfplumber-0.11-orange?logo=adobeacrobatreader&logoColor=white)
![openpyxl](https://img.shields.io/badge/openpyxl-3.1-green?logo=microsoftexcel&logoColor=white)
![Windows](https://img.shields.io/badge/Windows-portable-lightgrey?logo=windows&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-brightgreen)

Sistema local para monitorear el **Boletín Oficial de la República Argentina – Primera Sección**.  
Busca palabras clave dentro del PDF del día y recupera los párrafos completos donde aparecen, generando un reporte HTML automático.

---

## ¿Qué hace?

- Lee el PDF del Boletín Oficial (copiado manualmente a `pdf_hoy/`).
- Busca cada palabra clave definida en `palabras.txt` (búsqueda case-insensitive).
- Devuelve los párrafos completos donde aparece cada palabra (texto entre punto y punto).
- Si hay matches: genera un reporte HTML, lo abre en el navegador y copia el PDF a `archivo/AÑO/MES/DIA/`.
- Si no hay matches: avisa en consola y no genera reporte.
- En paralelo, registra si la URL del Boletín Oficial respondió correctamente ese día (monitoreo de estabilidad).

---

## Estructura de carpetas

```
scraping_bo/
├── README.md
├── pdf_hoy/                    ← Pegá acá el PDF del día (cualquier nombre .pdf)
├── palabras.txt                ← Palabras clave para buscar (una por línea)
├── scripts/
│   ├── app.py                  ← Script principal
│   ├── run.bat                 ← Doble clic → ejecuta todo
│   ├── requirements.txt        ← Dependencias Python
│   └── portable_python/        ← Python portable (no se sube al repo)
│       └── PYTHON_PORTABLE.md  ← Instrucciones de descarga e instalación
├── archivo/                    ← PDFs con matches, estructura creada automáticamente
│   └── 2026/
│       └── 04/
│           └── 07/
│               └── boletin_2026-04-07.pdf
├── resultados/                 ← Reportes HTML, creada automáticamente
│   └── 2026-04-07_reporte.html
└── registro/
    └── url_monitor.xlsx        ← Monitoreo diario de disponibilidad de la URL
```

---

## Requisitos

- Python 3.12.10 portable (WinPython dot, 64 bits) — ver `scripts/portable_python/PYTHON_PORTABLE.md`
- Sin permisos de administrador requeridos
- Librerías: ver `scripts/requirements.txt`

### Instalación de dependencias (solo la primera vez)

Abrí **WinPython Command Prompt** (está dentro de `scripts/portable_python/WPy64-31241/`) e ingresá el comando con la ruta completa al archivo:

```bash
pip install -r RUTA_COMPLETA\scraping_bo\scripts\requirements.txt
```

Por ejemplo: `C:\Users\tunombre\scraping_bo\scripts\requirements.txt`

> **Atención:** no alcanza con `pip install -r requirements.txt` porque pip corre desde su propia carpeta y no encuentra el archivo. Siempre usá la ruta completa.

---

## Uso

1. Descargá el PDF del Boletín Oficial desde [boletinoficial.gob.ar](https://www.boletinoficial.gob.ar) → Primera Sección.
2. Copiá el PDF dentro de `pdf_hoy/` (cualquier nombre, extensión `.pdf`).
3. Revisá que `palabras.txt` tenga las palabras que querés buscar.
4. Doble clic en `scripts/run.bat`.

**Si hay matches:**
- Se muestra el resultado en consola.
- Se genera y abre automáticamente el reporte HTML en el navegador.
- El PDF se copia a `archivo/AÑO/MES/DIA/`.

**Si no hay matches:**
- Se avisa en consola: `Sin resultados para las palabras buscadas.`
- No se genera reporte ni se archiva el PDF.

En ambos casos se registra en `registro/url_monitor.xlsx` si la URL del Boletín estuvo disponible ese día.

---

## Formato de palabras clave

Una palabra o frase por línea. Búsqueda case-insensitive. Líneas vacías ignoradas.

Ejemplo de `palabras.txt`:

```
licitación
concesión
ministerio de salud
resolución conjunta
```

---

## Monitoreo de URL (`url_monitor.xlsx`)

Cada ejecución agrega una fila:

| Columna | Descripción |
|---|---|
| ID | Número correlativo |
| Fecha | Fecha de la ejecución |
| URL | URL monitoreada |
| Disponible | Sí / No (respuesta HTTP 200) |

El objetivo es confirmar estabilidad durante 15 días consecutivos antes de pasar a la Etapa 2 (análisis directo desde la URL, sin descarga manual del PDF).

---

## Limitaciones conocidas

- El sistema procesa PDFs de texto extraíble. No funciona con PDFs escaneados (imágenes).
- El PDF del día debe copiarse manualmente. La descarga automática se implementará en la Etapa 2.
- Si hay más de un PDF en `pdf_hoy/`, se procesa el primero encontrado.

---

## Roadmap

- [x] Etapa 1: análisis de PDF manual + monitoreo de estabilidad de URL
- [ ] Etapa 2: análisis directo desde URL (tras 15 días de estabilidad confirmada)
- [ ] Descarga automática del PDF del día
- [ ] Interfaz gráfica simple para operadores no técnicos

---

## Tecnologías

- [pdfplumber](https://github.com/jsvine/pdfplumber) — extracción de texto de PDFs
- [openpyxl](https://openpyxl.readthedocs.io/) — lectura y escritura de Excel
- [requests](https://requests.readthedocs.io/) — monitoreo de disponibilidad de URL
- Python estándar (`os`, `re`, `datetime`, `shutil`, `webbrowser`)

---

## Licencia

MIT — libre uso, modificación y distribución.

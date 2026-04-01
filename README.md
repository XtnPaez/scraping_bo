# BoletinChecker

Sistema local para monitorear el **Boletín Oficial de la República Argentina – Primera Sección**.  
Busca palabras clave dentro de los PDFs del Boletín y recupera los párrafos completos donde aparecen, generando reportes HTML diarios y un log Excel acumulativo.

---

## ¿Qué hace?

- Lee el PDF del Boletín Oficial (copiado manualmente a una carpeta).
- Busca cada palabra clave definida en `palabras.txt` (búsqueda case-insensitive).
- Devuelve los párrafos completos donde aparece cada palabra (definido como: texto entre punto y punto).
- Genera un reporte HTML legible que se abre automáticamente en el navegador.
- Si hay resultados, copia el PDF a `archivo/AÑO/MES/DIA/` para conservarlo.
- Registra cada ejecución en un Excel acumulativo (`registro/boletin_log.xlsx`).

---

## Estructura de carpetas

```
scraping_bo/
├── pdf_hoy/                        ← Pegá acá el PDF del día (cualquier nombre .pdf)
├── pdf_historicos/                 ← Pegá acá PDFs de días anteriores para reprocesar
├── palabras.txt                    ← Palabras clave para el flujo diario (una por línea)
├── palabras_historico.txt          ← Palabras clave para el flujo histórico (editable libremente)
├── scripts/
│   ├── app.py                      ← Script principal: procesa el boletín del día
│   ├── historico.py                ← Script para procesar boletines anteriores
│   ├── run.bat                     ← Doble clic → ejecuta app.py
│   ├── run_historico.bat           ← Doble clic → ejecuta historico.py
│   ├── requirements.txt            ← Dependencias Python
│   └── portable_python/            ← (Opcional) Python portable si no hay Python instalado
├── archivo/                        ← PDFs archivados automáticamente cuando hay resultados
│   └── 2026/
│       └── 04/
│           └── 01/
│               └── boletin_2026-04-01.pdf
├── resultados/                     ← Reportes HTML generados por ejecución
│   ├── 2026-04-01_reporte.html
│   └── ...
└── registro/
    └── boletin_log.xlsx            ← Log acumulativo de todas las ejecuciones
```

---

## Requisitos

- Python 3.8 o superior (portable o instalado)
- Librerías: ver `scripts/requirements.txt`

Instalación de dependencias (solo la primera vez):

```bash
pip install -r scripts/requirements.txt
```

Si usás Python portable o no tenés permisos de administrador:

```bash
pip install --user -r scripts/requirements.txt
```

---

## Uso: Flujo diario

1. Descargá el PDF del Boletín Oficial desde [boletinoficial.gob.ar](https://www.boletinoficial.gob.ar) → Primera Sección.
2. Copiá el PDF dentro de la carpeta `pdf_hoy/` (podés nombrarlo como quieras, ej: `boletin_hoy.pdf`).
3. Revisá que `palabras.txt` tenga las palabras que querés buscar (una por línea).
4. Doble clic en `scripts/run.bat` — o desde consola:

```bash
python scripts/app.py
```

El script va a:
- Procesar el PDF.
- Mostrar en consola un resumen de resultados.
- Abrir automáticamente el reporte HTML en el navegador.
- Registrar la ejecución en `registro/boletin_log.xlsx`.
- Si hubo resultados, copiar el PDF a `archivo/AÑO/MES/DIA/`.

---

## Uso: Flujo histórico

Para reprocesar boletines de días anteriores sin afectar el flujo diario:

1. Copiá el/los PDFs históricos dentro de `pdf_historicos/`.
2. Editá `palabras_historico.txt` con las palabras que querés buscar para esa ejecución.
3. Doble clic en `scripts/run_historico.bat` — o desde consola:

```bash
python scripts/historico.py
```

El script va a:
- Pedirte la fecha del boletín (formato `AAAA-MM-DD`).
- Procesar el PDF con `palabras_historico.txt`.
- Generar el reporte HTML correspondiente.
- Registrar en el mismo Excel acumulativo (`boletin_log.xlsx`).
- Archivar el PDF en `archivo/AÑO/MES/DIA/` si hay resultados.

---

## Formato de palabras clave

El archivo `palabras.txt` (y `palabras_historico.txt`) acepta una palabra o frase por línea.  
La búsqueda es **case-insensitive** (no distingue mayúsculas/minúsculas).  
Las líneas vacías y los espacios al inicio/final se ignoran.

Ejemplo de `palabras.txt`:

```
licitación
concesión
ministerio de salud
resolución conjunta
```

---

## Log Excel (`boletin_log.xlsx`)

Cada ejecución agrega una fila con las siguientes columnas:

| Columna | Descripción |
|---|---|
| ID | Número de ejecución correlativo |
| Fecha | Fecha del boletín procesado |
| Tiene_Resultados | Sí / No |
| Cant_Palabras | Cantidad de palabras buscadas |
| Cant_Parrafos | Total de párrafos encontrados con al menos una palabra |
| Ruta_PDF | Ruta al PDF procesado |
| Ruta_Reporte | Ruta al reporte HTML generado |
| Notas | Observaciones adicionales (ej: "flujo histórico", errores) |

---

## Limitaciones conocidas

- El sistema procesa PDFs de texto extraíble. No funciona con PDFs escaneados (imágenes).
- El PDF del día debe copiarse manualmente. La descarga automática no está implementada en esta versión.
- Un único PDF por ejecución en el flujo diario. Si hay más de uno en `pdf_hoy/`, se procesa el primero encontrado.

---

## Roadmap

- [x] Versión básica: búsqueda de palabras y reporte HTML
- [x] Log Excel acumulativo
- [x] Archivado automático de PDFs con resultados
- [x] Script separado para históricos
- [ ] Descarga automática del PDF del día
- [ ] Interfaz gráfica simple (GUI) para operadores no técnicos
- [ ] Filtrado de resultados por sección del Boletín (Decretos, Resoluciones, etc.)
- [ ] Script de reorganización del Excel por hojas (por año/mes)

---

## Tecnologías

- [pdfplumber](https://github.com/jsvine/pdfplumber) — extracción de texto de PDFs
- [openpyxl](https://openpyxl.readthedocs.io/) — lectura y escritura de Excel
- Python estándar (`os`, `re`, `datetime`, `shutil`, `webbrowser`)

---

## Licencia

MIT — libre uso, modificación y distribución.
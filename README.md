# scraping_BO v2

![Python](https://img.shields.io/badge/Python-3.12-blue?logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-portable-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![pdfplumber](https://img.shields.io/badge/pdfplumber-0.11-orange)
![Windows](https://img.shields.io/badge/Windows-portable-lightgrey?logo=windows&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-brightgreen)

Sistema local con interfaz web para monitorear el **Boletín Oficial de la República Argentina – Primera Sección**.  
Permite cargar PDFs del Boletín, buscar palabras clave organizadas en sets reutilizables, y consultar el historial completo de resultados desde el navegador.

---

## ¿Qué hace?

- El usuario sube el PDF del Boletín Oficial desde la interfaz web.
- El sistema extrae automáticamente la fecha del PDF y lo almacena en la carpeta de archivo.
- El usuario elige un set de palabras clave y ejecuta la búsqueda.
- El sistema busca cada palabra (case-insensitive) y recupera los párrafos completos donde aparece.
- Los resultados quedan registrados en una base de datos SQLite y se muestran en pantalla.
- El historial de ejecuciones y resultados es consultable por fecha, palabra o set.
- Los sets de palabras son inmutables: garantizan trazabilidad completa de cada búsqueda.

---

## Estructura de carpetas

```
scraping_bo/
├── README.md
├── archivo/                        ← PDFs archivados, estructura creada automáticamente
│   └── 2026/
│       └── 04/
│           └── 9.pdf               ← Un PDF por día (día sin cero: 1, 9, 15...)
├── db/
│   └── boletin.db                  ← Base de datos SQLite
├── scripts/
│   ├── app.py                      ← Motor de procesamiento Python
│   ├── requirements.txt            ← Dependencias Python
│   └── portable_python/            ← Python portable (no se sube al repo)
│       └── PYTHON_PORTABLE.md
└── web/
    ├── index.php                   ← Interfaz principal (5 tabs)
    ├── upload.php                  ← Recibe y archiva el PDF
    ├── ejecutar.php                ← Llama a app.py y devuelve resultados
    ├── sets.php                    ← ABM de sets de palabras (alta y consulta)
    ├── historicos.php              ← Búsqueda de resultados históricos
    ├── portable_php/
    │   └── PHP_PORTABLE.md         
    └── assets/
        ├── style.css
        └── app.js
```

---

## Interfaz web — Tabs

| Tab | Nombre | Función |
|-----|--------|---------|
| 1 | **Subir PDF** | Upload del PDF, extracción automática de fecha, archivado en `/año/mes/dia.pdf` |
| 2 | **Ejecutar** | Muestra el último PDF subido, selector de set de palabras, botón ejecutar, resultados desde la base |
| 3 | **Históricos** | Búsqueda de ejecuciones anteriores filtrada por fecha o texto |
| 4 | **Sets de palabras** | Alta de nuevos sets y consulta de sets existentes con sus palabras |
| 5 | **Re-ejecutar** | Selección de cualquier PDF archivado + set de palabras para nuevas pasadas, con control de duplicados |

---

## Base de datos — SQLite

**4 tablas:**

### `sets`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INTEGER PK | Identificador |
| alias | TEXT | Nombre corto mostrado en el combo |
| descripcion | TEXT | Descripción opcional del criterio de armado |
| fecha_creacion | TEXT | Fecha de alta del set |

### `palabras`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INTEGER PK | Identificador |
| set_id | INTEGER FK | Set al que pertenece |
| palabra | TEXT | Palabra o frase a buscar |

### `ejecuciones`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INTEGER PK | Identificador |
| fecha_boletin | TEXT | Fecha del boletín procesado |
| ruta_pdf | TEXT | Ruta al PDF archivado |
| set_id | INTEGER FK | Set de palabras utilizado |
| fecha_ejecucion | TEXT | Fecha y hora de la ejecución |
| tiene_resultados | TEXT | Sí / No |
| cant_palabras | INTEGER | Total de palabras en el set |
| cant_parrafos | INTEGER | Total de párrafos con match |

### `resultados`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INTEGER PK | Identificador |
| ejecucion_id | INTEGER FK | Ejecución a la que pertenece |
| palabra | TEXT | Palabra que generó el match |
| parrafo | TEXT | Párrafo completo donde aparece |

---

## Reglas de negocio importantes

- **Un PDF por día:** la carpeta `archivo/año/mes/` almacena un solo archivo `dia.pdf` por día. Si el mismo PDF se sube dos veces, no se sobreescribe el archivo ya archivado.
- **Sets inmutables:** los sets de palabras no se pueden modificar ni eliminar una vez creados. Si se necesita una variación, se crea un nuevo set. Esto garantiza que cualquier resultado en la base sea reproducible.
- **Control de duplicados:** el sistema bloquea la ejecución si la combinación `PDF + set` ya fue procesada anteriormente, evitando resultados duplicados en la base.
- **Archivado único:** el PDF se archiva en la primera ejecución del día. Las ejecuciones posteriores del mismo día usan el archivo ya existente.

---

## Convención de nombres de archivos

```
archivo/
└── AÑO (4 dígitos)/
    └── MES (2 dígitos, con cero: 01, 04, 12)/
        └── DIA.pdf (sin cero: 1, 9, 15, 31)
```

Ejemplo: el Boletín del 9 de abril de 2026 se guarda en `archivo/2026/04/9.pdf`.

---

## Stack tecnológico

| Componente | Tecnología | Motivo |
|-----------|-----------|--------|
| Interfaz | PHP portable + HTML/JS | Sin instalación, corre con servidor built-in de PHP |
| Motor de búsqueda | Python portable | Portable, sin permisos de administrador |
| Extracción de PDF | pdfplumber | Robusto para PDFs de texto |
| Base de datos | SQLite | Sin servidor, archivo único, portable |
| Servidor local | `php -S localhost:8080` | Built-in, sin XAMPP ni instalación |

---

## Requisitos

- Python 3.12 portable (WinPython dot, 64 bits)
- PHP portable (cualquier versión >= 7.4)
- Sin permisos de administrador requeridos
- Dependencias Python: ver `scripts/requirements.txt`

### Instalación de dependencias Python (solo la primera vez)

Desde WinPython Command Prompt:

```bash
pip install -r RUTA_COMPLETA\scraping_bo\scripts\requirements.txt
```

### Levantar el servidor PHP

Desde la carpeta `web/`:

```bash
php.exe -S localhost:8080
```

Luego abrir el navegador en `http://localhost:8080`.

---

## Limitaciones conocidas

- Procesa únicamente PDFs de texto extraíble. No funciona con PDFs escaneados (imágenes).
- El PDF del día debe descargarse manualmente desde el sitio del Boletín Oficial.
- Si hay más de un PDF en una misma carpeta de día, el sistema toma el primero encontrado (situación que no debería ocurrir por diseño).

---

## Tecnologías

- [pdfplumber](https://github.com/jsvine/pdfplumber) — extracción de texto de PDFs
- [SQLite](https://www.sqlite.org/) — base de datos local
- PHP built-in server — interfaz web sin instalación
- Python estándar (`os`, `re`, `datetime`, `shutil`)

---

## Licencia

MIT — libre uso, modificación y distribución.

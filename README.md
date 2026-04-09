# scraping_BO v2

![Python](https://img.shields.io/badge/Python-3.12-blue?logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
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
- El sistema busca cada entrada (case-insensitive, coincidencia exacta de frase) y recupera los párrafos completos donde aparece.
- Los resultados quedan registrados en una base de datos SQLite y se muestran en pantalla.
- El historial de ejecuciones y resultados es consultable por fecha, set o texto.
- Los sets de palabras son inmutables: garantizan trazabilidad completa de cada búsqueda.

---

## Estructura de carpetas

```
scraping_bo/
├── README.md
├── ROADMAP.md
├── .gitignore
├── archivo/                        ← PDFs archivados (creada automáticamente, no se sube al repo)
│   └── 2026/
│       └── 04/
│           └── 9.pdf               ← Un PDF por día (día sin cero: 1, 9, 15...)
├── db/
│   └── boletin.db                  ← Base de datos SQLite (no se sube al repo)
├── scripts/
│   ├── app.py                      ← Motor de procesamiento Python
│   ├── init_db.py                  ← Crea la base de datos (correr una sola vez)
│   ├── reset_db.py                 ← Borra la base de datos
│   ├── requirements.txt            ← Dependencias Python
│   └── portable_python/            ← Python portable (no se sube al repo)
│       └── PYTHON_PORTABLE.md
└── web/
    ├── index.php                   ← Punto de entrada principal
    ├── navbar.php                  ← Navbar compartida
    ├── footer.php                  ← Footer compartido
    ├── subir.php                   ← Tab 1: subir y archivar PDF
    ├── ejecutar.php                ← Tab 2: ejecutar búsqueda
    ├── historicos.php              ← Tab 3: búsqueda histórica
    ├── sets.php                    ← Tab 4: alta y consulta de sets
    ├── reejecutar.php              ← Tab 5: re-ejecutar PDFs archivados
    ├── iniciar.bat                 ← Doble clic para levantar el servidor
    ├── portable_php/               ← PHP portable (no se sube al repo)
    │   └── PHP_PORTABLE.md
    └── assets/
        ├── style.css
        └── app.js
```

---

## Interfaz web — Tabs

| Tab | Nombre | Función |
|-----|--------|---------|
| 1 | **Subir PDF** | Upload del PDF, extracción automática de fecha, archivado en `archivo/año/mes/dia.pdf` |
| 2 | **Ejecutar** | Muestra el último PDF subido, selector de set, botón ejecutar, resultados desde la base |
| 3 | **Históricos** | Búsqueda de ejecuciones anteriores filtrada por fecha, set o texto |
| 4 | **Sets de palabras** | Alta de sets desde CSV y consulta de sets existentes |
| 5 | **Re-ejecutar** | Selección de cualquier PDF archivado + set, con control de duplicados |

---

## Base de datos — SQLite

**4 tablas:**

### `sets`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INTEGER PK | Identificador |
| alias | TEXT | Nombre corto (máximo 20 caracteres, único) |
| descripcion | TEXT | Descripción opcional |
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
| tiene_resultados | TEXT | Si / No |
| cant_palabras | INTEGER | Total de palabras en el set |
| cant_parrafos | INTEGER | Total de párrafos con match |

### `resultados`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INTEGER PK | Identificador |
| ejecucion_id | INTEGER FK | Ejecución a la que pertenece |
| palabra | TEXT | Palabra o frase que generó el match |
| parrafo | TEXT | Párrafo completo donde aparece |

---

## Reglas de negocio

- **Un PDF por día:** `archivo/año/mes/` almacena un solo archivo `dia.pdf` por día. Si el mismo PDF se sube dos veces, no se sobreescribe.
- **Sets inmutables:** los sets no se pueden modificar ni eliminar una vez creados. Si se necesita una variación, se crea un nuevo set. Esto garantiza trazabilidad completa.
- **Control de duplicados:** el sistema bloquea la ejecución si la combinación `PDF + set` ya fue procesada, evitando resultados duplicados en la base.
- **Búsqueda exacta de frases:** cada entrada del set se busca como frase completa, case-insensitive, equivalente a buscar entre comillas en Google.

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

## Sets de palabras

Los sets se cargan desde un archivo `.csv` con las siguientes características:

- Una palabra o frase por línea
- Sin encabezado
- Sin punto y coma ni delimitadores
- Origen compatible: Excel en Windows (Windows-1252) o texto plano (UTF-8)
- El sistema normaliza el encoding automáticamente

Cada set tiene un **alias** (máximo 20 caracteres, único) visible en los combos de la interfaz, y una descripción opcional de libre uso.

---

## Stack tecnológico

| Componente | Tecnología | Motivo |
|-----------|-----------|--------|
| Interfaz | PHP 8.5 portable + Bootstrap 5.3 | Sin instalación, corre con servidor built-in de PHP |
| Motor de búsqueda | Python 3.12 portable | Portable, sin permisos de administrador |
| Extracción de PDF | pdfplumber | Robusto para PDFs de texto |
| Base de datos | SQLite | Sin servidor, archivo único, portable |
| Servidor local | `php -S localhost:8080` | Built-in, sin XAMPP ni instalación |

---

## Instalación

### 1. Dependencias Python (solo la primera vez)

Desde WinPython Command Prompt, parado en `scripts/`:

```bash
pip install -r requirements.txt
```

### 2. Crear la base de datos (solo la primera vez)

```bash
python init_db.py
```

### 3. Configurar php.ini

En `web/portable_php/php.ini` verificar que estén activas las siguientes extensiones y parámetros:

```ini
; Extensiones requeridas (quitar el ; del inicio si están comentadas)
extension=pdo_sqlite
extension=sqlite3

; Directorio de extensiones
extension_dir = "ext"

; Uploads
file_uploads = On
upload_tmp_dir = "C:\xampp\htdocs\scraping_bo\web\portable_php\tmp"
upload_max_filesize = 50M
post_max_size = 50M

; Tiempo de ejecución (necesario para PDFs grandes)
max_execution_time = 300
max_input_time = 300

; Memoria
memory_limit = 256M

; Silenciar notices
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED
```

> La carpeta `tmp` debe crearse manualmente dentro de `portable_php/` si no existe.

### 4. Levantar el servidor

Doble clic en `web/iniciar.bat`. Dejar la ventana abierta mientras se usa el sistema.

Abrir el navegador en `http://localhost:8080`.

---

## Limitaciones conocidas

- Procesa únicamente PDFs de texto extraíble. No funciona con PDFs escaneados (imágenes).
- El PDF del día debe descargarse manualmente desde el sitio del Boletín Oficial.
- El servidor PHP debe estar corriendo para usar el sistema (ventana del `iniciar.bat` abierta).

---

## Tecnologías

- [pdfplumber](https://github.com/jsvine/pdfplumber) — extracción de texto de PDFs
- [SQLite](https://www.sqlite.org/) — base de datos local
- [Bootstrap 5.3](https://getbootstrap.com/) — interfaz web
- PHP built-in server — servidor local sin instalación
- Python estándar (`os`, `re`, `datetime`, `shutil`, `sqlite3`)

---

## Licencia

MIT — libre uso, modificación y distribución.

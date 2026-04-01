# Python Portable

Esta carpeta contiene el intérprete Python portable usado por el proyecto.  
**No se sube al repositorio** (está en `.gitignore` porque pesa varios cientos de MB).

## Versión en uso

**WinPython 3.12.4 — 64 bits — variante dot**  
Archivo descargado: `Winpython64-3.12.4.1.dot.exe`

## Cómo instalarlo

1. Descargá el instalador desde: [https://winpython.github.io](https://winpython.github.io)
2. Buscá la versión **3.12.4 / 64 bits / dot**
3. Ejecutá el `.exe` y descomprimí **en esta misma carpeta** (`scripts/portable_python/`)
4. Vas a ver aparecer una carpeta llamada `WPy64-31241/` con el intérprete adentro

## ¿Por qué WinPython dot?

- No requiere permisos de administrador
- No modifica el sistema ni el registro de Windows
- La variante "dot" es la más liviana (sin IDE ni librerías científicas)
- Compatible con todas las dependencias del proyecto (`pdfplumber`, `openpyxl`, etc.)

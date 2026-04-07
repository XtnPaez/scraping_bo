# Python Portable

Esta carpeta contiene el intérprete Python portable usado por el proyecto.  
**No se sube al repositorio** (está en `.gitignore` porque pesa varios cientos de MB).

## Versión recomendada

**WinPython — 64 bits — variante dot**  
Versión mínima recomendada: 3.12.x

## Cómo instalarlo

1. Descargá el instalador desde: [https://winpython.github.io](https://winpython.github.io)
2. En la página, hacé clic en el release más reciente → vas a ir a GitHub → bajá hasta **Assets**
3. Buscá el archivo que tenga `dot` en el nombre, por ejemplo: `Winpython64-3.12.10.0dot.exe`  
   ⚠️ **Importante:** tiene que decir `dot`. Sin esa palabra es la versión completa (5 GB).
4. Ejecutá el `.exe` y descomprimí **en esta misma carpeta** (`scripts/portable_python/`)
5. Va a aparecer una carpeta llamada `WPy64-XXXXX/` con el intérprete adentro

> El nombre exacto de la carpeta (`WPy64-31241`, `WPy64-31200`, etc.) varía según la versión descargada. El script `run.bat` la detecta automáticamente.

## ¿Por qué WinPython dot?

- No requiere permisos de administrador
- No modifica el sistema ni el registro de Windows
- La variante "dot" es la más liviana (sin IDE ni librerías científicas)
- Compatible con todas las dependencias del proyecto (`pdfplumber`, `openpyxl`, `requests`)

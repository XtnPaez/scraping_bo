@echo off
chcp 65001 > nul
echo.
echo ============================================================
echo   scraping_BO
echo ============================================================
echo.

:: Ruta al Python portable
set PYTHON=%~dp0portable_python\WPy64-31241\python-3.12.4.amd64\python.exe

:: Verificar que existe
if not exist "%PYTHON%" (
    echo ERROR: No se encontro Python portable.
    echo Revisá scripts\portable_python\PYTHON_PORTABLE.md
    echo.
    pause
    exit /b 1
)

:: Ejecutar app.py
"%PYTHON%" "%~dp0app.py"

echo.
pause

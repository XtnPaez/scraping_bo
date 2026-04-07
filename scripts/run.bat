@echo off
chcp 65001 > nul
echo.
echo ============================================================
echo   scraping_BO
echo ============================================================
echo.

set PYTHON=%~dp0portable_python\WPy64-31241\python-3.12.4.amd64\python.exe

if not exist "%PYTHON%" (
    echo ERROR: No se encontro Python portable.
    echo Revisa scripts\portable_python\PYTHON_PORTABLE.md
    echo.
    pause
    exit /b 1
)

"%PYTHON%" "%~dp0app.py"

echo.
pause

@echo off
chcp 65001 > nul
echo.
echo ============================================================
echo   scraping_BO
echo ============================================================
echo.

:: Buscar Python portable automaticamente dentro de portable_python/
set PYTHON=
for /d %%D in ("%~dp0portable_python\WPy64-*") do (
    if exist "%%D\python-*.amd64\python.exe" (
        for /d %%P in ("%%D\python-*.amd64") do (
            set PYTHON=%%P\python.exe
        )
    )
)

if not defined PYTHON (
    echo ERROR: No se encontro Python portable.
    echo Revisa scripts\portable_python\PYTHON_PORTABLE.md
    echo.
    pause
    exit /b 1
)

echo Python: %PYTHON%
echo.

"%PYTHON%" "%~dp0app.py"

echo.
pause

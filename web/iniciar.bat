@echo off
chcp 65001 > nul
title scraping_BO — servidor local

echo.
echo ============================================================
echo   scraping_BO — Servidor local
echo ============================================================
echo.
echo   NO CERRAR ESTA VENTANA
echo   Mientras esta abierta, el sistema funciona.
echo.
echo   Abrir el navegador en:
echo   http://localhost:8080
echo.
echo ============================================================
echo.

set PHP=%~dp0portable_php\php.exe

if not exist "%PHP%" (
    echo ERROR: No se encontro php.exe
    echo Ruta esperada: %PHP%
    echo.
    pause
    exit /b 1
)

"%PHP%" -S localhost:8080 -t "%~dp0"

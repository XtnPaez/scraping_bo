# Roadmap — scraping_BO v2

---

## MVP con interfaz web ✅
> Sistema funcional completo para uso diario.

- [x] Base de datos SQLite con las 4 tablas (`sets`, `palabras`, `ejecuciones`, `resultados`)
- [x] Motor Python `app.py` con búsqueda exacta de frases, integrado con SQLite
- [x] Servidor PHP portable levantado en `localhost:8080` via `iniciar.bat`
- [x] Tab 1: upload de PDF, extracción automática de fecha, archivado en `archivo/año/mes/dia.pdf`
- [x] Tab 2: selector de set, ejecución via `shell_exec()`, resultados en pantalla desde la base
- [x] Tab 3: búsqueda histórica de resultados filtrada por fecha, set y texto
- [x] Tab 4: alta de sets desde CSV con alias único, consulta de sets existentes
- [x] Tab 5: re-ejecución de PDFs archivados con control de duplicados por combinación `PDF + set`

---

## Interfaz de usuario
> En desarrollo. El sistema es funcional pero la UI está siendo refinada.

- [ ] Revisión general de estilos y experiencia de usuario
- [ ] Mejoras de usabilidad en mobile
- [ ] Feedback visual más claro durante la ejecución
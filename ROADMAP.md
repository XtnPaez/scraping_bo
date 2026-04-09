# Roadmap — scraping_BO v2

---

## MVP con interfaz web
> Prioridad actual. Sistema funcional completo para uso diario.

- [ ] Base de datos SQLite con las 4 tablas (`sets`, `palabras`, `ejecuciones`, `resultados`)
- [ ] Motor Python `app.py` adaptado para recibir set de palabras desde la base en lugar de `palabras.txt`
- [ ] Servidor PHP portable levantado en `localhost:8080`
- [ ] Tab 1: upload de PDF, extracción automática de fecha, archivado en `archivo/año/mes/dia.pdf`
- [ ] Tab 2: selector de set, ejecución via `shell_exec()`, resultados en pantalla desde la base
- [ ] Tab 3: búsqueda histórica de resultados filtrada por fecha y texto
- [ ] Tab 4: alta de sets de palabras con alias y descripción, consulta de sets existentes
- [ ] Tab 5: re-ejecución de PDFs archivados con control de duplicados por combinación `PDF + set`
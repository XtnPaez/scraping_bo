# Roadmap — scraping_BO v2

---

## Etapa 1 — MVP con interfaz web
> Prioridad actual. Sistema funcional completo para uso diario.

- [ ] Base de datos SQLite con las 4 tablas (`sets`, `palabras`, `ejecuciones`, `resultados`)
- [ ] Motor Python `app.py` adaptado para recibir set de palabras desde la base en lugar de `palabras.txt`
- [ ] Servidor PHP portable levantado en `localhost:8080`
- [ ] Tab 1: upload de PDF, extracción automática de fecha, archivado en `archivo/año/mes/dia.pdf`
- [ ] Tab 2: selector de set, ejecución via `shell_exec()`, resultados en pantalla desde la base
- [ ] Tab 3: búsqueda histórica de resultados filtrada por fecha y texto
- [ ] Tab 4: alta de sets de palabras con alias y descripción, consulta de sets existentes
- [ ] Tab 5: re-ejecución de PDFs archivados con control de duplicados por combinación `PDF + set`

---

## Etapa 2 — Descarga automática del PDF
> Depende de confirmar 15 días consecutivos de estabilidad de la URL del Boletín Oficial.

- [ ] Registro diario de disponibilidad de URL (ya implementado, continuar)
- [ ] Evaluar viabilidad de Selenium o Playwright en entorno portable sin permisos de administrador
- [ ] Implementar descarga automática del PDF del día
- [ ] Integrar descarga al Tab 1 como opción alternativa al upload manual

---

## Etapa 3 — Clasificación semántica (NLP básico)
> Sin modelos de lenguaje. Clasificación por palabras clave de tipo normativo.

- [ ] Definir categorías: incompatibilidad, complementariedad, derogación, modificación
- [ ] Implementar clasificación automática de párrafos encontrados por categoría
- [ ] Agregar columna `tipo_relacion` a la tabla `resultados`
- [ ] Filtro por tipo de relación en Tab 3

---

## Etapa 4 — Integración con infraestructura CNCPS
> Requiere coordinación con área de Informática.

- [ ] Migración de SQLite a base de datos relacional en servidor institucional
- [ ] API o conector para alimentar tablero Power BI
- [ ] Sistema de alertas por publicación de nueva normativa relevante
- [ ] Documentación técnica para transferencia al equipo de IT

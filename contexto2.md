# Contexto del Proyecto: Sistema de Matching B2B

## Resumen del Proyecto

Este sistema es una plataforma B2B para la gestión de eventos empresariales, donde compradores y proveedores pueden ser emparejados (matches) según criterios como categorías, subcategorías, fechas de disponibilidad, palabras clave y descripciones. El sistema permite la administración de eventos, empresas, participantes, agendas de citas y la lógica de matching entre empresas.

### Arquitectura Actual
- **Backend en PHP** siguiendo el patrón MVC (Model-View-Controller).
- **Base de datos relacional** (MySQL) con tablas para eventos, empresas, matches, citas, etc.
- **Frontend** basado en vistas PHP, con uso de AJAX para cargar y gestionar datos dinámicamente.
- **Lógica de matching** centralizada en el modelo `Match.php`, que ahora almacena todos los datos relevantes de cada match directamente en la tabla `matches` (campos denormalizados).
- **Controladores** como `MatchController.php` y `EventController.php` exponen endpoints para la gestión de eventos y matches, y devuelven datos en formato adecuado para AJAX.
- **Vistas** en `/views/events/` y `/views/matches/` muestran la información y permiten la interacción del usuario.

### Cambios Recientes
- Eliminación de dependencias de vistas SQL para matches; toda la información relevante se almacena en la tabla `matches`.
- Refactorización de la lógica de generación de matches para poblar todos los campos denormalizados.
- Limpieza de código legado y separación estricta de responsabilidades según MVC.
- Uso de AJAX en el frontend para cargar y gestionar matches de forma eficiente y dinámica.

## Recomendaciones para Mantener y Mejorar el Código

1. **Mantener la separación MVC**
   - Toda la lógica de negocio debe residir en los modelos.
   - Los controladores solo deben orquestar la lógica y preparar datos para las vistas o endpoints AJAX.
   - Las vistas deben ser "dumb views": solo mostrar datos, sin lógica de negocio.

2. **Uso de AJAX para Interactividad**
   - Continuar usando AJAX para cargar, filtrar y actualizar matches y citas, evitando recargas completas de página.
   - Los endpoints deben devolver solo los datos necesarios en formato JSON.

3. **Documentar endpoints y modelos**
   - Mantener documentación clara de los endpoints disponibles y los datos que esperan/devuelven.
   - Documentar la estructura de la tabla `matches` y el significado de cada campo.

4. **Evitar lógica duplicada y SQL embebido**
   - Toda la lógica de matching y manipulación de datos debe estar en los modelos, no en controladores ni vistas.
   - Evitar consultas SQL directas en las vistas o controladores.

5. **Pruebas y validaciones**
   - Implementar pruebas unitarias para la lógica de matching y validaciones de datos.
   - Validar siempre los datos recibidos por AJAX en el backend.

6. **Escalabilidad y mantenibilidad**
   - Si la lógica de matching crece, considerar dividir el modelo `Match.php` en servicios o clases especializadas.
   - Mantener el código modular y bien comentado.

7. **Optimización de consultas**
   - Aprovechar los campos denormalizados para evitar joins costosos.
   - Indexar los campos más consultados en la tabla `matches`.

8. **Seguridad**
   - Usar tokens CSRF en todos los formularios y endpoints sensibles.
   - Validar roles y permisos en cada acción del controlador.

## Siguientes pasos sugeridos
- Documentar todos los endpoints AJAX y su uso esperado.
- Crear plantillas base para nuevas vistas "dumb" y controladores limpios.
- Implementar pruebas para la lógica de matching y endpoints críticos.
- Mantener este archivo actualizado con cambios arquitectónicos relevantes.

---

*Última actualización: 16 de junio de 2025*

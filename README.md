# B2B Conector

Sistema web para la gestión de eventos de networking empresarial, permitiendo la administración de empresas, participantes, categorías, matches, citas y más.

## Características principales
- Gestión de eventos empresariales
- Administración de empresas, asistentes y categorías
- Generación y gestión de matches y citas
- Panel de control con estadísticas
- Interfaz moderna y minimalista

## Requisitos
- PHP 7.4 o superior
- Servidor web (Apache, Nginx, etc.)
- MySQL/MariaDB
- Composer (opcional, si usas dependencias externas)

## Instalación
1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/tu-repo.git
   ```
2. Copia el proyecto a tu servidor web (por ejemplo, `/var/www/html/b2b_conector`).
3. Configura la base de datos en `config/database.php`.
4. Ajusta otros parámetros en `config/config.php` según tu entorno.
5. Asegúrate de que la carpeta `uploads/` tenga permisos de escritura.
6. Accede a `http://localhost/b2b_conector/public` en tu navegador.

## Estructura del proyecto
- `controllers/` — Lógica de controladores (MVC)
- `models/` — Modelos de datos y lógica de negocio
- `views/` — Vistas y plantillas HTML/PHP
- `public/` — Archivos públicos (index.php, assets, imágenes, JS, CSS)
- `config/` — Configuración de base de datos y parámetros globales
- `utils/` — Utilidades y helpers
- `uploads/` — Archivos subidos (logos, etc.)
- `logs/` — Archivos de log

## Uso básico
- Inicia sesión como administrador para crear y gestionar eventos.
- Agrega empresas, asistentes y categorías desde el panel.
- Genera matches y programa citas automáticamente.
- Visualiza estadísticas y exporta información desde el dashboard.

## Personalización
- Los estilos están en `public/assets/css/` y se organizan por módulos y componentes.
- Puedes modificar las vistas en la carpeta `views/` para adaptar la interfaz.

## Licencia
Este proyecto es privado y para uso interno. Si deseas usarlo o modificarlo, contacta al autor.

---

Desarrollado por Str Nu.

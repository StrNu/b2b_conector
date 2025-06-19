Este proyecto consiste en una plataforma B2B (Business to Business) diseñada para facilitar la conexión entre proveedores y compradores mediante la organización de eventos de networking. La plataforma permite generar citas automáticas basadas en coincidencias (matches) entre las necesidades de los compradores y las ofertas de los proveedores.


Estamos trabajando con Arquitectura MVC (Model-View-Controller):
Usa una estructura clara basada en el patrón MVC.
Organiza los archivos en las carpetas models, views y controllers.
Incluye una carpeta adicional llamada config para la configuración de la base de datos y otros ajustes globales.
Limpieza y Claridad del Código:
Escribe código limpio, legible y bien documentado.
Usa nombres descriptivos para variables, funciones y clases.
Evita el uso excesivo de frameworks CSS como Tailwind. Prefiere un diseño simple y modular con CSS personalizado o Bootstrap (solo si es necesario).
Base de Datos:
Utiliza MySQL como motor de base de datos.
Implementa consultas SQL optimizadas y evita consultas redundantes.
Define relaciones entre tablas usando foreign keys y sigue las mejores prácticas de normalización.
Autenticación y Seguridad:
Implementa un sistema de autenticación seguro (login/logout).
Usa hashing seguro (bcrypt) para almacenar contraseñas.
Protege contra ataques comunes como SQL Injection, XSS y CSRF.
Implementa sesiones seguras con session.use_strict_mode habilitado.

Evita duplicar código; centraliza la lógica común en helpers o servicios.
Pruebas y Depuración:
Incluye comentarios en el código explicando funcionalidades clave.
Proporciona ejemplos de pruebas básicas para validar el funcionamiento del sistema.
Modernidad y Rendimiento:
Usa características modernas de PHP (PHP 8.x si es posible).
Optimiza consultas SQL y minimiza el número de solicitudes a la base de datos.
Implementa técnicas de caché si es necesario.


En caso de los compradores, ellos podrán registrar sus productos/servicios que serán tomados de las categorías y subcategorías del evento, así como los datos de cantidad, presupuesto. etc.
Asignación de categorías a eventos

En caso de los proveedores, podrán registrar sus ofertas, indicando de la lista de categorías y subcategorías del evento cuáles son los productos/servicios que ofrecen.
Requisitos de compradores y ofertas de proveedores

en caso de que el proveedor y comprador coincidan en una categoría/subcategoría, se hará un match
Emparejamientos (Matches)

Tanto compradores como vendedores, podrán elegir durante el registro los nombres de los asistentes al evento y las fechas en que asistirán.


Seccion matches
Propósito
La función de Matches ayuda a los organizadores de eventos a conectar compradores con proveedores que comparten intereses comerciales, agilizando el proceso de emparejamiento B2B durante los eventos.



Componentes Clave

1. Generación de Matches:

Las Matches se generan automáticamente según las categorías y subcategorías que los compradores están interesados ​​en comprar y que ofrecen los proveedores.
El sistema calcula un porcentaje de "nivel de coincidencia" según la adecuación de los requisitos del comprador a las ofertas de los proveedores.

Cada coincidencia muestra las empresas compradoras y proveedoras.
Muestra las categorías de productos coincidentes entre ellas.
Muestra los días de asistencia en los que ambas empresas estarán presentes.
Incluye un indicador de nivel de coincidencia.
nos muestra un botón para generar una cita



Con el match de productos  entre comprador y vendedor y tomando en cuenta los días de asistencia podremos hacer la generación automática de potenciales reuniones y generar una agenda con las reuniones de cada compradores, debemos de tener 2 botones en matches uno para hacerlo individual y otro para generar todos los match.

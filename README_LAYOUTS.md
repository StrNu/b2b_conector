# Sistema de Layouts Dinámicos - Guía de Integración

## 📁 Archivos Creados

```
controllers/
├── BaseController.php          # Controlador base con render()
├── AdminController.php         # Para admins normales
└── EventAdminController.php    # Para admins de eventos

views/
├── layouts/
│   ├── admin.php              # Layout para admins normales
│   └── event.php              # Layout para usuarios de eventos
└── events/
    └── view_content.php       # Vista limpia sin layout
```

## 🧪 Prueba Paso a Paso

### 1. Probar Layout Admin
```php
// En cualquier controlador existente, agregar:
require_once 'controllers/BaseController.php';

class MiController extends BaseController {
    public function index() {
        $data = [
            'pageTitle' => 'Mi Página',
            'moduleCSS' => 'mi-modulo',
            'items' => []
        ];
        $this->render('mi-vista/index', $data, 'admin');
    }
}
```

### 2. Probar Layout Event
```php
// Para eventos
$this->render('events/view_content', $data, 'event');
```

### 3. Layout Automático
```php
// Detecta automáticamente admin vs event
$this->render('mi-vista', $data, 'auto');
```

## 🔄 Migración Gradual

### Opción A: Migrar por controlador
1. Extender `BaseController`
2. Cambiar `include VIEW_DIR . '/shared/header.php'` por `$this->render()`
3. Mover contenido HTML a vistas separadas

### Opción B: Mantener compatibilidad
```php
// En controladores existentes
if (method_exists($this, 'render')) {
    $this->render('vista', $data, 'auto');
} else {
    // Método actual con include
    include VIEW_DIR . '/shared/header.php';
    include VIEW_DIR . '/vista.php';
    include VIEW_DIR . '/shared/footer.php';
}
```

## 🎯 Ejemplo Completo: EventController

### Antes:
```php
// EventController::view()
include VIEW_DIR . '/shared/header.php';
// contenido
include VIEW_DIR . '/shared/footer.php';
```

### Después:
```php
class EventController extends BaseController {
    public function view($id) {
        // ... lógica existente ...
        
        $data = [
            'pageTitle' => $event->getEventName(),
            'moduleCSS' => 'events',
            'event' => $event,
            'participants' => $participants
        ];
        
        // Layout automático según usuario
        $this->render('events/view_content', $data, 'auto');
    }
}
```

## ⚙️ Variables Disponibles en Layouts

```php
$data = [
    'pageTitle' => 'Título de la página',
    'moduleCSS' => 'nombre-modulo',     // Carga assets/css/modules/nombre-modulo.css
    'moduleJS' => 'nombre-modulo',      // Carga assets/js/modules/nombre-modulo.js
    'additionalCSS' => ['extra1', 'extra2'],
    'additionalJS' => ['extra1.js', 'extra2.js'],
    'breadcrumbs' => [
        ['title' => 'Inicio', 'url' => '/dashboard'],
        ['title' => 'Actual']
    ]
];
```

## 🚨 Validación

### Verificar que funciona:
1. **Admin normal**: Login normal → Debe usar layout admin con sidebar
2. **Event admin**: Login evento → Debe usar layout event sin sidebar
3. **CSS/JS**: Todos los assets deben cargar correctamente
4. **Breadcrumbs**: Deben aparecer en ambos layouts

### Rollback si hay problemas:
1. Cambiar `$this->render()` de vuelta a `include`
2. Los archivos originales no se tocaron
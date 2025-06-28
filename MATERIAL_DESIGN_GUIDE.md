# Guía de Material Design 3 - B2B Conector

## 🎯 Resumen de la Implementación

B2B Conector ahora utiliza **Material Design 3** con una paleta de colores **purple/violet** como tema principal. Todas las páginas que usen el layout `admin.php` ahora tienen automáticamente el nuevo diseño.

## 📁 Archivos Actualizados

### ✅ Layout Principal
- **`views/layouts/admin.php`** - Completamente rediseñado con Material Design 3
- **`views/shared/assets-refactored.php`** - Incluye `material-theme.css`
- **`config/config.php`** - Incluye configuración Material
- **`config/helpers.php`** - Carga helpers Material

### ✅ Nuevos Archivos
- **`config/material-config.php`** - Configuración y helpers Material Design 3
- **`public/assets/css/material-theme.css`** - Componentes Material Design 3

## 🎨 Componentes Material Design 3 Disponibles

### 1. Botones Material
```php
// Usando helpers (recomendado)
echo materialButton('Texto', 'filled', 'fas fa-icon');
echo materialButton('Texto', 'tonal', 'fas fa-icon');
echo materialButton('Texto', 'outlined', 'fas fa-icon');
echo materialButton('Texto', 'text', 'fas fa-icon');

// O directamente con CSS
<button class="btn-material btn-material--filled">
    <i class="fas fa-plus"></i> Crear
</button>
```

### 2. Cards Material
```php
// Usando helpers
echo materialCard('Título', 'Contenido', 'elevated', 'Acciones');
echo materialCard('Título', 'Contenido', 'filled');
echo materialCard('Título', 'Contenido', 'outlined');

// O directamente con CSS
<div class="card-material card-material--elevated">
    <div style="padding: 1.5rem;">Contenido</div>
</div>
```

### 3. Formularios Material
```html
<div class="textfield-material">
    <input class="textfield-material__input" type="text" id="campo" placeholder=" ">
    <label class="textfield-material__label" for="campo">Etiqueta</label>
    <div class="textfield-material__supporting-text">Texto de ayuda</div>
</div>
```

### 4. Navegación Material
- La navegación principal ya usa `nav-material`
- Dropdowns con efectos Material
- Links con estados hover/active Material

### 5. Hero Sections Material
```html
<div class="hero-material">
    <div class="container">
        <div class="hero-material__content">
            <h1 class="hero-material__title">Título</h1>
            <p class="hero-material__subtitle">Subtítulo</p>
        </div>
    </div>
</div>
```

### 6. FAB (Floating Action Button)
```html
<button class="fab-material" onclick="accion()">
    <i class="fas fa-plus"></i>
</button>
```

## 🛠️ Migración de Páginas Existentes

### Automática
Todas las páginas que usen:
```php
$this->render('vista/nombre', $data, 'admin');
```
Ya tienen Material Design 3 automáticamente.

### Manual (para mejorar la experiencia)

#### 1. Cambiar botones existentes:
```php
// Antes
<button class="btn btn--primary">Guardar</button>

// Después  
<?= materialButton('Guardar', 'filled', 'fas fa-save') ?>
```

#### 2. Cambiar cards existentes:
```php
// Antes
<div class="card">
    <div class="card__header"><h3>Título</h3></div>
    <div class="card__body">Contenido</div>
</div>

// Después
<?= materialCard('Título', 'Contenido', 'elevated') ?>
```

#### 3. Actualizar formularios:
```html
<!-- Antes -->
<div class="form-group">
    <label for="campo">Etiqueta</label>
    <input class="form-control" type="text" id="campo">
</div>

<!-- Después -->
<div class="textfield-material">
    <input class="textfield-material__input" type="text" id="campo" placeholder=" ">
    <label class="textfield-material__label" for="campo">Etiqueta</label>
</div>
```

## 🎯 Páginas de Prueba

### Para desarrolladores:
- **`/test-admin-layout.php`** - Testing completo del layout admin
- **`/demo-material.php`** - Demo completo Material Design 3
- **`/examples/material-usage.php`** - Ejemplos de uso real

### Para producción:
Las páginas normales del dashboard ya funcionan con Material Design 3:
- `/dashboard` - Panel principal
- `/events` - Gestión de eventos  
- `/companies` - Gestión de empresas
- `/matches` - Gestión de matches

## ⚙️ Configuración

### Habilitar/Deshabilitar Material Design 3:
```php
// En config/material-config.php
define('MATERIAL_DESIGN_ENABLED', true); // false para deshabilitar
```

### Personalizar colores:
```php
// En config/material-config.php
$materialConfig = [
    'primary_color' => '#9c27b0',    // Purple principal
    'secondary_color' => '#673ab7',   // Violet secundario
    'surface_color' => '#fef7ff',     // Superficie
];
```

## 🔧 Helpers Disponibles

```php
// Verificar si Material Design está habilitado
if (useMaterialDesign()) {
    // Lógica específica para Material
}

// Obtener clases CSS Material
$class = getMaterialClass('button', 'filled');
$class = getMaterialClass('card', 'elevated');

// Generar componentes
echo materialButton('Texto', 'variant', 'icon', 'attributes');
echo materialCard('Título', 'Contenido', 'variant', 'Acciones');
```

## 🎨 Paleta de Colores Material Design 3

### Primarios (Purple)
- `--md-primary-10: #21005d`
- `--md-primary-40: #6750a4` 
- `--md-primary-80: #d0bcff`
- `--md-primary-90: #eaddff`

### Secundarios (Violet)  
- `--color-secondary-400: #7e57c2`
- `--color-secondary-500: #673ab7`
- `--color-secondary-600: #5e35b1`

### Superficies
- `--md-surface-bright: #fef7ff`
- `--md-surface-container: #f1ecf4`

## 📱 Responsive Design

Material Design 3 está optimizado para:
- 📱 **Mobile**: 320px - 768px
- 📟 **Tablet**: 768px - 1024px  
- 🖥️ **Desktop**: 1024px+

## ✅ Beneficios del Nuevo Diseño

1. **🎨 Moderna**: Diseño actual siguiendo estándares Google
2. **📱 Responsive**: Perfecta adaptación a todos los dispositivos
3. **⚡ Rápida**: CSS optimizado y carga eficiente
4. **♿ Accesible**: Cumple estándares de accesibilidad
5. **🔧 Mantenible**: Código organizado y documentado

## 🚀 Próximos Pasos

1. **Probar** las páginas existentes con el nuevo diseño
2. **Migrar** gradualmente componentes específicos a Material
3. **Personalizar** colores o tipografías según necesidades
4. **Documentar** cualquier problema o mejora necesaria

---

¿Necesitas ayuda con la implementación? Revisa las páginas de prueba o consulta este documento.
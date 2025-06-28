<?php
// Vista de registro de compradores con diseño limpio y profesional
$pageTitle = 'Registro de Compradores - ' . (isset($event) ? $event->getEventName() : 'Evento');
$moduleCSS = 'public_registration';
$breadcrumbs = [
    ['title' => 'Evento', 'url' => BASE_URL . '/events/view/' . $eventId],
    ['title' => 'Registro de Compradores']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- CSS del proyecto -->
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/core.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/layouts.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/public_registration.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Poppins', sans-serif; margin: 0; padding: 0; background: #f8fafc;">

<!-- Header del evento -->
<header style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 1rem 0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-calendar-check" style="font-size: 1.5rem;"></i>
            <div>
                <h1 style="margin: 0; font-size: 1.25rem; font-weight: 600;">Panel de Eventos</h1>
                <?php if (isset($event)): ?>
                <p style="margin: 0; font-size: 0.875rem; opacity: 0.9;"><?= htmlspecialchars($event->getEventName()) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="<?= BASE_URL ?>" style="color: white; text-decoration: none; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; transition: background 0.2s;">
                <i class="fas fa-home"></i> Inicio
            </a>
        </div>
    </div>
</header>

<div class="registration-container">
    <!-- Header del formulario -->
    <div class="registration-header">
        <h1 class="registration-title">Registro para Compradores</h1>
        <p class="registration-subtitle">
            Complete el siguiente formulario para registrarse como comprador en este evento. 
            Podrá especificar sus requerimientos y productos de interés.
        </p>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])): ?>
        <div class="flash-messages">
            <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <?php
                    // Determinar el icono basado en el tipo de mensaje
                    $iconMap = [
                        'success' => 'check-circle',
                        'error' => 'exclamation-circle',
                        'danger' => 'exclamation-triangle',
                        'warning' => 'exclamation-triangle',
                        'info' => 'info-circle'
                    ];
                    $icon = isset($iconMap[$type]) ? $iconMap[$type] : 'info-circle';
                    ?>
                    <div class="flash-message flash-message--<?= htmlspecialchars($type) ?>">
                        <div class="flash-message__icon">
                            <i class="fas fa-<?= $icon ?>"></i>
                        </div>
                        <div class="flash-message__content">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['flash_messages']); ?>
    <?php endif; ?>

    <!-- Formulario principal -->
    <form action="<?= BASE_URL ?>/buyers_registration/<?= (int)$eventId ?>/store" method="POST" enctype="multipart/form-data" class="registration-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <!-- Sección: Información de la Empresa -->
        <section class="form-section">
            <div class="form-section-header">
                <i class="fas fa-building form-section-icon"></i>
                <h2 class="form-section-title">Información de la Empresa</h2>
            </div>
            
            <div class="form-grid form-grid--2-cols">
                <div class="form-field">
                    <label class="form-label form-label--required">Nombre de la Empresa</label>
                    <input type="text" name="company_name" class="form-input" required 
                           placeholder="Nombre de su empresa" value="<?= old('company_name') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label">Sitio Web</label>
                    <input type="url" name="website" class="form-input" 
                           placeholder="https://www.ejemplo.com" value="<?= old('website') ?>">
                </div>
                
                <div class="form-field form-field--full-width">
                    <label class="form-label form-label--required">Descripción de la Empresa</label>
                    <textarea name="description" class="form-textarea" required 
                              placeholder="Describe brevemente tu empresa, productos y servicios que ofreces"><?= old('description') ?></textarea>
                </div>
                
                <div class="form-field form-field--full-width">
                    <label class="form-label form-label--required">Palabras clave</label>
                    <input type="text" name="keywords" class="form-input" required
                           placeholder="Ej. acero inoxidable, ISO 9001, maquila textil" value="<?= old('keywords') ?>">
                    <div class="form-help-text">
                        Escribe las palabras clave separadas por comas. Ejemplo: acero inoxidable, ISO 9001, maquila textil
                    </div>
                </div>
                <!-- Certificaciones -->
                <div class="form-field form-field--full-width">
                    <label class="form-label">Certificaciones de Calidad y Gestión</label>
                    <div class="checkbox-group">
                        <?php 
                        $certifications = ['ISO 9001', 'ISO 14001', 'ISO 45001', 'ISO 22000', 'Six Sigma / Lean Six Sigma'];
                        $descriptions = [
                            'ISO 9001' => 'Gestión de calidad',
                            'ISO 14001' => 'Gestión ambiental', 
                            'ISO 45001' => 'Seguridad y salud ocupacional',
                            'ISO 22000' => 'Seguridad alimentaria',
                            'Six Sigma / Lean Six Sigma' => 'Mejora de procesos y eficiencia'
                        ];
                        ?>
                        <?php foreach ($certifications as $cert): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="certifications[]" value="<?= $cert ?>" 
                                       id="cert_<?= md5($cert) ?>" class="checkbox-input"
                                       <?= in_array($cert, $_POST['certifications'] ?? []) ? 'checked' : '' ?>>
                                <label for="cert_<?= md5($cert) ?>" class="checkbox-label">
                                    <strong><?= $cert ?></strong> – <?= $descriptions[$cert] ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-field" style="margin-top: 1rem;">
                        <label class="form-label">Otras certificaciones</label>
                        <input type="text" name="certifications_otros" class="form-input" 
                               placeholder="Especifique otras certificaciones" value="<?= old('certifications_otros') ?>">
                    </div>
                </div>
                <!-- Logo de la empresa -->
                <div class="form-field form-field--full-width">
                    <label class="form-label">Logo de la Empresa</label>
                    <div class="file-upload">
                        <div class="file-upload-area" onclick="document.getElementById('logo-upload').click()">
                            <button type="button" class="file-upload-button">
                                <i class="fas fa-cloud-upload-alt"></i> Seleccionar Archivo
                            </button>
                            <div class="file-upload-feedback" id="logo-upload-feedback">
                                Arrastre una imagen o haga clic para seleccionar
                            </div>
                        </div>
                        <input type="file" name="logo" accept="image/*" class="file-upload-input" id="logo-upload">
                    </div>
                    <div class="form-help-text">
                        Formatos soportados: JPG, PNG, GIF. Tamaño máximo: 5MB
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label">Ciudad</label>
                    <input type="text" name="city" class="form-input" 
                           placeholder="Ciudad" value="<?= old('city') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label">País</label>
                    <input type="text" name="country" class="form-input" 
                           placeholder="País" value="<?= old('country', 'México') ?>">
                </div>
            </div>
        </section>
        <!-- Sección: Datos de Contacto -->
        <section class="form-section">
            <div class="form-section-header">
                <i class="fas fa-user form-section-icon"></i>
                <h2 class="form-section-title">Datos de Contacto</h2>
            </div>
            
            <div class="form-grid form-grid--2-cols">
                <div class="form-field">
                    <label class="form-label form-label--required">Nombre</label>
                    <input type="text" name="contact_first_name" class="form-input" required 
                           placeholder="Nombre" value="<?= old('contact_first_name') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label form-label--required">Apellido</label>
                    <input type="text" name="contact_last_name" class="form-input" required 
                           placeholder="Apellido" value="<?= old('contact_last_name') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label form-label--required">Teléfono Celular</label>
                    <input type="tel" name="phone" class="form-input" required 
                           placeholder="+52 222 123 4567" value="<?= old('phone') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label form-label--required">Correo Electrónico</label>
                    <input type="email" name="email" class="form-input" required 
                           placeholder="correo@ejemplo.com" value="<?= old('email') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label">Ciudad</label>
                    <input type="text" name="contact_city" class="form-input" 
                           placeholder="Ciudad" value="<?= old('contact_city') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label">País</label>
                    <input type="text" name="contact_country" class="form-input" 
                           placeholder="País" value="<?= old('contact_country', 'México') ?>">
                </div>
            </div>
        </section>
        <!-- Sección: Datos para Registro de la Cuenta -->
        <section class="form-section">
            <div class="form-section-header">
                <i class="fas fa-id-card form-section-icon"></i>
                <h2 class="form-section-title">Datos para Registro de la Cuenta</h2>
            </div>
            
            <div class="form-grid form-grid--2-cols">
                <div class="form-field">
                    <label class="form-label form-label--required">Correo Electrónico de la Cuenta</label>
                    <input type="email" name="username" class="form-input" required 
                           placeholder="cuenta@ejemplo.com" value="<?= old('username') ?>">
                </div>
                
                <div class="form-field">
                    <label class="form-label form-label--required">Contraseña</label>
                    <input type="password" name="password" class="form-input" required 
                           placeholder="********">
                </div>
                
                <div class="form-field">
                    <label class="form-label form-label--required">Repetir Contraseña</label>
                    <input type="password" name="password_repeat" class="form-input" required 
                           placeholder="********">
                </div>
            </div>
        </section>
        <!-- Sección: Registro de Asistentes al Evento -->
        <section class="form-section">
            <div class="form-section-header">
                <i class="fas fa-users form-section-icon"></i>
                <h2 class="form-section-title">Registro de Asistentes al Evento</h2>
            </div>
            
            <div class="checkbox-item" style="margin-bottom: 1.5rem;">
                <input type="checkbox" name="is_self_attendee" class="checkbox-input" id="is_self_attendee">
                <label for="is_self_attendee" class="checkbox-label">
                    Soy la misma persona que está llenando este formulario
                </label>
            </div>
            
            <div id="assistants-list">
                <div class="form-grid form-grid--2-cols assistant-item">
                    <div class="form-field">
                        <label class="form-label form-label--required">Nombre</label>
                        <input type="text" name="assistants[0][first_name]" class="form-input" required 
                               id="assistant_first_name_0" placeholder="Nombre" value="<?= old('assistants[0][first_name]') ?>">
                    </div>
                    
                    <div class="form-field">
                        <label class="form-label form-label--required">Apellido</label>
                        <input type="text" name="assistants[0][last_name]" class="form-input" required 
                               id="assistant_last_name_0" placeholder="Apellido" value="<?= old('assistants[0][last_name]') ?>">
                    </div>
                    
                    <div class="form-field">
                        <label class="form-label form-label--required">Teléfono Celular</label>
                        <input type="tel" name="assistants[0][phone]" class="form-input" required 
                               id="assistant_phone_0" placeholder="+52 222 123 4567" value="<?= old('assistants[0][phone]') ?>">
                    </div>
                    
                    <div class="form-field">
                        <label class="form-label form-label--required">Correo Electrónico</label>
                        <input type="email" name="assistants[0][email]" class="form-input" required 
                               id="assistant_email_0" placeholder="correo@ejemplo.com" value="<?= old('assistants[0][email]') ?>">
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn-secondary" onclick="addAssistant()">
                <i class="fas fa-plus"></i> Agregar otro asistente
            </button>
        </section>
        <!-- Sección: Productos o Servicios de Interés -->
        <section class="form-section">
            <div class="form-section-header">
                <i class="fas fa-box form-section-icon"></i>
                <h2 class="form-section-title">Productos o Servicios de Interés</h2>
            </div>
            
            <p class="form-help-text" style="margin-bottom: 1.5rem;">
                <strong>Seleccione al menos un producto o servicio de interés.</strong> Puede indicar un presupuesto aproximado y la cantidad requerida.
            </p>
            
            <?php 
            // Filter categories to only show active ones
            $activeCategories = array_filter($categories, function($cat) {
                return isset($cat['is_active']) && $cat['is_active'] == 1;
            });
            ?>
            <?php if (!empty($activeCategories)): ?>
                <div class="tabs-container">
                    <div class="tabs-list" id="requirement-tabs">
                        <?php $firstTab = true; foreach ($activeCategories as $cat): ?>
                            <?php 
                            // Check if this category has active subcategories
                            $activeSubcategories = [];
                            if (!empty($subcategories[$cat['event_category_id']])) {
                                $activeSubcategories = array_filter($subcategories[$cat['event_category_id']], function($sub) {
                                    return isset($sub['is_active']) && $sub['is_active'] == 1;
                                });
                            }
                            ?>
                            <?php if (!empty($activeSubcategories)): ?>
                            <button type="button" class="tab-button <?= $firstTab ? 'tab-button--active' : '' ?>" 
                                    data-tab="tab-<?= (int)$cat['event_category_id'] ?>" 
                                    onclick="showTab('tab-<?= (int)$cat['event_category_id'] ?>', this)">
                                <?= htmlspecialchars($cat['name']) ?>
                            </button>
                            <?php $firstTab = false; endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php $firstTab = true; foreach ($activeCategories as $cat): ?>
                        <?php 
                        // Filter subcategories to only show active ones
                        $activeSubcategories = [];
                        if (!empty($subcategories[$cat['event_category_id']])) {
                            $activeSubcategories = array_filter($subcategories[$cat['event_category_id']], function($sub) {
                                return isset($sub['is_active']) && $sub['is_active'] == 1;
                            });
                        }
                        ?>
                        <?php if (!empty($activeSubcategories)): ?>
                        <div class="tab-content" id="tab-<?= (int)$cat['event_category_id'] ?>" 
                             style="<?php if (!$firstTab) echo 'display:none;'; ?>">
                            <div class="requirements-table-container">
                                <table class="requirements-table">
                                    <thead>
                                        <tr>
                                            <th>Requerimiento</th>
                                            <th>Presupuesto USD</th>
                                            <th>Cantidad</th>
                                            <th>Unidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($activeSubcategories as $sub): ?>
                                        <tr>
                                            <td>
                                                <div class="checkbox-item">
                                                    <input type="checkbox" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][selected]" 
                                                           value="1" id="req_<?= $sub['event_subcategory_id'] ?>" class="checkbox-input req-checkbox">
                                                    <label for="req_<?= $sub['event_subcategory_id'] ?>" class="checkbox-label">
                                                        <?= htmlspecialchars($sub['name']) ?>
                                                    </label>
                                                </div>
                                            </td>
                                                <td>
                                                    <div class="currency-input">
                                                        <span class="currency-symbol">$</span>
                                                        <input type="number" step="0.01" min="0" 
                                                               name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][budget]" 
                                                               class="form-input req-budget" disabled placeholder="0.00">
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" min="1" 
                                                           name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][quantity]" 
                                                           class="form-input req-qty" disabled placeholder="1">
                                                </td>
                                                <td>
                                                    <select name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][unit]" 
                                                            class="form-select req-unit" disabled>
                                                        <option value="">Selecciona</option>
                                                        <option value="pieza">Pieza</option>
                                                        <option value="kg">Kg</option>
                                                        <option value="ton">Tonelada</option>
                                                        <option value="servicio">Servicio</option>
                                                        <option value="otro">Otro</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php $firstTab = false; endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="form-help-text">No hay categorías configuradas para este evento.</p>
            <?php endif; ?>
        </section>
        <!-- Sección: Días de Asistencia -->
        <section class="form-section">
            <div class="form-section-header">
                <i class="fas fa-calendar-alt form-section-icon"></i>
                <h2 class="form-section-title">Días de Asistencia</h2>
            </div>
            
            <p class="form-help-text" style="margin-bottom: 1.5rem;">
                <strong>Seleccione al menos un día de asistencia al evento.</strong>
            </p>
            
            <?php if (isset($eventDays) && !empty($eventDays)): ?>
                <div class="checkbox-group">
                    <?php foreach ($eventDays as $day): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" name="attendance_days[]" value="<?= htmlspecialchars($day) ?>" 
                                   id="day_<?= $day ?>" class="checkbox-input">
                            <label for="day_<?= $day ?>" class="checkbox-label">
                                <?= htmlspecialchars(date('d/m/Y', strtotime($day))) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php
                // Calcular días del evento si no están disponibles
                if (isset($event) && $event->getStartDate() && $event->getEndDate()) {
                    $startDate = new DateTime($event->getStartDate());
                    $endDate = new DateTime($event->getEndDate());
                    $interval = $startDate->diff($endDate);
                    $totalDays = $interval->days + 1;
                    $eventDays = [];
                    $currentDate = clone $startDate;
                    for ($i = 0; $i < $totalDays; $i++) {
                        $eventDays[] = $currentDate->format('Y-m-d');
                        $currentDate->modify('+1 day');
                    }
                ?>
                    <div class="checkbox-group">
                        <?php foreach ($eventDays as $day): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="attendance_days[]" value="<?= htmlspecialchars($day) ?>" 
                                       id="day_<?= $day ?>" class="checkbox-input">
                                <label for="day_<?= $day ?>" class="checkbox-label">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($day))) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php } else { ?>
                    <p class="form-help-text">No hay fechas configuradas para este evento.</p>
                <?php } ?>
            <?php endif; ?>
        </section>

        <!-- Botón de envío -->
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Registrarse como Comprador
            </button>
        </div>
    </form>
</div>
<script>
// JavaScript para funcionalidades del formulario
document.addEventListener('DOMContentLoaded', function() {
    let assistantIndex = 1;
    
    // Función para agregar asistente
    window.addAssistant = function() {
        const list = document.getElementById('assistants-list');
        const assistantCount = list.querySelectorAll('.assistant-item').length;
        
        const div = document.createElement('div');
        div.className = 'form-grid form-grid--2-cols assistant-item';
        div.style.marginTop = '1.5rem';
        div.innerHTML = `
            <div class="form-field">
                <label class="form-label form-label--required">Nombre</label>
                <input type="text" name="assistants[${assistantIndex}][first_name]" class="form-input" required placeholder="Nombre">
            </div>
            <div class="form-field">
                <label class="form-label form-label--required">Apellido</label>
                <input type="text" name="assistants[${assistantIndex}][last_name]" class="form-input" required placeholder="Apellido">
            </div>
            <div class="form-field">
                <label class="form-label form-label--required">Teléfono Celular</label>
                <input type="tel" name="assistants[${assistantIndex}][phone]" class="form-input" required placeholder="+52 222 123 4567">
            </div>
            <div class="form-field">
                <label class="form-label form-label--required">Correo Electrónico</label>
                <input type="email" name="assistants[${assistantIndex}][email]" class="form-input" required placeholder="correo@ejemplo.com">
            </div>
        `;
        list.appendChild(div);
        assistantIndex++;
    };

    // Función para mostrar/ocultar asistente si es la misma persona
    const selfAttendeeCheckbox = document.getElementById('is_self_attendee');
    if (selfAttendeeCheckbox) {
        selfAttendeeCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            const contactFirstName = document.querySelector('input[name="contact_first_name"]').value;
            const contactLastName = document.querySelector('input[name="contact_last_name"]').value;
            const contactPhone = document.querySelector('input[name="phone"]').value;
            const contactEmail = document.querySelector('input[name="email"]').value;
            
            document.getElementById('assistant_first_name_0').value = isChecked ? contactFirstName : '';
            document.getElementById('assistant_last_name_0').value = isChecked ? contactLastName : '';
            document.getElementById('assistant_phone_0').value = isChecked ? contactPhone : '';
            document.getElementById('assistant_email_0').value = isChecked ? contactEmail : '';
        });
    }

    // Función para cambiar tabs
    window.showTab = function(tabId, button) {
        // Ocultar todos los contenidos
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        // Mostrar el contenido seleccionado
        document.getElementById(tabId).style.display = 'block';
        
        // Actualizar estado de botones
        document.querySelectorAll('#requirement-tabs .tab-button').forEach(btn => {
            btn.classList.remove('tab-button--active');
        });
        button.classList.add('tab-button--active');
    };

    // Función para mostrar nombre del archivo seleccionado
    const logoUploadInput = document.getElementById('logo-upload');
    const logoUploadFeedback = document.getElementById('logo-upload-feedback');
    
    if (logoUploadInput && logoUploadFeedback) {
        logoUploadInput.addEventListener('change', function(event) {
            if (event.target.files && event.target.files.length > 0) {
                logoUploadFeedback.textContent = event.target.files[0].name;
            } else {
                logoUploadFeedback.textContent = 'Arrastre una imagen o haga clic para seleccionar';
            }
        });
    }

    // Habilitar/Deshabilitar inputs según el checkbox para requerimientos
    document.querySelectorAll('.req-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const row = checkbox.closest('tr');
            const budgetInput = row.querySelector('.req-budget');
            const qtyInput = row.querySelector('.req-qty');
            const unitSelect = row.querySelector('.req-unit');
            
            if (budgetInput) budgetInput.disabled = !checkbox.checked;
            if (qtyInput) qtyInput.disabled = !checkbox.checked;
            if (unitSelect) unitSelect.disabled = !checkbox.checked;
        });
    });

    // Validación del formulario
    const form = document.querySelector('.registration-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            let errorMessages = [];

            // Validar que se seleccione al menos una subcategoría
            const checkedRequirements = document.querySelectorAll('.req-checkbox:checked');
            if (checkedRequirements.length === 0) {
                hasErrors = true;
                errorMessages.push('Debe seleccionar al menos un producto o servicio de interés.');
            }

            // Validar que se seleccione al menos un día de asistencia
            const checkedDays = document.querySelectorAll('input[name="attendance_days[]"]:checked');
            if (checkedDays.length === 0) {
                hasErrors = true;
                errorMessages.push('Debe seleccionar al menos un día de asistencia al evento.');
            }

            // Mostrar errores si los hay
            if (hasErrors) {
                e.preventDefault();
                alert('Por favor corrija los siguientes errores:\n\n' + errorMessages.join('\n'));
                return false;
            }
        });
    }
});
</script>

<!-- Footer -->
<footer style="background: #1e293b; color: white; padding: 2rem 0; margin-top: 4rem;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem; text-align: center;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <p style="margin: 0; font-weight: 600;">B2B Conector v1.0.0</p>
                <p style="margin: 0; font-size: 0.875rem; opacity: 0.8;">Plataforma de registro para eventos B2B</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="#" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.875rem;">Ayuda</a>
                <a href="#" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.875rem;">Privacidad</a>
                <a href="#" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.875rem;">Términos</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>

<?php
// Helper function para obtener valores anteriores del formulario
function old($name, $default = '') {
    if (strpos($name, '.') !== false) {
        // Handle array notation like "assistants[0][first_name]"
        $parts = explode('[', str_replace(']', '', $name));
        $key = $parts[0];
        if (count($parts) >= 3) {
            $index = $parts[1];
            $field = $parts[2];
            return isset($_POST[$key][$index][$field]) ? htmlspecialchars($_POST[$key][$index][$field]) : $default;
        }
    }
    return isset($_POST[$name]) ? htmlspecialchars($_POST[$name]) : $default;
}
?>

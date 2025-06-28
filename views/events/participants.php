<?php 
// Vista de participants con Material Design 3
if (file_exists(CONFIG_DIR . '/material-config.php')) {
    require_once CONFIG_DIR . '/material-config.php';
}

// Fallback functions if Material Design helpers are not available
if (!function_exists('materialButton')) {
    function materialButton($text, $variant = 'filled', $icon = '', $attributes = '', $size = '') {
        $class = 'btn btn-primary';
        if ($variant === 'outlined') $class = 'btn btn-secondary';
        if ($variant === 'tonal') $class = 'btn btn-info';
        if ($size === 'small') $class .= ' btn-sm';
        return '<button class="' . $class . '" ' . $attributes . '>' . $text . '</button>';
    }
}

if (!function_exists('materialCard')) {
    function materialCard($title, $content, $variant = 'elevated', $actions = '') {
        return '<div class="card">
                    <div class="card-header"><h5>' . $title . '</h5></div>
                    <div class="card-body">' . $content . '</div>
                    ' . ($actions ? '<div class="card-footer">' . $actions . '</div>' : '') . '
                </div>';
    }
}

if (!function_exists('displayFlashMessages')) {
    function displayFlashMessages() {
        include(VIEW_DIR . '/shared/notifications.php');
    }
}

if (!function_exists('isEventUserAuthenticated')) {
    function isEventUserAuthenticated() {
        return false;
    }
}

$pageTitle = 'Gestión de Asistentes del Evento';
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Eventos', 'url' => BASE_URL . '/events'],
    ['title' => 'Asistentes']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Gestión de Asistentes del Evento</h1>
            <p class="page-subtitle">Administra y gestiona todos los asistentes del evento</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-building"></i> Agregar empresa',
                'filled',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . $eventId . '/create-company\'"'
            ) ?>
            <?php
            if (!isset($eventId) || empty($eventId)) {
                echo '<div class="alert alert-danger">Error: No se encontró el ID del evento para el botón de regreso.</div>';
                $eventId = 0; // fallback para evitar errores de sintaxis
            }
            ?>
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . $eventId . '\'"'
            ) ?>
        </div>
    </div>
    <!-- Flash Messages are handled by the admin layout -->

    <!-- Add Participant Form -->
    <div class="add-participant-section">
        <?php 
        // Preparar variables para el formulario
        $formFirstName = htmlspecialchars($_SESSION['form_data']['first_name'] ?? '');
        $formLastName = htmlspecialchars($_SESSION['form_data']['last_name'] ?? '');
        $formEmail = htmlspecialchars($_SESSION['form_data']['email'] ?? '');
        $formPhone = htmlspecialchars($_SESSION['form_data']['mobile_phone'] ?? '');
        $formCompanyId = $_SESSION['form_data']['company_id'] ?? '';
        
        $errorFirstName = $_SESSION['validation_errors']['first_name'] ?? '';
        $errorLastName = $_SESSION['validation_errors']['last_name'] ?? '';
        $errorEmail = $_SESSION['validation_errors']['email'] ?? '';
        $errorCompanyId = $_SESSION['validation_errors']['company_id'] ?? '';
        
        ob_start();
        ?>
            <form action="<?= BASE_URL ?>/events/addParticipant/<?= $eventId ?>" method="POST" class="participant-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-grid">
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="first_name" class="textfield-material__input" 
                                   required placeholder=" " value="<?= $formFirstName ?>">
                            <label class="textfield-material__label">Nombre *</label>
                        </div>
                        <?php if ($errorFirstName): ?>
                            <div class="textfield-material__supporting-text textfield-material__supporting-text--error"><?= $errorFirstName ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="last_name" class="textfield-material__input" 
                                   required placeholder=" " value="<?= $formLastName ?>">
                            <label class="textfield-material__label">Apellido *</label>
                        </div>
                        <?php if ($errorLastName): ?>
                            <div class="textfield-material__supporting-text textfield-material__supporting-text--error"><?= $errorLastName ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="email" name="email" class="textfield-material__input" 
                                   required placeholder=" " value="<?= $formEmail ?>">
                            <label class="textfield-material__label">Email *</label>
                        </div>
                        <?php if ($errorEmail): ?>
                            <div class="textfield-material__supporting-text textfield-material__supporting-text--error"><?= $errorEmail ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="mobile_phone" class="textfield-material__input" 
                                   placeholder=" " value="<?= $formPhone ?>">
                            <label class="textfield-material__label">Teléfono</label>
                        </div>
                    </div>
                    
                    <div class="form-field form-field--wide">
                        <div class="textfield-material">
                            <select name="company_id" class="textfield-material__input" required>
                                <option value="">Seleccione una empresa</option>
                                <?php foreach ($companies as $company): ?>
                                    <?php $selected = ($formCompanyId == $company['company_id']) ? 'selected' : ''; ?>
                                    <option value="<?= $company['company_id'] ?>" <?= $selected ?>><?= htmlspecialchars($company['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="textfield-material__label">Empresa *</label>
                        </div>
                        <?php if ($errorCompanyId): ?>
                            <div class="textfield-material__supporting-text textfield-material__supporting-text--error"><?= $errorCompanyId ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <?= materialButton(
                        '<i class="fas fa-plus"></i> Agregar Asistente',
                        'filled',
                        '',
                        'type="submit"'
                    ) ?>
                </div>
            </form>
        <?php 
        $formContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-user-plus"></i> Agregar Nuevo Asistente',
            $formContent,
            'outlined'
        );
        ?>
        <?php unset($_SESSION['form_data'], $_SESSION['validation_errors']); ?>
    </div>
    <!-- Search Section -->
    <div class="search-section">
        <?= materialCard(
            '<i class="fas fa-search"></i> Buscar Asistentes',
            '
            <div class="search-field">
                <div class="textfield-material">
                    <input type="text" id="searchParticipant" class="textfield-material__input" placeholder=" ">
                    <label class="textfield-material__label">Buscar por nombre, apellido o email...</label>
                </div>
                <div class="textfield-material__supporting-text">
                    <i class="fas fa-info-circle"></i>
                    Búsqueda en tiempo real
                </div>
            </div>',
            'outlined'
        ) ?>
    </div>

    <!-- Participants Table -->
    <div class="participants-section">
        <?php if (!empty($participants)): ?>
            <?php 
            ob_start(); 
            ?>
                <div class="table-responsive">
                    <table id="participantsTable" class="table-material">
                        <thead class="table-material__header">
                            <tr>
                                <th class="table-material__cell table-material__cell--header">Nombre</th>
                                <th class="table-material__cell table-material__cell--header">Apellido</th>
                                <th class="table-material__cell table-material__cell--header">Email</th>
                                <th class="table-material__cell table-material__cell--header">Teléfono</th>
                                <th class="table-material__cell table-material__cell--header">Empresa</th>
                                <th class="table-material__cell table-material__cell--header">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $a): ?>
                                <tr class="table-material__row">
                                    <td class="table-material__cell">
                                        <div class="participant-info">
                                            <div class="participant-info__name"><?= htmlspecialchars($a['first_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="participant-info">
                                            <div class="participant-info__name"><?= htmlspecialchars($a['last_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="contact-info">
                                            <i class="fas fa-envelope"></i>
                                            <?= htmlspecialchars($a['email']) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="contact-info">
                                            <i class="fas fa-phone"></i>
                                            <?= htmlspecialchars($a['mobile_phone'] ?? 'N/A') ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="company-info">
                                            <div class="company-info__name"><?= htmlspecialchars($a['company_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="action-buttons">
                                            <?= materialButton(
                                                '<i class="fas fa-edit"></i>',
                                                'tonal',
                                                '',
                                                'onclick="window.location.href=\'' . BASE_URL . '/events/editParticipant/' . $eventId . '/' . $a['assistant_id'] . '\'" title="Editar asistente"',
                                                'small'
                                            ) ?>
                                            <form action="<?= BASE_URL ?>/events/deleteParticipant/<?= $eventId ?>/<?= $a['assistant_id'] ?>" method="POST" 
                                                  onsubmit="return confirm('¿Eliminar este asistente?');" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <?= materialButton(
                                                    '<i class="fas fa-trash"></i>',
                                                    'outlined',
                                                    '',
                                                    'type="submit" title="Eliminar asistente"',
                                                    'small'
                                                ) ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php 
            $tableContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-users"></i> Lista de Asistentes (' . count($participants) . ')',
                $tableContent,
                'elevated'
            );
            ?>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state-container">
                <?= materialCard(
                    '',
                    '
                    <div class="empty-state">
                        <div class="empty-state__icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="empty-state__title">No hay asistentes registrados</h3>
                        <p class="empty-state__subtitle">
                            No se encontraron asistentes registrados para este evento.
                        </p>
                    </div>',
                    'outlined'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchParticipant');
    const table = document.getElementById('participantsTable');
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>

<style>
/* Participants Material Design 3 styles */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.page-header__content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.page-subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    font-size: 1rem;
}

.page-header__actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
    flex-wrap: wrap;
}

.add-participant-section,
.search-section,
.participants-section {
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-field--wide {
    grid-column: 1 / -1;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--md-outline-variant);
}

.search-field {
    max-width: 500px;
}

.textfield-material__supporting-text--error {
    color: var(--md-error-40);
}

.table-responsive {
    overflow-x: auto;
    min-width: 100%;
}

.table-material {
    width: 100%;
    border-collapse: collapse;
    background: var(--md-surface);
    min-width: 700px;
}

.table-material__header {
    background: var(--md-surface-container);
}

.table-material__cell {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--md-outline-variant);
    vertical-align: top;
}

.table-material__cell--header {
    font-weight: 600;
    color: var(--md-on-surface-variant);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-material__row:hover {
    background: var(--md-surface-container-lowest);
}

.participant-info__name {
    font-weight: 600;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.company-info__name {
    font-weight: 500;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.contact-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
}

.contact-info i {
    font-size: 0.75rem;
    color: var(--md-primary-40);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.action-buttons form {
    display: inline;
    margin: 0;
}

.action-buttons .btn-material--outlined {
    border-color: var(--md-error-40);
    color: var(--md-error-40);
}

.action-buttons .btn-material--outlined:hover {
    background: var(--md-error-container);
    border-color: var(--md-error-40);
    color: var(--md-on-error-container);
}

.action-buttons .btn-material--tonal {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.action-buttons .btn-material--tonal:hover {
    background: var(--md-primary-container-hover, #dde5ff);
    transform: scale(1.02);
}

/* Notification styles are now loaded globally via components.css */

.empty-state-container {
    display: flex;
    justify-content: center;
    margin: 3rem 0;
}

.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state__icon {
    font-size: 4rem;
    color: var(--md-outline);
    margin-bottom: 1.5rem;
}

.empty-state__title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.empty-state__subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-header__actions {
        flex-direction: column;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .table-material__cell {
        padding: 0.75rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
// JavaScript para manejar notificaciones
document.addEventListener('DOMContentLoaded', function() {
    // Auto-cerrar notificaciones después de 5 segundos
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 5000);
    });
    
    // Manejar clics en botones de cerrar
    document.querySelectorAll('.notification-close').forEach(button => {
        button.addEventListener('click', function() {
            const notification = this.closest('.notification');
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        });
    });
});
</script>

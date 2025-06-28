<?php 
// Vista de edit participant con Material Design 3
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

$pageTitle = 'Editar Participante';
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/dashboard'],
    ['title' => 'Eventos', 'url' => BASE_URL . '/events'],
    ['title' => 'Participantes', 'url' => BASE_URL . '/events/participants/' . $eventId],
    ['title' => 'Editar']
];
?>

<?php if (!isset($assistant) || !$assistant): ?>
    <div class="content-area">
        <div class="error-message">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="error-title">Error: Participante no encontrado</h2>
            <p class="error-subtitle">No se encontró el participante a editar. Por favor, regrese al listado.</p>
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Listado',
                'filled',
                '',
                'onclick="window.history.back();"'
            ) ?>
        </div>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Editar Participante</h1>
            <p class="page-subtitle">Modifica la información del participante registrado</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver a Participantes',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/participants/' . $eventId . '\'"'
            ) ?>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Edit Form -->
    <div class="edit-form-container">
        <form action="<?= BASE_URL ?>/events/editParticipant/<?= $eventId ?>/<?= $assistant->getId() ?>" 
              method="POST" class="participant-edit-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <!-- Personal Information Section -->
            <?php 
            ob_start();
            ?>
                <div class="form-grid">
                    <div class="form-field">
                        <div class="textfield-material <?= isset($_SESSION['validation_errors']['first_name']) ? 'textfield-material--error' : '' ?>">
                            <input type="text" name="first_name" class="textfield-material__input" 
                                   required placeholder=" " value="<?= htmlspecialchars($assistant->getFirstName()) ?>">
                            <label class="textfield-material__label">Nombre *</label>
                            <?php if (isset($_SESSION['validation_errors']['first_name'])): ?>
                                <div class="textfield-material__supporting-text textfield-material__supporting-text--error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= $_SESSION['validation_errors']['first_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material <?= isset($_SESSION['validation_errors']['last_name']) ? 'textfield-material--error' : '' ?>">
                            <input type="text" name="last_name" class="textfield-material__input" 
                                   required placeholder=" " value="<?= htmlspecialchars($assistant->getLastName()) ?>">
                            <label class="textfield-material__label">Apellido *</label>
                            <?php if (isset($_SESSION['validation_errors']['last_name'])): ?>
                                <div class="textfield-material__supporting-text textfield-material__supporting-text--error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= $_SESSION['validation_errors']['last_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php 
            $personalContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-user"></i> Información Personal',
                $personalContent,
                'elevated'
            );
            ?>

            <!-- Contact Information Section -->
            <?php 
            ob_start();
            ?>
                <div class="form-grid">
                    <div class="form-field">
                        <div class="textfield-material <?= isset($_SESSION['validation_errors']['email']) ? 'textfield-material--error' : '' ?>">
                            <input type="email" name="email" class="textfield-material__input" 
                                   required placeholder=" " value="<?= htmlspecialchars($assistant->getEmail()) ?>">
                            <label class="textfield-material__label">Email *</label>
                            <div class="textfield-material__supporting-text">
                                <i class="fas fa-info-circle"></i>
                                Este email se usará para las notificaciones del evento
                            </div>
                            <?php if (isset($_SESSION['validation_errors']['email'])): ?>
                                <div class="textfield-material__supporting-text textfield-material__supporting-text--error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= $_SESSION['validation_errors']['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="tel" name="mobile_phone" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($assistant->getMobilePhone() ?? '') ?>">
                            <label class="textfield-material__label">Teléfono Móvil</label>
                            <div class="textfield-material__supporting-text">
                                <i class="fas fa-phone"></i>
                                Incluye código de país. Ej: +52 55 1234 5678
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            $contactContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-address-book"></i> Información de Contacto',
                $contactContent,
                'elevated'
            );
            ?>

            <!-- Company Information Section -->
            <?php 
            ob_start();
            ?>
                <div class="company-info">
                    <div class="textfield-material">
                        <input type="text" class="textfield-material__input" 
                               value="<?= htmlspecialchars($assistant->getCompany() ? $assistant->getCompany()['company_name'] : 'Sin empresa asignada') ?>" 
                               readonly placeholder=" ">
                        <label class="textfield-material__label">Empresa</label>
                        <div class="textfield-material__supporting-text">
                            <i class="fas fa-building"></i>
                            Empresa a la que pertenece este participante
                        </div>
                    </div>
                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($assistant->getCompanyId()) ?>">
                </div>
            <?php 
            $companyContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-building"></i> Información de la Empresa',
                $companyContent,
                'outlined'
            );
            ?>

            <!-- Form Actions -->
            <div class="form-actions">
                <?= materialButton(
                    '<i class="fas fa-times"></i> Cancelar',
                    'outlined',
                    '',
                    'type="button" onclick="window.location.href=\'' . BASE_URL . '/events/participants/' . $eventId . '\'"'
                ) ?>
                <?= materialButton(
                    '<i class="fas fa-save"></i> Guardar Cambios',
                    'filled',
                    '',
                    'type="submit"'
                ) ?>
            </div>
        </form>
    </div>
</div>

<?php unset($_SESSION['validation_errors']); ?>

<style>
/* Edit Participant Material Design 3 styles */
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
}

.edit-form-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    max-width: 800px;
}

.participant-edit-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.company-info {
    max-width: 500px;
}

.textfield-material {
    position: relative;
    display: flex;
    flex-direction: column;
}

.textfield-material__input {
    padding: 1rem 1rem 0.5rem 1rem;
    border: 2px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-small);
    background: var(--md-surface);
    color: var(--md-on-surface);
    font-size: 1rem;
    transition: all var(--md-motion-duration-short2);
    font-family: 'Poppins', sans-serif;
}

.textfield-material__input:focus {
    outline: none;
    border-color: var(--md-primary-40);
    background: var(--md-surface-container-lowest);
}

.textfield-material__input:focus + .textfield-material__label,
.textfield-material__input:not(:placeholder-shown) + .textfield-material__label {
    transform: translateY(-0.5rem) scale(0.75);
    color: var(--md-primary-40);
}

.textfield-material__input:read-only {
    background: var(--md-surface-container-lowest);
    color: var(--md-on-surface-variant);
    border-color: var(--md-outline-variant);
}

.textfield-material__label {
    position: absolute;
    left: 1rem;
    top: 1rem;
    color: var(--md-on-surface-variant);
    font-size: 1rem;
    pointer-events: none;
    transition: all var(--md-motion-duration-short2);
    background: var(--md-surface);
    padding: 0 0.25rem;
    transform-origin: left center;
}

.textfield-material__supporting-text {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    line-height: 1.4;
}

.textfield-material__supporting-text i {
    font-size: 0.75rem;
    opacity: 0.8;
}

.textfield-material--error .textfield-material__input {
    border-color: var(--md-error-40);
}

.textfield-material--error .textfield-material__label {
    color: var(--md-error-40);
}

.textfield-material__supporting-text--error {
    color: var(--md-error-40);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 2rem 0 1rem 0;
    border-top: 1px solid var(--md-outline-variant);
}

.error-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    color: var(--md-error-40);
}

.error-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.8;
}

.error-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: var(--md-on-surface);
    font-family: 'Montserrat', sans-serif;
}

.error-subtitle {
    font-size: 1rem;
    color: var(--md-on-surface-variant);
    margin: 0 0 2rem 0;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .edit-form-container {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.5rem;
    }
    
    .textfield-material__input {
        padding: 0.875rem 0.875rem 0.5rem 0.875rem;
    }
    
    .textfield-material__label {
        left: 0.875rem;
    }
}
</style>
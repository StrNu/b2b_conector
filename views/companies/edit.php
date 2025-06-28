<?php 
// Vista de edit company con Material Design 3
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

$pageTitle = 'Editar Empresa';
$moduleCSS = 'companies';
$moduleJS = 'companies';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/dashboard'],
    ['title' => 'Empresas', 'url' => BASE_URL . '/companies'],
    ['title' => 'Editar']
];
?>

<?php if (!isset($company) || !$company): ?>
    <div class="content-area">
        <div class="error-message">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="error-title">Error: Empresa no encontrada</h2>
            <p class="error-subtitle">No se encontró la empresa a editar. Por favor, regrese al listado.</p>
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
            <h1 class="page-title">Editar Empresa</h1>
            <p class="page-subtitle">Modifica la información de la empresa registrada</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Listado',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . (isset($eventModel) && $eventModel ? (int)$eventModel->getId() : (int)$company->getEventId()) . '\'"'
            ) ?>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Edit Form -->
    <div class="edit-form-container">
        <form action="<?= BASE_URL . '/companies/edit/' . (int)$company->getId() ?>" 
              method="POST" enctype="multipart/form-data" class="company-edit-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_GET['redirect_to'] ?? ($_SERVER['HTTP_REFERER'] ?? '')) ?>">
            
            <?php
            require_once __DIR__ . '/../../models/Event.php';
            $eventName = '';
            $eventId = $company->getEventId();
            if ($eventId) {
                $eventModelObj = new Event();
                if ($eventModelObj->findById($eventId)) {
                    $eventName = $eventModelObj->getEventName();
                }
            }
            ?>

            <!-- Event Information -->
            <?php 
            ob_start();
            ?>
                <div class="event-info">
                    <div class="textfield-material">
                        <input type="text" class="textfield-material__input" 
                               value="<?= htmlspecialchars($eventName) ?>" readonly placeholder=" ">
                        <label class="textfield-material__label">Evento</label>
                        <div class="textfield-material__supporting-text">
                            <i class="fas fa-info-circle"></i>
                            Evento al que pertenece esta empresa
                        </div>
                    </div>
                    <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                </div>
            <?php 
            $eventContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-calendar"></i> Información del Evento',
                $eventContent,
                'outlined'
            );
            ?>

            <!-- Company Data Section -->
            <?php 
            ob_start();
            ?>
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="textfield-material">
                            <input type="text" name="company_name" class="textfield-material__input" 
                                   required placeholder=" " value="<?= htmlspecialchars($company->getCompanyName()) ?>">
                            <label class="textfield-material__label">Nombre de la Empresa *</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="address" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getAddress()) ?>">
                            <label class="textfield-material__label">Dirección</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="city" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getCity()) ?>">
                            <label class="textfield-material__label">Ciudad</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="country" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getCountry()) ?>">
                            <label class="textfield-material__label">País</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="url" name="website" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getWebsite()) ?>">
                            <label class="textfield-material__label">Sitio Web</label>
                        </div>
                    </div>
                    
                    <div class="form-field form-field--wide">
                        <div class="file-upload-material">
                            <label class="file-upload-material__label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Logo de la Empresa
                            </label>
                            <input type="file" name="company_logo" accept="image/*" 
                                   class="file-upload-material__input" id="company_logo">
                            <div class="file-upload-material__supporting-text">
                                Formatos soportados: JPG, PNG, GIF. Tamaño máximo: 5MB
                            </div>
                            
                            <?php $logo = method_exists($company, 'getCompanyLogo') ? $company->getCompanyLogo() : null; ?>
                            <?php if ($logo): ?>
                                <div class="current-logo">
                                    <label class="current-logo__label">Logo actual:</label>
                                    <div class="current-logo__image">
                                        <img src="<?= BASE_PUBLIC_URL ?>/uploads/logos/<?= htmlspecialchars($logo) ?>" 
                                             alt="Logo actual" class="logo-preview">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php 
            $companyContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-building"></i> Datos de la Empresa',
                $companyContent,
                'elevated'
            );
            ?>

            <!-- Contact Information Section -->
            <?php 
            ob_start();
            ?>
                <div class="form-grid">
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="contact_first_name" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getContactFirstName()) ?>">
                            <label class="textfield-material__label">Nombre de Contacto</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="text" name="contact_last_name" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getContactLastName()) ?>">
                            <label class="textfield-material__label">Apellido de Contacto</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="tel" name="phone" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars($company->getPhone()) ?>">
                            <label class="textfield-material__label">Teléfono</label>
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <div class="textfield-material">
                            <input type="email" name="email" class="textfield-material__input" 
                                   required placeholder=" " value="<?= htmlspecialchars($company->getEmail()) ?>">
                            <label class="textfield-material__label">Email *</label>
                        </div>
                    </div>
                </div>
            <?php 
            $contactContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-user"></i> Información de Contacto',
                $contactContent,
                'elevated'
            );
            ?>

            <!-- Configuration Section -->
            <?php 
            ob_start();
            ?>
                <div class="configuration-grid">
                    <div class="form-field">
                        <div class="textfield-material">
                            <select name="role" class="textfield-material__input" required>
                                <option value="">Seleccione...</option>
                                <option value="buyer" <?= ($company->getRole() === 'buyer') ? 'selected' : '' ?>>Comprador</option>
                                <option value="supplier" <?= ($company->getRole() === 'supplier') ? 'selected' : '' ?>>Proveedor</option>
                            </select>
                            <label class="textfield-material__label">Rol *</label>
                        </div>
                    </div>
                    
                    <div class="form-field form-field--wide">
                        <div class="textfield-material">
                            <textarea name="description" class="textfield-material__input textfield-material__input--textarea" 
                                      rows="4" placeholder=" "><?= htmlspecialchars($company->getDescription()) ?></textarea>
                            <label class="textfield-material__label">Descripción</label>
                            <div class="textfield-material__supporting-text">
                                Describe los productos o servicios de la empresa
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-field form-field--wide">
                        <div class="textfield-material">
                            <input type="text" name="keywords" class="textfield-material__input" 
                                   placeholder=" " value="<?= htmlspecialchars(is_array($company->getKeywords()) ? implode(', ', $company->getKeywords()) : $company->getKeywords()) ?>">
                            <label class="textfield-material__label">Palabras clave</label>
                            <div class="textfield-material__supporting-text">
                                <i class="fas fa-info-circle"></i>
                                Escribe las palabras clave separadas por comas. Ejemplo: acero inoxidable, ISO 9001, maquila textil
                            </div>
                        </div>
                    </div>
                    
                    <!-- Certifications Section -->
                    <div class="certifications-section form-field--wide">
                        <h4 class="section-subtitle">
                            <i class="fas fa-certificate"></i>
                            Certificaciones de Calidad y Gestión
                        </h4>
                        
                        <div class="certifications-grid">
                            <?php $certs = is_array($company->getCertifications()) ? $company->getCertifications() : (json_decode($company->getCertifications(), true) ?: []); ?>
                            
                            <div class="checkbox-group">
                                <div class="checkbox-material">
                                    <input type="checkbox" name="certifications[]" value="ISO 9001" 
                                           id="cert_iso9001" <?= in_array('ISO 9001', $certs) ? 'checked' : '' ?>>
                                    <label for="cert_iso9001" class="checkbox-material__label">
                                        <span class="checkbox-material__checkmark"></span>
                                        ISO 9001 – Gestión de calidad
                                    </label>
                                </div>
                                
                                <div class="checkbox-material">
                                    <input type="checkbox" name="certifications[]" value="ISO 14001" 
                                           id="cert_iso14001" <?= in_array('ISO 14001', $certs) ? 'checked' : '' ?>>
                                    <label for="cert_iso14001" class="checkbox-material__label">
                                        <span class="checkbox-material__checkmark"></span>
                                        ISO 14001 – Gestión ambiental
                                    </label>
                                </div>
                                
                                <div class="checkbox-material">
                                    <input type="checkbox" name="certifications[]" value="ISO 45001" 
                                           id="cert_iso45001" <?= in_array('ISO 45001', $certs) ? 'checked' : '' ?>>
                                    <label for="cert_iso45001" class="checkbox-material__label">
                                        <span class="checkbox-material__checkmark"></span>
                                        ISO 45001 – Seguridad y salud ocupacional
                                    </label>
                                </div>
                                
                                <div class="checkbox-material">
                                    <input type="checkbox" name="certifications[]" value="ISO 22000" 
                                           id="cert_iso22000" <?= in_array('ISO 22000', $certs) ? 'checked' : '' ?>>
                                    <label for="cert_iso22000" class="checkbox-material__label">
                                        <span class="checkbox-material__checkmark"></span>
                                        ISO 22000 – Seguridad alimentaria
                                    </label>
                                </div>
                                
                                <div class="checkbox-material">
                                    <input type="checkbox" name="certifications[]" value="Six Sigma / Lean Six Sigma" 
                                           id="cert_sixsigma" <?= in_array('Six Sigma / Lean Six Sigma', $certs) ? 'checked' : '' ?>>
                                    <label for="cert_sixsigma" class="checkbox-material__label">
                                        <span class="checkbox-material__checkmark"></span>
                                        Six Sigma / Lean Six Sigma – Mejora de procesos
                                    </label>
                                </div>
                            </div>
                            
                            <div class="other-certifications">
                                <?php 
                                $otros = '';
                                if ($certs) {
                                    foreach ($certs as $c) {
                                        if (!in_array($c, ['ISO 9001','ISO 14001','ISO 45001','ISO 22000','Six Sigma / Lean Six Sigma'])) {
                                            $otros = $c;
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <div class="textfield-material">
                                    <input type="text" name="certifications_otros" class="textfield-material__input" 
                                           placeholder=" " value="<?= htmlspecialchars($otros) ?>">
                                    <label class="textfield-material__label">Otras certificaciones</label>
                                    <div class="textfield-material__supporting-text">
                                        Especifique otras certificaciones no listadas
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Status -->
                    <div class="status-section form-field--wide">
                        <div class="switch-material">
                            <input type="checkbox" name="is_active" id="is_active" 
                                   class="switch-material__input" <?= $company->isActive() ? 'checked' : '' ?>>
                            <label for="is_active" class="switch-material__label">
                                <span class="switch-material__track"></span>
                                <span class="switch-material__thumb"></span>
                            </label>
                            <span class="switch-material__text">Empresa Activa</span>
                        </div>
                    </div>
                </div>
            <?php 
            $configContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-cog"></i> Configuración',
                $configContent,
                'elevated'
            );
            ?>

            <!-- Form Actions -->
            <div class="form-actions">
                <?= materialButton(
                    '<i class="fas fa-times"></i> Cancelar',
                    'outlined',
                    '',
                    'type="button" onclick="window.history.back();"'
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

<style>
/* Company Edit Form Material Design 3 styles */
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
}

.company-edit-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.event-info {
    max-width: 500px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.configuration-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-field--wide {
    grid-column: 1 / -1;
}

.textfield-material__input--textarea {
    resize: vertical;
    min-height: 100px;
}

.file-upload-material {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.file-upload-material__label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface);
}

.file-upload-material__input {
    padding: 1rem;
    border: 2px dashed var(--md-outline);
    border-radius: var(--md-shape-corner-medium);
    background: var(--md-surface-container-lowest);
    cursor: pointer;
    transition: all var(--md-motion-duration-short2);
}

.file-upload-material__input:hover {
    border-color: var(--md-primary-40);
    background: var(--md-primary-container);
}

.file-upload-material__supporting-text {
    font-size: 0.75rem;
    color: var(--md-on-surface-variant);
}

.current-logo {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
}

.current-logo__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface-variant);
}

.current-logo__image {
    display: flex;
    justify-content: flex-start;
}

.logo-preview {
    max-height: 80px;
    max-width: 200px;
    object-fit: contain;
    border-radius: var(--md-shape-corner-small);
    border: 1px solid var(--md-outline-variant);
}

.section-subtitle {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 1.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-family: 'Montserrat', sans-serif;
}

.section-subtitle i {
    color: var(--md-primary-40);
}

.certifications-section {
    background: var(--md-surface-container-lowest);
    padding: 1.5rem;
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
}

.certifications-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.checkbox-material {
    position: relative;
    display: flex;
    align-items: flex-start;
}

.checkbox-material input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.checkbox-material__label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    font-size: 0.875rem;
    line-height: 1.4;
    color: var(--md-on-surface);
    padding: 0.5rem 0;
}

.checkbox-material__checkmark {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border: 2px solid var(--md-outline);
    border-radius: var(--md-shape-corner-extra-small);
    background: var(--md-surface);
    position: relative;
    transition: all var(--md-motion-duration-short2);
}

.checkbox-material__checkmark::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 10px;
    border: solid var(--md-on-primary);
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    opacity: 0;
    transition: opacity var(--md-motion-duration-short2);
}

.checkbox-material input:checked + .checkbox-material__label .checkbox-material__checkmark {
    background: var(--md-primary-40);
    border-color: var(--md-primary-40);
}

.checkbox-material input:checked + .checkbox-material__label .checkbox-material__checkmark::after {
    opacity: 1;
}

.checkbox-material:hover .checkbox-material__checkmark {
    border-color: var(--md-primary-40);
}

.other-certifications {
    margin-top: 1rem;
}

.status-section {
    padding: 1rem 0;
}

.switch-material {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.switch-material__input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.switch-material__label {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
}

.switch-material__track {
    width: 52px;
    height: 32px;
    background: var(--md-outline);
    border-radius: 16px;
    transition: all var(--md-motion-duration-short2);
}

.switch-material__thumb {
    position: absolute;
    width: 24px;
    height: 24px;
    background: var(--md-outline);
    border-radius: 50%;
    top: 4px;
    left: 4px;
    transition: all var(--md-motion-duration-short2);
    box-shadow: var(--md-elevation-1);
}

.switch-material__input:checked + .switch-material__label .switch-material__track {
    background: var(--md-primary-40);
}

.switch-material__input:checked + .switch-material__label .switch-material__thumb {
    background: var(--md-on-primary);
    transform: translateX(20px);
}

.switch-material__text {
    font-size: 1rem;
    font-weight: 500;
    color: var(--md-on-surface);
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
    
    .checkbox-group {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .checkbox-material__label {
        font-size: 0.8rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
}
</style>
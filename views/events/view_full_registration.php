<?php 
// Vista de view_full_registration con Material Design 3
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

$pageTitle = 'Registro Completo de Empresa';
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/dashboard'],
    ['title' => 'Eventos', 'url' => BASE_URL . '/events'],
    ['title' => 'Registro Completo']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Registro Completo de Empresa</h1>
            <p class="page-subtitle">Información detallada del registro de la empresa en el evento</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Regresar al evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/event_list?event_id=' . (int)$event->getId() . '\'"'
            ) ?>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Company Information Section -->
    <div class="registration-sections">
        <!-- Información de la Empresa -->
        <?php 
        ob_start();
        ?>
            <div class="company-info-grid">
                <div class="info-item">
                    <label class="info-label">Nombre de la Empresa</label>
                    <div class="info-value"><?= htmlspecialchars($company->getCompanyName()) ?></div>
                </div>
                <div class="info-item">
                    <label class="info-label">Sitio Web</label>
                    <div class="info-value">
                        <?php if ($company->getWebsite()): ?>
                            <a href="<?= htmlspecialchars($company->getWebsite()) ?>" target="_blank" class="external-link">
                                <i class="fas fa-external-link-alt"></i>
                                <?= htmlspecialchars($company->getWebsite()) ?>
                            </a>
                        <?php else: ?>
                            <span class="no-data">No especificado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-item info-item--full">
                    <label class="info-label">Descripción</label>
                    <div class="info-value description-text"><?= nl2br(htmlspecialchars($company->getDescription())) ?></div>
                </div>
                <?php if ($company->getCompanyLogo()): ?>
                <div class="info-item info-item--full">
                    <label class="info-label">Logo de la Empresa</label>
                    <div class="company-logo">
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= htmlspecialchars($company->getCompanyLogo()) ?>" 
                             alt="Logo de <?= htmlspecialchars($company->getCompanyName()) ?>" 
                             class="logo-image">
                    </div>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <label class="info-label">Ciudad</label>
                    <div class="info-value"><?= htmlspecialchars($company->getCity()) ?></div>
                </div>
                <div class="info-item">
                    <label class="info-label">País</label>
                    <div class="info-value"><?= htmlspecialchars($company->getCountry()) ?></div>
                </div>
            </div>
        <?php 
        $companyContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-building"></i> Información de la Empresa',
            $companyContent,
            'elevated',
            materialButton(
                '<i class="fas fa-edit"></i> Editar',
                'tonal',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . (int)$event->getId() . '/edit/' . (int)$company->getId() . '\'"',
                'small'
            )
        );
        ?>

        <!-- Datos de Contacto -->
        <?php 
        ob_start();
        ?>
            <div class="contact-info-grid">
                <div class="info-item">
                    <label class="info-label">Nombre</label>
                    <div class="info-value"><?= htmlspecialchars($company->getContactFirstName()) ?></div>
                </div>
                <div class="info-item">
                    <label class="info-label">Apellido</label>
                    <div class="info-value"><?= htmlspecialchars($company->getContactLastName()) ?></div>
                </div>
                <div class="info-item">
                    <label class="info-label">Teléfono Celular</label>
                    <div class="info-value contact-phone">
                        <i class="fas fa-phone"></i>
                        <?= htmlspecialchars($company->getPhone()) ?>
                    </div>
                </div>
                <div class="info-item">
                    <label class="info-label">Correo Electrónico</label>
                    <div class="info-value contact-email">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= htmlspecialchars($company->getEmail()) ?>"><?= htmlspecialchars($company->getEmail()) ?></a>
                    </div>
                </div>
            </div>
        <?php 
        $contactContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-user"></i> Datos de Contacto',
            $contactContent,
            'elevated'
        );
        ?>

        <!-- Datos de Acceso -->
        <?php 
        ob_start();
        ?>
            <div class="access-info">
                <?php if (!empty($eventUserEmail)): ?>
                    <div class="info-item">
                        <label class="info-label">Email de acceso</label>
                        <div class="info-value contact-email">
                            <i class="fas fa-key"></i>
                            <?= htmlspecialchars($eventUserEmail) ?>
                        </div>
                    </div>
                    
                    <div class="password-change-section">
                        <h4 class="section-subtitle">Cambiar Contraseña</h4>
                        <form action="<?= BASE_URL ?>/auth/change_password_event" method="POST" class="password-form">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($eventUserEmail) ?>">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                            
                            <div class="form-row">
                                <div class="textfield-material">
                                    <input type="password" name="new_password" class="textfield-material__input" 
                                           required placeholder=" ">
                                    <label class="textfield-material__label">Nueva contraseña</label>
                                </div>
                                <div class="textfield-material">
                                    <input type="password" name="confirm_password" class="textfield-material__input" 
                                           required placeholder=" ">
                                    <label class="textfield-material__label">Repetir nueva contraseña</label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <?= materialButton(
                                    '<i class="fas fa-lock"></i> Cambiar contraseña',
                                    'filled',
                                    '',
                                    'type="submit"'
                                ) ?>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-info-circle"></i>
                        No hay datos de usuario para este evento.
                    </div>
                <?php endif; ?>
            </div>
        <?php 
        $accessContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-key"></i> Datos de Acceso',
            $accessContent,
            'elevated'
        );
        ?>

        <!-- Asistentes -->
        <?php 
        ob_start();
        ?>
            <div class="assistants-section">
                <?php if (!empty($assistants)): ?>
                    <div class="assistants-list">
                        <?php foreach ($assistants as $i => $asist): ?>
                            <div class="assistant-item">
                                <div class="assistant-info">
                                    <div class="assistant-header">
                                        <h4 class="assistant-name">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($asist['first_name'] . ' ' . $asist['last_name']) ?>
                                        </h4>
                                        <?= materialButton(
                                            '<i class="fas fa-edit"></i>',
                                            'tonal',
                                            '',
                                            'onclick="window.location.href=\'' . BASE_URL . '/events/editParticipant/' . (int)$event->getId() . '/' . (int)$asist['assistant_id'] . '\'" title="Editar participante"',
                                            'small'
                                        ) ?>
                                    </div>
                                    <div class="assistant-details">
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <?= htmlspecialchars($asist['mobile_phone']) ?>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-envelope"></i>
                                            <a href="mailto:<?= htmlspecialchars($asist['email']) ?>"><?= htmlspecialchars($asist['email']) ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-user-plus"></i>
                        <p>No hay asistentes registrados para esta empresa</p>
                        <?= materialButton(
                            '<i class="fas fa-user-plus"></i> Registrar asistente',
                            'filled',
                            '',
                            'onclick="window.location.href=\'' . BASE_URL . '/events/participants/' . (int)$event->getId() . '\'"'
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
        $assistantsContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-users"></i> Asistentes Registrados',
            $assistantsContent,
            'elevated',
            !empty($assistants) ? materialButton(
                '<i class="fas fa-edit"></i> Editar',
                'tonal',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/editParticipant/' . (int)$event->getId() . '/' . (!empty($assistants) ? (int)$assistants[0]['assistant_id'] : '') . '\'"',
                'small'
            ) : ''
        );
        ?>

        <!-- Productos o Servicios de Interés -->
        <?php 
        ob_start();
        ?>
            <div class="requirements-section">
                <?php
                $role = method_exists($company, 'getRole') ? $company->getRole() : ($eventUserRole ?? null);
                
                if ($role === 'supplier') {
                    // Mostrar ofertas del proveedor
                    if (!empty($offers) && !empty($categories) && !empty($subcategories)) {
                        include(VIEW_DIR . '/events/partials/supplier_offers_readonly.php');
                    } else {
                        echo '<div class="no-data-message">
                                <i class="fas fa-box"></i>
                                <p>No se especificaron productos o servicios de interés.</p>
                              </div>';
                    }
                } else {
                    // Mostrar requerimientos del comprador
                    if (!empty($categories) && !empty($subcategories)) {
                        include(VIEW_DIR . '/events/partials/requirements_readonly.php');
                    } else {
                        echo '<div class="no-data-message">
                                <i class="fas fa-shopping-cart"></i>
                                <p>No se especificaron productos o servicios de interés.</p>
                              </div>';
                    }
                }
                ?>
            </div>
        <?php 
        $requirementsContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-box"></i> Productos o Servicios de Interés',
            $requirementsContent,
            'elevated',
            materialButton(
                '<i class="fas fa-edit"></i> Editar',
                'tonal',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/editRequirements/' . (int)$event->getId() . '/' . (int)$company->getId() . '\'"',
                'small'
            )
        );
        ?>

        <!-- Días de Asistencia -->
        <?php 
        ob_start();
        ?>
            <div class="attendance-section">
                <?php if (!empty($attendanceDays)): ?>
                    <div class="attendance-days">
                        <?php foreach ($attendanceDays as $day): ?>
                            <div class="day-item">
                                <i class="fas fa-calendar-day"></i>
                                <span><?= dateFromDatabase(is_array($day) && isset($day['date']) ? $day['date'] : $day) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-calendar-times"></i>
                        <p>No se especificaron días de asistencia.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
        $attendanceContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-calendar-alt"></i> Días de Asistencia',
            $attendanceContent,
            'elevated',
            materialButton(
                '<i class="fas fa-edit"></i> Editar',
                'tonal',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/edit_days_attendance/' . (int)$event->getId() . '/' . (int)$company->getId() . '\'"',
                'small'
            )
        );
        ?>
    </div>
</div>

<style>
/* Registration Full View Material Design 3 styles */
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

.registration-sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.company-info-grid,
.contact-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item--full {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface-variant);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1rem;
    color: var(--md-on-surface);
    line-height: 1.5;
}

.description-text {
    background: var(--md-surface-container-lowest);
    padding: 1rem;
    border-radius: var(--md-shape-corner-small);
    border: 1px solid var(--md-outline-variant);
}

.external-link {
    color: var(--md-primary-40);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: color var(--md-motion-duration-short2);
}

.external-link:hover {
    color: var(--md-primary-30);
    text-decoration: underline;
}

.no-data {
    color: var(--md-on-surface-variant);
    font-style: italic;
}

.company-logo {
    display: flex;
    justify-content: flex-start;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
}

.logo-image {
    max-height: 80px;
    max-width: 200px;
    object-fit: contain;
    border-radius: var(--md-shape-corner-small);
}

.contact-phone,
.contact-email {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.contact-phone i,
.contact-email i {
    color: var(--md-primary-40);
    font-size: 0.875rem;
}

.contact-email a {
    color: var(--md-primary-40);
    text-decoration: none;
}

.contact-email a:hover {
    text-decoration: underline;
}

.access-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.password-change-section {
    background: var(--md-surface-container-lowest);
    padding: 1.5rem;
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
}

.section-subtitle {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 1rem 0;
    font-family: 'Montserrat', sans-serif;
}

.password-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid var(--md-outline-variant);
}

.assistants-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.assistant-item {
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
    overflow: hidden;
}

.assistant-info {
    padding: 1.5rem;
}

.assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.assistant-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-family: 'Montserrat', sans-serif;
}

.assistant-name i {
    color: var(--md-primary-40);
}

.assistant-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
}

.detail-item i {
    color: var(--md-primary-40);
    width: 16px;
    text-align: center;
}

.detail-item a {
    color: var(--md-primary-40);
    text-decoration: none;
}

.detail-item a:hover {
    text-decoration: underline;
}

.no-data-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
    color: var(--md-on-surface-variant);
}

.no-data-message i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.6;
}

.no-data-message p {
    margin: 0 0 1.5rem 0;
    font-size: 1rem;
}

.attendance-days {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.day-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
    border-radius: var(--md-shape-corner-medium);
    font-weight: 500;
}

.day-item i {
    font-size: 1.125rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .company-info-grid,
    .contact-info-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .assistant-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .attendance-days {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .assistant-details {
        font-size: 0.8rem;
    }
}
</style>
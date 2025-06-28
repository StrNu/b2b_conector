<?php 
// Vista de empresa con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
$moduleCSS = 'companies';
$additionalCSS = ['material-theme.css'];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Detalle de Empresa</h1>
            <p class="page-subtitle">Información completa de la empresa registrada en el evento</p>
        </div>
        <div class="page-header__actions">
            <?php 
            // Determinar la URL de retorno basada en si tenemos eventId
            $backUrl = BASE_URL . '/companies';
            if (!empty($eventId)) {
                $backUrl = BASE_URL . '/events/companies/' . (int)$eventId;
            }
            ?>
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Listado',
                'outlined',
                '',
                'onclick="window.location.href=\'' . $backUrl . '\'"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-edit"></i> Editar Empresa',
                'filled',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/companies/edit/' . (int)$company->getId() . '\'"'
            ) ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Perfil de la empresa -->
    <div class="company-profile">
        <?= materialCard(
            '',
            '
            <div class="company-profile__content">
                <div class="company-profile__avatar">
                    ' . (!empty($company->getCompanyLogo()) ? 
                        '<img src="' . BASE_PUBLIC_URL . '/uploads/logos/' . htmlspecialchars($company->getCompanyLogo()) . '" 
                             alt="Logo de ' . htmlspecialchars($company->getCompanyName()) . '"
                             class="company-profile__image">' :
                        '<div class="company-profile__placeholder">
                            <i class="fas fa-building"></i>
                        </div>'
                    ) . '
                </div>
                
                <div class="company-profile__info">
                    <h2 class="company-profile__name">' . htmlspecialchars($company->getCompanyName()) . '</h2>
                    <p class="company-profile__contact">
                        ' . htmlspecialchars($company->getContactFirstName() . ' ' . $company->getContactLastName()) . '
                    </p>
                    <div class="company-profile__badges">
                        <span class="badge-material badge-material--' . ($company->getRole() === 'buyer' ? 'primary' : 'secondary') . '">
                            <i class="fas fa-' . ($company->getRole() === 'buyer' ? 'shopping-cart' : 'boxes') . '"></i>
                            ' . ($company->getRole() === 'buyer' ? 'Comprador' : 'Proveedor') . '
                        </span>
                        <span class="badge-material badge-material--' . ($company->isActive() ? 'success' : 'error') . '">
                            <i class="fas fa-circle"></i>
                            ' . ($company->isActive() ? 'Activo' : 'Inactivo') . '
                        </span>
                    </div>
                </div>
            </div>',
            'elevated'
        ) ?>
    </div>

    <!-- Grid de información -->
    <div class="company-details-grid">
        <!-- Información de contacto -->
        <div class="company-section">
            <?= materialCard(
                '<i class="fas fa-address-card"></i> Información de Contacto',
                '
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">Contacto Principal</div>
                            <div class="info-item__value">' . htmlspecialchars($company->getContactFirstName() . ' ' . $company->getContactLastName()) . '</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">Email</div>
                            <div class="info-item__value">
                                <a href="mailto:' . htmlspecialchars($company->getEmail()) . '" class="info-link">
                                    ' . htmlspecialchars($company->getEmail()) . '
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">Teléfono</div>
                            <div class="info-item__value">
                                <a href="tel:' . htmlspecialchars($company->getPhone()) . '" class="info-link">
                                    ' . htmlspecialchars($company->getPhone()) . '
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    ' . (!empty($company->getWebsite()) ? '
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">Sitio Web</div>
                            <div class="info-item__value">
                                <a href="' . htmlspecialchars($company->getWebsite()) . '" target="_blank" class="info-link info-link--external">
                                    ' . htmlspecialchars($company->getWebsite()) . '
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>' : '') . '
                </div>',
                'outlined'
            ) ?>
        </div>

        <!-- Información de ubicación -->
        <div class="company-section">
            <?= materialCard(
                '<i class="fas fa-map-marker-alt"></i> Ubicación',
                '
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-map-pin"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">Dirección Completa</div>
                            <div class="info-item__value">' . htmlspecialchars($company->getAddress()) . '</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-city"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">Ciudad</div>
                            <div class="info-item__value">' . htmlspecialchars($company->getCity()) . '</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-item__icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="info-item__content">
                            <div class="info-item__label">País</div>
                            <div class="info-item__value">' . htmlspecialchars($company->getCountry()) . '</div>
                        </div>
                    </div>
                </div>',
                'outlined'
            ) ?>
        </div>
    </div>

    <!-- Secciones adicionales -->
    <div class="company-additional-sections">
        <!-- Keywords -->
        <?php 
        $keywords = $company->getKeywords();
        $hasKeywords = false;
        
        if (is_string($keywords) && !empty(trim($keywords))) {
            $keywordArray = array_filter(array_map('trim', explode(',', $keywords)));
            $hasKeywords = !empty($keywordArray);
        } elseif (is_array($keywords) && !empty($keywords)) {
            $keywordArray = array_filter(array_map('trim', $keywords));
            $hasKeywords = !empty($keywordArray);
        }
        
        if ($hasKeywords): ?>
        <div class="company-section">
            <?= materialCard(
                '<i class="fas fa-tags"></i> Palabras Clave',
                '
                <div class="keywords-container">
                    ' . implode('', array_map(function($keyword) {
                        if (!empty(trim($keyword))) {
                            return '<span class="chip-material">
                                <i class="fas fa-tag"></i>
                                ' . htmlspecialchars(trim($keyword)) . '
                            </span>';
                        }
                        return '';
                    }, $keywordArray)) . '
                </div>',
                'outlined'
            ) ?>
        </div>
        <?php endif; ?>

        <!-- Certificaciones -->
        <?php 
        $certifications = $company->getCertifications();
        $hasCertifications = false;
        
        if (is_string($certifications) && !empty(trim($certifications))) {
            $certArray = array_filter(array_map('trim', preg_split('/[,\n\r]+/', $certifications)));
            $hasCertifications = !empty($certArray);
        } elseif (is_array($certifications) && !empty($certifications)) {
            $certArray = array_filter(array_map('trim', $certifications));
            $hasCertifications = !empty($certArray);
        }
        
        if ($hasCertifications): ?>
        <div class="company-section">
            <?= materialCard(
                '<i class="fas fa-certificate"></i> Certificaciones',
                '
                <div class="certifications-grid">
                    ' . implode('', array_map(function($certification) {
                        if (!empty(trim($certification))) {
                            return '<div class="certification-item">
                                <div class="certification-item__icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div class="certification-item__text">
                                    ' . htmlspecialchars(trim($certification)) . '
                                </div>
                            </div>';
                        }
                        return '';
                    }, $certArray)) . '
                </div>',
                'outlined'
            ) ?>
        </div>
        <?php endif; ?>

        <!-- Descripción de la empresa -->
        <?php if (!empty($company->getDescription())): ?>
        <div class="company-section">
            <?= materialCard(
                '<i class="fas fa-info-circle"></i> Descripción de la Empresa',
                '
                <div class="company-description">
                    ' . nl2br(htmlspecialchars($company->getDescription())) . '
                </div>',
                'outlined'
            ) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Company view specific Material Design 3 styles */
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

.company-profile {
    margin-bottom: 2rem;
}

.company-profile__content {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.company-profile__avatar {
    flex-shrink: 0;
}

.company-profile__image {
    width: 120px;
    height: 120px;
    border-radius: var(--md-shape-corner-large);
    object-fit: cover;
    border: 2px solid var(--md-outline-variant);
}

.company-profile__placeholder {
    width: 120px;
    height: 120px;
    background: var(--md-surface-container);
    border-radius: var(--md-shape-corner-large);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--md-on-surface-variant);
    font-size: 3rem;
    border: 2px solid var(--md-outline-variant);
}

.company-profile__info {
    flex: 1;
}

.company-profile__name {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.company-profile__contact {
    font-size: 1.125rem;
    color: var(--md-on-surface-variant);
    margin: 0 0 1rem 0;
}

.company-profile__badges {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.company-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.company-additional-sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.company-section {
    width: 100%;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.info-item__icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--md-primary-40);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.info-item__content {
    flex: 1;
    min-width: 0;
}

.info-item__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface-variant);
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item__value {
    font-size: 1rem;
    color: var(--md-on-surface);
    line-height: 1.4;
    word-break: break-word;
}

.info-link {
    color: var(--md-primary-40);
    text-decoration: none;
    transition: color var(--md-motion-duration-short2);
}

.info-link:hover {
    color: var(--md-primary-30);
    text-decoration: underline;
}

.info-link--external {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.keywords-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.chip-material {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
    border-radius: var(--md-shape-corner-full);
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid var(--md-outline-variant);
}

.chip-material i {
    font-size: 0.75rem;
}

.certifications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.certification-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
}

.certification-item__icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
    border-radius: var(--md-shape-corner-full);
    flex-shrink: 0;
}

.certification-item__text {
    font-weight: 500;
    color: var(--md-on-surface);
    line-height: 1.3;
}

.company-description {
    font-size: 1rem;
    line-height: 1.6;
    color: var(--md-on-surface);
}

.badge-material {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-material--primary {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.badge-material--secondary {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
}

.badge-material--success {
    background: var(--md-success-container);
    color: var(--md-on-success-container);
}

.badge-material--error {
    background: var(--md-error-container);
    color: var(--md-on-error-container);
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
    
    .company-profile__content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .company-details-grid {
        grid-template-columns: 1fr;
    }
    
    .company-profile__badges {
        justify-content: center;
    }
    
    .certifications-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .company-profile__image,
    .company-profile__placeholder {
        width: 80px;
        height: 80px;
    }
    
    .company-profile__placeholder {
        font-size: 2rem;
    }
    
    .company-profile__name {
        font-size: 1.5rem;
    }
    
    .keywords-container {
        justify-content: center;
    }
}
</style>
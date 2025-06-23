<?php 
$moduleCSS = 'companies';
$additionalCSS = ['components/layouts.css'];
include VIEW_DIR . '/shared/header.php'; 
?>

<div class="content">
    <div class="content-layout">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <h1>Detalle de Empresa</h1>
                <p>Información completa de la empresa registrada en el evento</p>
            </div>
            <div class="page-header-actions">
                <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventModel->getId() ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
                <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventModel->getId() ?>/edit/<?= (int)$company->getId() ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> Editar Empresa
                </a>
            </div>
        </div>

        <?php include(VIEW_DIR . '/shared/notifications.php'); ?>

        <!-- Company Profile -->
        <div class="card card-simple">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if (!empty($company->getCompanyLogo())): ?>
                        <img src="<?= BASE_PUBLIC_URL ?>/uploads/logos/<?= htmlspecialchars($company->getCompanyLogo()) ?>" 
                             alt="Logo de <?= htmlspecialchars($company->getCompanyName()) ?>">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <h2 class="profile-title"><?= htmlspecialchars($company->getCompanyName()) ?></h2>
                    <p class="profile-subtitle">
                        <?= htmlspecialchars($company->getContactFirstName() . ' ' . $company->getContactLastName()) ?>
                    </p>
                    <div class="profile-badges">
                        <span class="badge role-<?= $company->getRole() ?>">
                            <i class="fas fa-<?= $company->getRole() === 'buyer' ? 'shopping-cart' : 'boxes' ?>"></i>
                            <?= $company->getRole() === 'buyer' ? 'Comprador' : 'Proveedor' ?>
                        </span>
                        <span class="badge status-<?= $company->isActive() ? 'active' : 'inactive' ?>">
                            <i class="fas fa-circle"></i>
                            <?= $company->isActive() ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-address-card"></i>
                Información de Contacto
            </div>
            <div class="card-body">
                <div class="info-section">
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                Contacto Principal
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($company->getContactFirstName() . ' ' . $company->getContactLastName()) ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </div>
                            <div class="info-value">
                                <a href="mailto:<?= htmlspecialchars($company->getEmail()) ?>" class="link">
                                    <?= htmlspecialchars($company->getEmail()) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-phone"></i>
                                Teléfono
                            </div>
                            <div class="info-value">
                                <a href="tel:<?= htmlspecialchars($company->getPhone()) ?>" class="link">
                                    <?= htmlspecialchars($company->getPhone()) ?>
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($company->getWebsite())): ?>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-globe"></i>
                                Sitio Web
                            </div>
                            <div class="info-value">
                                <a href="<?= htmlspecialchars($company->getWebsite()) ?>" target="_blank" class="link external">
                                    <?= htmlspecialchars($company->getWebsite()) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Information -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-map-marker-alt"></i>
                Ubicación
            </div>
            <div class="card-body">
                <div class="info-section">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-map-pin"></i>
                            Dirección Completa
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($company->getAddress()) ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-city"></i>
                                Ciudad
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($company->getCity()) ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-flag"></i>
                                País
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($company->getCountry()) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keywords Section -->
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
        ?>
        
        <?php if ($hasKeywords): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-tags"></i>
                Palabras Clave
            </div>
            <div class="card-body">
                <div class="keywords-container">
                    <?php foreach ($keywordArray as $keyword): ?>
                        <?php if (!empty(trim($keyword))): ?>
                            <span class="keyword-tag">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars(trim($keyword)) ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Certifications Section -->
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
        ?>
        
        <?php if ($hasCertifications): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-certificate"></i>
                Certificaciones
            </div>
            <div class="card-body">
                <div class="certifications-container">
                    <div class="certifications-grid">
                        <?php foreach ($certArray as $certification): ?>
                            <?php if (!empty(trim($certification))): ?>
                                <div class="certification-item">
                                    <i class="fas fa-award"></i>
                                    <span><?= htmlspecialchars(trim($certification)) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Company Description -->
        <?php if (!empty($company->getDescription())): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>
                Descripción de la Empresa
            </div>
            <div class="card-body">
                <div class="info-value" style="line-height: 1.6; font-size: 1rem;">
                    <?= nl2br(htmlspecialchars($company->getDescription())) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php
// Incluir pie de página
include VIEW_DIR . '/shared/footer.php';
?>

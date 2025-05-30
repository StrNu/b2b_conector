<?php
// Incluir encabezado
include VIEW_DIR . '/shared/header.php';
?>
<div class="company-view-container">
    <h1>Detalle de Empresa</h1>
    <div class="company-main-info card">
        <div class="company-header">
            <?php
            // DEBUG: Mostrar valor y ruta del logo
            if (isset($company)) {
                $logo = method_exists($company, 'getCompanyLogo') ? $company->getCompanyLogo() : '';
                echo '<div style="color:red;font-size:12px;">DEBUG company_logo: ' . htmlspecialchars($logo) . '<br>DEBUG URL: ' . htmlspecialchars(BASE_PUBLIC_URL . '/uploads/logos/' . $logo) . '</div>';
            }
            ?>
            <?php if (!empty($company->getCompanyLogo())): ?>
                <img src="<?= BASE_PUBLIC_URL ?>/uploads/logos/<?= htmlspecialchars($company->getCompanyLogo()) ?>" alt="Logo de la empresa" class="company-logo" style="max-width:120px;max-height:120px;">
            <?php else: ?>
                <span class="text-xs text-gray-400">Sin logo</span>
            <?php endif; ?>
            <div class="company-data">
                <h2><?= htmlspecialchars($company->getCompanyName()) ?></h2>
                <p><strong>Rol:</strong> <?= $company->getRole() === 'buyer' ? 'Comprador' : 'Proveedor' ?></p>
                <p><strong>Contacto:</strong> <?= htmlspecialchars($company->getContactFirstName() . ' ' . $company->getContactLastName()) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($company->getEmail()) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($company->getPhone()) ?></p>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($company->getAddress()) ?>, <?= htmlspecialchars($company->getCity()) ?>, <?= htmlspecialchars($company->getCountry()) ?></p>
                <p><strong>Sitio web:</strong> <?php if (!empty($company->getWebsite())): ?><a href="<?= htmlspecialchars($company->getWebsite()) ?>" target="_blank"><?= htmlspecialchars($company->getWebsite()) ?></a><?php endif; ?></p>
                <p><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($company->getDescription())) ?></p>
                <p><strong>Estado:</strong> <?= $company->isActive() ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>' ?></p>
            </div>
        </div>
    </div>
    <div class="company-actions">
        <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventModel->getId() ?>/edit/<?= (int)$company->getId() ?>" class="btn btn-primary">Editar Empresa</a>
        <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventModel->getId() ?>" class="btn btn-secondary">Volver al Listado</a>
    </div>
</div>
<?php
// Incluir pie de página
include VIEW_DIR . '/shared/footer.php';
?>

<?php
// Incluir encabezado
include VIEW_DIR . '/shared/header.php';
?>
<div class="company-view-container">
    <h1>Detalle de Empresa</h1>
    <div class="company-main-info card">
        <div class="company-header">
            <?php if (!empty($this->companyModel->company_logo)): ?>
                <img src="<?= BASE_PUBLIC_URL . '/uploads/logos/' . htmlspecialchars($this->companyModel->company_logo) ?>" alt="Logo de la empresa" class="company-logo" style="max-width:120px;max-height:120px;">
            <?php endif; ?>
            <div class="company-data">
                <h2><?= htmlspecialchars($this->companyModel->getCompanyName()) ?></h2>
                <p><strong>Rol:</strong> <?= $role === 'buyer' ? 'Comprador' : 'Proveedor' ?></p>
                <p><strong>Contacto:</strong> <?= htmlspecialchars($this->companyModel->getContactFirstName() . ' ' . $this->companyModel->getContactLastName()) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($this->companyModel->getEmail()) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($this->companyModel->getPhone()) ?></p>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($this->companyModel->getAddress()) ?>, <?= htmlspecialchars($this->companyModel->getCity()) ?>, <?= htmlspecialchars($this->companyModel->getCountry()) ?></p>
                <p><strong>Sitio web:</strong> <?php if (!empty($this->companyModel->getWebsite())): ?><a href="<?= htmlspecialchars($this->companyModel->getWebsite()) ?>" target="_blank"><?= htmlspecialchars($this->companyModel->getWebsite()) ?></a><?php endif; ?></p>
                <p><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($this->companyModel->getDescription())) ?></p>
                <p><strong>Estado:</strong> <?= $this->companyModel->isActive() ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>' ?></p>
            </div>
        </div>
    </div>
    <div class="company-actions">
        <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventModel->getId() ?>/edit/<?= (int)$companyModel->getId() ?>" class="btn btn-primary">Editar Empresa</a>
        <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventModel->getId() ?>" class="btn btn-secondary">Volver al Listado</a>
    </div>
</div>
<?php
// Incluir pie de página
include VIEW_DIR . '/shared/footer.php';
?>

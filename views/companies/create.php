<?php include(VIEW_DIR . '/shared/header.php'); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
<style>
    .company-form .form-row {
        display: flex;
        gap: 24px;
    }
    .company-form .form-group.half {
        flex: 1 1 0;
        min-width: 0;
    }
    .company-form .form-section {
        margin-bottom: 2.5rem;
        padding: 1.5rem 1.5rem 1rem 1.5rem;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .company-form .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2a4365;
        margin-bottom: 1.2rem;
        letter-spacing: 0.5px;
    }
    .company-form .form-title {
        margin-bottom: 2.5rem;
        font-size: 1.5rem;
        font-weight: 600;
    }
    .company-form .form-actions {
        margin-top: 2.5rem;
        display: flex;
        gap: 1rem;
    }
    .company-form label {
        font-weight: 500;
        margin-bottom: 0.3rem;
    }
    .company-form .form-group {
        margin-bottom: 1.2rem;
    }
    .company-form .logo-upload-area {
        margin-top: 0.5rem;
    }
    @media (max-width: 900px) {
        .company-form .form-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>
<div class="content">
    <div class="content-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h1 class="form-title">
            <?= isset($companyModel) ? 'Editar Empresa' : 'Agregar Nueva Empresa' ?>
        </h1>
        <a href="<?= isset($eventModel) ? (BASE_URL . '/events/companies/' . (int)$eventModel->getId()) : (BASE_URL . '/companies') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <?php if (isset($_SESSION['error_addcompany'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_addcompany']) ?>
        </div>
        <?php unset($_SESSION['error_addcompany']); ?>
    <?php endif; ?>
    <?php if (!isset($eventModel)): ?>
        <div class="alert alert-danger">Solo se puede crear o editar empresas desde el contexto de un evento.</div>
        <?php return; ?>
    <?php endif; ?>
    <div class="card form-card">
        <!-- Archivo eliminado: Este formulario legacy ya no debe usarse. -->
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

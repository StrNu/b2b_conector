listado<?php include(VIEW_DIR . '/shared/header.php'); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
<div class="content">
    <?php if (!isset($company) || !$company): ?>
        <div class="alert alert-danger">Error: No se encontró la empresa a editar. Por favor, regrese al listado.</div>
        <?php return; ?>
    <?php endif; ?>
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Editar Empresa</h1>
        <a href="<?= BASE_URL . '/events/companies/' . (isset($eventModel) && $eventModel ? (int)$eventModel->getId() : (int)$company->getEventId()) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="card max-w-2xl mx-auto p-6 bg-white rounded shadow">
        <form action="<?= isset($eventModel) 
                ? BASE_URL . '/events/companies/' . (int)$eventModel->getId() . '/edit/' . (int)$company->getId() 
                : BASE_URL . '/companies/edit/' . (int)$company->getId() ?>" 
            method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
            <div class="form-group">
                <label for="event_id">Evento</label>
                <input type="text" id="event_id" class="form-control" value="<?= htmlspecialchars($eventName) ?>" readonly>
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
            </div>
            <fieldset class="form-section mb-4">
                <legend class="section-title font-semibold mb-2">Datos de la Empresa</legend>
                <div class="form-group">
                    <label for="company_name">Nombre de la Empresa <span class="text-danger">*</span></label>
                    <input type="text" id="company_name" name="company_name" class="form-control" required value="<?= htmlspecialchars($company->getCompanyName()) ?>">
                </div>
                <div class="form-group">
                    <label for="address">Dirección</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($company->getAddress()) ?>">
                </div>
                <div class="form-group">
                    <label for="city">Ciudad</label>
                    <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($company->getCity()) ?>">
                </div>
                <div class="form-group">
                    <label for="country">País</label>
                    <input type="text" id="country" name="country" class="form-control" value="<?= htmlspecialchars($company->getCountry()) ?>">
                </div>
                <div class="form-group">
                    <label for="website">Sitio Web</label>
                    <input type="text" id="website" name="website" class="form-control" value="<?= htmlspecialchars($company->getWebsite()) ?>">
                </div>
                <div class="form-group">
                    <label for="company_logo">Logo de la Empresa</label>
                    <input type="file" class="form-control-file" id="company_logo" name="company_logo" accept="image/*">
                    <?php $logo = method_exists($company, 'getCompanyLogo') ? $company->getCompanyLogo() : null; ?>
                    <?php if ($logo): ?>
                        <div class="mt-2">
                            <img src="<?= BASE_PUBLIC_URL ?>/uploads/logos/<?= htmlspecialchars($logo) ?>" alt="Logo actual" style="max-height: 80px;">
                        </div>
                    <?php endif; ?>
                </div>
            </fieldset>
            <fieldset class="form-section mb-4">
                <legend class="section-title font-semibold mb-2">Contacto</legend>
                <div class="form-group">
                    <label for="contact_first_name">Nombre de Contacto</label>
                    <input type="text" id="contact_first_name" name="contact_first_name" class="form-control" value="<?= htmlspecialchars($company->getContactFirstName()) ?>">
                </div>
                <div class="form-group">
                    <label for="contact_last_name">Apellido de Contacto</label>
                    <input type="text" id="contact_last_name" name="contact_last_name" class="form-control" value="<?= htmlspecialchars($company->getContactLastName()) ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($company->getPhone()) ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($company->getEmail()) ?>">
                </div>
            </fieldset>
            <fieldset class="form-section mb-4">
                <legend class="section-title font-semibold mb-2">Configuración</legend>
                <div class="form-group">
                    <label for="role">Rol <span class="text-danger">*</span></label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="buyer" <?= ($company->getRole() === 'buyer') ? 'selected' : '' ?>>Comprador</option>
                        <option value="supplier" <?= ($company->getRole() === 'supplier') ? 'selected' : '' ?>>Proveedor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($company->getDescription()) ?></textarea>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?= $company->isActive() ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Empresa Activa</label>
                </div>
            </fieldset>
            <div class="form-actions flex justify-end gap-3 mt-4">
                <button type="button" class="btn btn-secondary" onclick="window.history.back();">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
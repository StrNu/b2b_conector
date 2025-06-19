<?php
// Vista: Alta de empresa desde el contexto de un evento
// Variables esperadas: $eventModel
?>
<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h1 class="form-title">Agregar Nueva Empresa</h1>
        <a href="<?= isset($eventModel) ? (BASE_URL . '/events/companies/' . (int)$eventModel->getId()) : (BASE_URL . '/companies') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <?php if (!isset($eventModel)): ?>
        <div class="alert alert-danger">Solo se puede crear o editar empresas desde el contexto de un evento.</div>
        <?php return; ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['validation_errors']) && is_array($_SESSION['validation_errors'])): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($_SESSION['validation_errors'] as $field => $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form class="company-form" method="post" enctype="multipart/form-data" action="<?= BASE_URL . '/events/companies/' . (int)$eventModel->getId() . '/create-company' ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="form-section">
            <div class="form-group">
                <label for="company_name">Nombre de la empresa *</label>
                <input type="text" name="company_name" id="company_name" class="form-control" required value="<?= htmlspecialchars($formData['company_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="address">Dirección</label>
                <input type="text" name="address" id="address" class="form-control" value="<?= htmlspecialchars($formData['address'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="city">Ciudad</label>
                <input type="text" name="city" id="city" class="form-control" value="<?= htmlspecialchars($formData['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="country">País</label>
                <input type="text" name="country" id="country" class="form-control" value="<?= htmlspecialchars($formData['country'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="website">Sitio Web</label>
                <input type="text" name="website" id="website" class="form-control" value="<?= htmlspecialchars($formData['website'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="company_logo">Logo</label>
                <input type="file" name="company_logo" id="company_logo" class="form-control">
            </div>
            <div class="form-group">
                <label for="contact_first_name">Nombre de Contacto</label>
                <input type="text" name="contact_first_name" id="contact_first_name" class="form-control" value="<?= htmlspecialchars($formData['contact_first_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="contact_last_name">Apellido de Contacto</label>
                <input type="text" name="contact_last_name" id="contact_last_name" class="form-control" value="<?= htmlspecialchars($formData['contact_last_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="phone">Teléfono</label>
                <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="role">Rol *</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <option value="buyer" <?= (isset($formData['role']) && $formData['role'] == 'buyer') ? 'selected' : '' ?>>Comprador</option>
                    <option value="supplier" <?= (isset($formData['role']) && $formData['role'] == 'supplier') ? 'selected' : '' ?>>Proveedor</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="keywords">Palabras clave</label>
                <small class="form-text text-muted">
                  Escribe las palabras clave separadas por comas. Ejemplo: acero inoxidable, ISO 9001, maquila textil
                </small>
                <input type="text" name="keywords" id="keywords" class="form-control" placeholder="Ej. acero inoxidable, ISO 9001, maquila textil" value="<?= htmlspecialchars($formData['keywords'] ?? '') ?>">
            </div>
            <fieldset class="form-group card mb-4">
                <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-certificate"></i> Certificaciones</legend>
                <div class="mb-2">
                    <label class="label">Certificaciones de Calidad y Gestión</label>
                    <div class="mb-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="certifications[]" value="ISO 9001" class="mr-2" <?= in_array('ISO 9001', $formData['certifications'] ?? []) ? 'checked' : '' ?>>
                            ISO 9001 – Gestión de calidad
                        </label><br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="certifications[]" value="ISO 14001" class="mr-2" <?= in_array('ISO 14001', $formData['certifications'] ?? []) ? 'checked' : '' ?>>
                            ISO 14001 – Gestión ambiental
                        </label><br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="certifications[]" value="ISO 45001" class="mr-2" <?= in_array('ISO 45001', $formData['certifications'] ?? []) ? 'checked' : '' ?>>
                            ISO 45001 – Seguridad y salud ocupacional
                        </label><br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="certifications[]" value="ISO 22000" class="mr-2" <?= in_array('ISO 22000', $formData['certifications'] ?? []) ? 'checked' : '' ?>>
                            ISO 22000 – Seguridad alimentaria
                        </label><br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="certifications[]" value="Six Sigma / Lean Six Sigma" class="mr-2" <?= in_array('Six Sigma / Lean Six Sigma', $formData['certifications'] ?? []) ? 'checked' : '' ?>>
                            Six Sigma / Lean Six Sigma – Mejora de procesos y eficiencia
                        </label>
                    </div>
                    <div class="mt-2">
                        <label class="label">Otros:</label>
                        <input type="text" name="certifications_otros" class="form-control" placeholder="Especifique otras certificaciones" value="<?= htmlspecialchars($formData['certifications_otros'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>
            <div class="form-group">
                <label for="is_active">
                    <input type="checkbox" name="is_active" id="is_active" value="1" <?= (!isset($formData['is_active']) || $formData['is_active']) ? 'checked' : '' ?>> Empresa activa
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Empresa
            </button>
            <a href="<?= BASE_URL . '/events/companies/' . (int)$eventModel->getId() ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

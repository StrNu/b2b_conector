<!-- views/events/create.php -->
<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content event-create-content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="event-create-title">Crear Nuevo Evento</h1>
        <div class="actions">
            <a href="<?= BASE_URL ?>/events" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="event-create-card">
        <form action="<?= BASE_URL ?>/events/store" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <!-- Empresa organizadora -->
            <fieldset class="form-section mb-6">
                <legend class="section-title">Empresa Organizadora</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name">Nombre Empresa Organizadora</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" value="<?= isset($_SESSION['form_data']['company_name']) ? htmlspecialchars($_SESSION['form_data']['company_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_name">Nombre de Contacto</label>
                        <input type="text" id="contact_name" name="contact_name" class="form-control" value="<?= isset($_SESSION['form_data']['contact_name']) ? htmlspecialchars($_SESSION['form_data']['contact_name']) : '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">Email de Contacto</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?= isset($_SESSION['form_data']['contact_email']) ? htmlspecialchars($_SESSION['form_data']['contact_email']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Teléfono de Contacto</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?= isset($_SESSION['form_data']['contact_phone']) ? htmlspecialchars($_SESSION['form_data']['contact_phone']) : '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group w-full">
                        <label for="company_logo">Logo de la Empresa</label>
                        <input type="file" class="form-control-file" id="company_logo" name="company_logo" accept="image/*">
                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</small>
                    </div>
                </div>
            </fieldset>
            <!-- Información del Evento -->
            <fieldset class="form-section mb-6">
                <legend class="section-title">Información del Evento</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_name">Nombre del Evento <span class="text-danger">*</span></label>
                        <input type="text" id="event_name" name="event_name" class="form-control" required value="<?= isset($_SESSION['form_data']['event_name']) ? htmlspecialchars($_SESSION['form_data']['event_name']) : '' ?>">
                        <?php if (isset($_SESSION['validation_errors']['event_name'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['event_name'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="venue">Sede <span class="text-danger">*</span></label>
                        <input type="text" id="venue" name="venue" class="form-control" required value="<?= isset($_SESSION['form_data']['venue']) ? htmlspecialchars($_SESSION['form_data']['venue']) : '' ?>">
                        <?php if (isset($_SESSION['validation_errors']['venue'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['venue'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">Ciudad <span class="text-danger">*</span></label>
                        <input type="text" id="city" name="city" class="form-control" required value="<?= isset($_SESSION['form_data']['city']) ? htmlspecialchars($_SESSION['form_data']['city']) : '' ?>">
                        <?php if (isset($_SESSION['validation_errors']['city'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['city'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="event_logo">Logo del Evento</label>
                        <input type="file" class="form-control-file" id="event_logo" name="event_logo" accept="image/*">
                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Fecha de Inicio <span class="text-danger">*</span></label>
                        <input type="text" id="start_date" name="start_date" class="form-control datepicker" required value="<?= isset($_SESSION['form_data']['start_date']) ? htmlspecialchars($_SESSION['form_data']['start_date']) : '' ?>">
                        <?php if (isset($_SESSION['validation_errors']['start_date'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['start_date'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="end_date">Fecha de Finalización <span class="text-danger">*</span></label>
                        <input type="text" id="end_date" name="end_date" class="form-control datepicker" required value="<?= isset($_SESSION['form_data']['end_date']) ? htmlspecialchars($_SESSION['form_data']['end_date']) : '' ?>">
                        <?php if (isset($_SESSION['validation_errors']['end_date'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['end_date'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="start_time">Hora de Inicio <span class="text-danger">*</span></label>
                        <input type="time" id="start_time" name="start_time" class="form-control" required value="<?= isset($_SESSION['form_data']['start_time']) ? htmlspecialchars($_SESSION['form_data']['start_time']) : '09:00' ?>">
                        <?php if (isset($_SESSION['validation_errors']['start_time'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['start_time'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="end_time">Hora de Finalización <span class="text-danger">*</span></label>
                        <input type="time" id="end_time" name="end_time" class="form-control" required value="<?= isset($_SESSION['form_data']['end_time']) ? htmlspecialchars($_SESSION['form_data']['end_time']) : '18:00' ?>">
                        <?php if (isset($_SESSION['validation_errors']['end_time'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['end_time'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>
            <!-- Configuración de Encuentros -->
            <fieldset class="form-section mb-6">
                <legend class="section-title">Configuración de Encuentros</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="meeting_duration">Duración de Reuniones (minutos) <span class="text-danger">*</span></label>
                        <input type="number" id="meeting_duration" name="meeting_duration" class="form-control" required min="5" step="5" value="<?= isset($_SESSION['form_data']['meeting_duration']) ? htmlspecialchars($_SESSION['form_data']['meeting_duration']) : '30' ?>">
                        <?php if (isset($_SESSION['validation_errors']['meeting_duration'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['meeting_duration'] ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Duración en minutos de cada cita entre compradores y proveedores</small>
                    </div>
                    <div class="form-group">
                        <label for="available_tables">Número de Mesas Disponibles <span class="text-danger">*</span></label>
                        <input type="number" id="available_tables" name="available_tables" class="form-control" required min="1" value="<?= isset($_SESSION['form_data']['available_tables']) ? htmlspecialchars($_SESSION['form_data']['available_tables']) : '10' ?>">
                        <?php if (isset($_SESSION['validation_errors']['available_tables'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['available_tables'] ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Cantidad de mesas disponibles para reuniones simultáneas</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="estimated_capacity">Capacidad Estimada</label>
                        <input type="number" id="estimated_capacity" name="estimated_capacity" class="form-control" readonly>
                        <small class="form-text text-muted">Número estimado de reuniones diarias (calculado automáticamente)</small>
                    </div>
                    <div class="form-group flex items-center mt-6">
                        <input type="checkbox" class="form-check-input mr-2" id="has_break" name="has_break" <?= isset($_SESSION['form_data']['has_break']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="has_break">Incluir Descansos</label>
                    </div>
                </div>
                <div id="breaks-container" class="mt-3 <?= isset($_SESSION['form_data']['has_break']) ? '' : 'd-none' ?>">
                    <h4 class="breaks-title">Configuración de Descansos</h4>
                    <p class="text-muted">Defina los periodos de descanso durante el evento (no se programarán citas en estos horarios)</p>
                    <div class="breaks-list">
                        <div class="break-item form-row">
                            <div class="form-group">
                                <label>Hora de Inicio</label>
                                <input type="time" name="break_start_time[]" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Hora de Fin</label>
                                <input type="time" name="break_end_time[]" class="form-control">
                            </div>
                            <div class="form-group flex items-end">
                                <button type="button" class="btn btn-sm btn-danger remove-break mb-3">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" id="add-break">
                        <i class="fas fa-plus"></i> Agregar Descanso
                    </button>
                </div>
            </fieldset>
            <!-- Usuario Administrador del Evento -->
            <fieldset class="form-section mb-6">
                <legend class="section-title">Usuario Administrador del Evento</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_email">Email del Administrador <span class="text-danger">*</span></label>
                        <input type="email" id="admin_email" name="admin_email" class="form-control" required value="<?= isset($_SESSION['form_data']['admin_email']) ? htmlspecialchars($_SESSION['form_data']['admin_email']) : '' ?>">
                        <?php if (isset($_SESSION['validation_errors']['admin_email'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['admin_email'] ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Se enviará un correo con las credenciales de acceso a esta dirección</small>
                    </div>
                    <div class="form-group">
                        <label for="admin_password">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" id="admin_password" name="admin_password" class="form-control" required minlength="6">
                        <?php if (isset($_SESSION['validation_errors']['admin_password'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['admin_password'] ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Mínimo 6 caracteres. Se enviará de forma segura por correo</small>
                    </div>
                </div>
            </fieldset>
            <!-- Estado del Evento -->
            <fieldset class="form-section mb-6">
                <legend class="section-title">Estado del Evento</legend>
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                        <label class="custom-control-label" for="is_active">Evento Activo</label>
                    </div>
                    <small class="form-text text-muted">Si está activado, el evento será visible para los usuarios y podrán registrarse</small>
                </div>
            </fieldset>
            <div class="form-actions flex justify-end gap-3 mt-8">
                <button type="button" class="btn btn-secondary" onclick="window.history.back();">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Evento</button>
            </div>
        </form>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
<!-- Flatpickr CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr('.datepicker', {
            dateFormat: 'Y-m-d',
            allowInput: true,
            locale: 'es'
        });
    });
</script>

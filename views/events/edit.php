<!-- views/events/edit.php -->
<div class="content">
    <div class="content-header">
        <h1>Editar Evento</h1>
        <div class="actions">
            <a href="<?= BASE_URL ?>/events" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/events/update/<?= $eventModel->getId() ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="event_name">Nombre del Evento <span class="text-danger">*</span></label>
                            <input type="text" id="event_name" name="event_name" class="form-control" required 
                                value="<?= isset($_SESSION['form_data']['event_name']) ? htmlspecialchars($_SESSION['form_data']['event_name']) : htmlspecialchars($eventModel->getEventName()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['event_name'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['event_name'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_name">Empresa Organizadora</label>
                            <input type="text" id="company_name" name="company_name" class="form-control" 
                                value="<?= isset($_SESSION['form_data']['company_name']) ? htmlspecialchars($_SESSION['form_data']['company_name']) : htmlspecialchars($eventModel->getCompanyName()) ?>">
                            <small class="form-text text-muted">Nombre de la empresa que organiza el evento</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="venue">Sede <span class="text-danger">*</span></label>
                            <input type="text" id="venue" name="venue" class="form-control" required 
                                value="<?= isset($_SESSION['form_data']['venue']) ? htmlspecialchars($_SESSION['form_data']['venue']) : htmlspecialchars($eventModel->getVenue()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['venue'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['venue'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="event_logo">Logo del Evento</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="event_logo" name="event_logo" accept="image/*">
                                <label class="custom-file-label" for="event_logo">Seleccionar archivo</label>
                            </div>
                            <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</small>
                            <?php if (!empty($eventModel->getEventLogo())): ?>
                                <div class="mt-2">
                                    <img src="<?= BASE_URL ?>/uploads/logos/<?= htmlspecialchars($eventModel->getEventLogo()) ?>" alt="Logo actual" class="img-thumbnail" style="max-height: 100px;">
                                    <p class="small text-muted">Logo actual</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="text" id="start_date" name="start_date" class="form-control datepicker" required 
                                value="<?= isset($_SESSION['form_data']['start_date']) ? htmlspecialchars($_SESSION['form_data']['start_date']) : dateFromDatabase($eventModel->getStartDate()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['start_date'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['start_date'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">Fecha de Finalización <span class="text-danger">*</span></label>
                            <input type="text" id="end_date" name="end_date" class="form-control datepicker" required 
                                value="<?= isset($_SESSION['form_data']['end_date']) ? htmlspecialchars($_SESSION['form_data']['end_date']) : dateFromDatabase($eventModel->getEndDate()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['end_date'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['end_date'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_time">Hora de Inicio <span class="text-danger">*</span></label>
                            <input type="time" id="start_time" name="start_time" class="form-control" required 
                                value="<?= isset($_SESSION['form_data']['start_time']) ? htmlspecialchars($_SESSION['form_data']['start_time']) : htmlspecialchars($eventModel->getStartTime()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['start_time'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['start_time'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_time">Hora de Finalización <span class="text-danger">*</span></label>
                            <input type="time" id="end_time" name="end_time" class="form-control" required 
                                value="<?= isset($_SESSION['form_data']['end_time']) ? htmlspecialchars($_SESSION['form_data']['end_time']) : htmlspecialchars($eventModel->getEndTime()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['end_time'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['end_time'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="available_tables">Número de Mesas Disponibles <span class="text-danger">*</span></label>
                            <input type="number" id="available_tables" name="available_tables" class="form-control" required min="1" 
                                value="<?= isset($_SESSION['form_data']['available_tables']) ? htmlspecialchars($_SESSION['form_data']['available_tables']) : htmlspecialchars($eventModel->getAvailableTables()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['available_tables'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['available_tables'] ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">Cantidad de mesas disponibles para reuniones simultáneas</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="meeting_duration">Duración de Reuniones (minutos) <span class="text-danger">*</span></label>
                            <input type="number" id="meeting_duration" name="meeting_duration" class="form-control" required min="5" step="5" 
                                value="<?= isset($_SESSION['form_data']['meeting_duration']) ? htmlspecialchars($_SESSION['form_data']['meeting_duration']) : htmlspecialchars($eventModel->getMeetingDuration()) ?>">
                            <?php if (isset($_SESSION['validation_errors']['meeting_duration'])): ?>
                                <div class="error-message"><?= $_SESSION['validation_errors']['meeting_duration'] ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">Duración en minutos de cada cita entre compradores y proveedores</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_name">Persona de Contacto</label>
                            <input type="text" id="contact_name" name="contact_name" class="form-control" 
                                value="<?= isset($_SESSION['form_data']['contact_name']) ? htmlspecialchars($_SESSION['form_data']['contact_name']) : htmlspecialchars($eventModel->getContactName()) ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_email">Email de Contacto</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-control" 
                                value="<?= isset($_SESSION['form_data']['contact_email']) ? htmlspecialchars($_SESSION['form_data']['contact_email']) : htmlspecialchars($eventModel->getContactEmail()) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_phone">Teléfono de Contacto</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="form-control" 
                                value="<?= isset($_SESSION['form_data']['contact_phone']) ? htmlspecialchars($_SESSION['form_data']['contact_phone']) : htmlspecialchars($eventModel->getContactPhone()) ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="has_break" name="has_break" 
                                <?= isset($_SESSION['form_data']['has_break']) ? 'checked' : ($eventModel->hasBreak() ? 'checked' : '') ?>>
                            <label class="form-check-label" for="has_break">Incluir Breaks/Descansos</label>
                            <small class="form-text text-muted">Si se activa, podrá configurar periodos de descanso durante el evento</small>
                        </div>
                    </div>
                </div>
                
                <div id="breaks-container" class="mt-3 <?= (isset($_SESSION['form_data']['has_break']) || $eventModel->hasBreak()) ? '' : 'd-none' ?>">
                    <h4>Configuración de Breaks</h4>
                    <p class="text-muted">Defina los periodos de descanso durante el evento (no se programarán citas en estos horarios)</p>
                    
                    <div class="breaks-list">
                        <?php if (!empty($breaks)): ?>
                            <?php foreach ($breaks as $index => $break): ?>
                                <div class="break-item row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Hora de Inicio</label>
                                            <input type="time" name="break_start_time[]" class="form-control" value="<?= substr($break['start_time'], 0, 5) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Hora de Fin</label>
                                            <input type="time" name="break_end_time[]" class="form-control" value="<?= substr($break['end_time'], 0, 5) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-danger remove-break mb-3">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="break-item row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Hora de Inicio</label>
                                        <input type="time" name="break_start_time[]" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Hora de Fin</label>
                                        <input type="time" name="break_end_time[]" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-danger remove-break mb-3">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-secondary" id="add-break">
                        <i class="fas fa-plus"></i> Agregar Break
                    </button>
                </div>
                
                <hr>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" 
                            <?= isset($_SESSION['form_data']['is_active']) ? 'checked' : ($eventModel->isActive() ? 'checked' : '') ?>>
                        <label class="custom-control-label" for="is_active">Evento Activo</label>
                    </div>
                    <small class="form-text text-muted">Si está activado, el evento será visible para los usuarios y podrán registrarse</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back();">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar u ocultar la sección de breaks según el checkbox
    const hasBreakCheckbox = document.getElementById('has_break');
    const breaksContainer = document.getElementById('breaks-container');
    
    hasBreakCheckbox.addEventListener('change', function() {
        if (this.checked) {
            breaksContainer.classList.remove('d-none');
        } else {
            breaksContainer.classList.add('d-none');
        }
    });
    
    // Agregar nuevo break
    const addBreakBtn = document.getElementById('add-break');
    const breaksList = document.querySelector('.breaks-list');
    
    addBreakBtn.addEventListener('click', function() {
        const breakItem = document.querySelector('.break-item').cloneNode(true);
        const inputs = breakItem.querySelectorAll('input');
        inputs.forEach(input => input.value = '');
        
        // Configurar el botón de eliminar
        const removeBtn = breakItem.querySelector('.remove-break');
        removeBtn.addEventListener('click', function() {
            if (document.querySelectorAll('.break-item').length > 1) {
                breakItem.remove();
            }
        });
        
        breaksList.appendChild(breakItem);
    });
    
    // Configurar el botón de eliminar para los breaks existentes
    document.querySelectorAll('.remove-break').forEach(button => {
        button.addEventListener('click', function() {
            if (document.querySelectorAll('.break-item').length > 1) {
                this.closest('.break-item').remove();
            }
        });
    });
    
    // Actualizar etiqueta del input de archivo
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'Seleccionar archivo';
        const fileLabel = this.nextElementSibling;
        fileLabel.textContent = fileName;
    });
});
</script>
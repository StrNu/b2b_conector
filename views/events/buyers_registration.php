<?php include(VIEW_DIR . '/shared/header.php'); ?>
</style>
<div class="container mx-auto py-8 max-w-2xl">
    <h1 class="text-3xl font-bold text-center mb-2">Registro para Compradores</h1>
    <p class="text-center text-gray-600 mb-6">Complete el siguiente formulario para <a href="#" class="text-primary underline">registrarse</a> como comprador en este evento. Podrá especificar sus requerimientos y productos de interés.</p>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <form action="<?= BASE_URL ?>/buyers_registration/<?= (int)$event->getId() ?>/store" method="POST" class="space-y-8 bg-white rounded-xl shadow-lg p-6" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <!-- Información de la Empresa -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-building"></i> Información de la Empresa</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="label">Nombre de la Empresa *</label>
                    <input type="text" name="company_name" class="form-control" required placeholder="Nombre de su empresa">
                </div>
                <div>
                    <label class="label">Sitio Web</label>
                    <input type="text" name="website" class="form-control" placeholder="www.example.com">
                </div>
            </div>
            <div class="mb-2">
                <label class="label">Descripción de la Empresa *</label>
                <textarea name="description" class="form-control" rows="10" required placeholder="Un párrafo de texto con información que describa el proceso productivo que hace su empresa"></textarea>
            </div>
            <div class="mb-2">
                <label class="label">Logo de la Empresa</label>
                <div class="border-dashed border-2 rounded flex flex-col items-center justify-center py-4 bg-gray-50">
                    <input type="file" name="logo" accept="image/*" class="hidden" id="logo-upload">
                    <label for="logo-upload" class="cursor-pointer btn btn-secondary">Seleccionar Archivo</label>
                    <span class="text-xs text-gray-400 mt-2" id="logo-upload-feedback">Arrastre una imagen o haga clic para seleccionar</span>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Ciudad</label>
                    <input type="text" name="city" class="form-control" placeholder="Ciudad">
                </div>
                <div>
                    <label class="label">País</label>
                    <input type="text" name="country" class="form-control" value="México">
                </div>
            </div>
        </fieldset>
        <!-- Datos de Contacto -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-user"></i> Datos de Contacto</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="label">Nombre *</label>
                    <input type="text" name="contact_first_name" class="form-control" required placeholder="Nombre">
                </div>
                <div>
                    <label class="label">Apellido *</label>
                    <input type="text" name="contact_last_name" class="form-control" required placeholder="Apellido">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="label">Teléfono Celular</label>
                    <input type="text" name="phone" class="form-control" placeholder="+52 222 123 4567">
                </div>
                <div>
                    <label class="label">Correo Electrónico *</label>
                    <input type="email" name="email" class="form-control" required placeholder="email@ejemplo.com">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Ciudad</label>
                    <input type="text" name="contact_city" class="form-control" placeholder="Ciudad">
                </div>
                <div>
                    <label class="label">País</label>
                    <input type="text" name="contact_country" class="form-control" value="México">
                </div>
            </div>
        </fieldset>
        <!-- Datos para Registro de la Cuenta -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-lock"></i> Datos para Registro de la Cuenta</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="label">Correo Electrónico *</label>
                    <input type="email" name="username" class="form-control" required placeholder="email@ejemplo.com">
                </div>
                <div>
                    <label class="label">Contraseña *</label>
                    <input type="password" name="password" class="form-control" required placeholder="********">
                </div>
                <div>
                    <label class="label">Repetir Contraseña *</label>
                    <input type="password" name="password_repeat" class="form-control" required placeholder="********">
                </div>
            </div>
        </fieldset>
        <!-- Registro de Asistentes al Evento -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-users"></i> Registro de Asistentes al Evento</legend>
            <div class="mb-2">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="same_as_contact" class="mr-2">
                    Hacer clic aquí en caso de que sea la misma persona que está llenando este formulario
                </label>
            </div>
            <div id="assistants-list">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2 assistant-item">
                    <div>
                        <label class="label">Nombre *</label>
                        <input type="text" name="assistants[0][first_name]" class="form-control" required placeholder="Nombre">
                    </div>
                    <div>
                        <label class="label">Apellido *</label>
                        <input type="text" name="assistants[0][last_name]" class="form-control" required placeholder="Apellido">
                    </div>
                    <div>
                        <label class="label">Teléfono Celular</label>
                        <input type="text" name="assistants[0][phone]" class="form-control" placeholder="+52 222 123 4567">
                    </div>
                    <div>
                        <label class="label">Correo Electrónico *</label>
                        <input type="email" name="assistants[0][email]" class="form-control" required placeholder="email@ejemplo.com">
                    </div>
                </div>
            </div>
            <button type="button" onclick="addAssistant()" class="btn btn-secondary btn-xs mt-2">+ Agregar otro asistente</button>
        </fieldset>
        <!-- Productos o Servicios de Interés -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-box"></i> Productos o Servicios de Interés</legend>
            <p class="text-gray-500 text-sm mb-2">Seleccione los productos o servicios que le interesan. Puede indicar un presupuesto aproximado y la cantidad requerida.</p>
            <div class="mb-2">
                <div class="flex flex-wrap gap-1 mb-2">
                    <?php foreach ($categories as $i => $cat): ?>
                        <button type="button" class="tab-btn btn btn-light px-2 py-1 text-xs md:text-sm <?= $i === 0 ? 'active' : '' ?>" data-tab="cat-<?= (int)$cat['event_category_id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($categories as $i => $cat): ?>
                    <div class="tab-panel <?= $i === 0 ? '' : 'hidden' ?>" id="cat-<?= (int)$cat['event_category_id'] ?>">
                        <?php if (!empty($subcategories[$cat['event_category_id']])): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs md:text-sm border">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="p-2 text-left">Requerimiento</th>
                                            <th class="p-2 text-left">Presupuesto en dólares</th>
                                            <th class="p-2 text-left">Cantidad</th>
                                            <th class="p-2 text-left">Unidad de medida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($subcategories[$cat['event_category_id']] as $sub): ?>
                                        <tr class="border-b">
                                            <td class="p-2">
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][selected]" value="1" class="req-checkbox">
                                                    <span><?= htmlspecialchars($sub['name']) ?></span>
                                                </label>
                                            </td>
                                            <td class="p-2">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-gray-400">$</span>
                                                    <input type="number" step="0.01" min="0" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][budget]" class="form-control w-24 req-budget" disabled>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <input type="number" min="1" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][quantity]" class="form-control w-16 req-qty" disabled>
                                            </td>
                                            <td class="p-2">
                                                <select name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][unit]" class="form-control req-unit" disabled>
                                                    <option value="">Selecciona</option>
                                                    <option value="pieza">Pieza</option>
                                                    <option value="kg">Kg</option>
                                                    <option value="ton">Tonelada</option>
                                                    <option value="servicio">Servicio</option>
                                                    <option value="otro">Otro</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">No hay subcategorías para esta categoría.</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <!-- Días de Asistencia -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-calendar-alt"></i> Días de Asistencia</legend>
            <p class="text-gray-500 text-sm mb-2">Seleccione los días que planea asistir al evento.</p>
            <div class="flex flex-wrap gap-4">
                <?php
                // Obtener rango de días del evento
                $startDate = new DateTime($event->getStartDate());
                $endDate = new DateTime($event->getEndDate());
                $interval = $startDate->diff($endDate);
                $totalDays = $interval->days + 1;
                $eventDays = [];
                $currentDate = clone $startDate;
                for ($i = 0; $i < $totalDays; $i++) {
                    $eventDays[] = $currentDate->format('Y-m-d');
                    $currentDate->modify('+1 day');
                }
                ?>
                <?php foreach ($eventDays as $day): ?>
                    <label class="inline-flex items-center mr-4 mb-2">
                        <input type="checkbox" name="attendance_days[]" value="<?= htmlspecialchars($day) ?>" class="mr-2">
                        <?= htmlspecialchars(date('d/m/Y', strtotime($day))) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div class="text-center">
            <button type="submit" class="btn btn-primary px-8 py-2 text-lg">Registrarse</button>
        </div>
    </form>
</div>
<script>
let assistantIndex = 1;
function addAssistant() {
    const list = document.getElementById('assistants-list');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-2 assistant-item';
    div.innerHTML = `<div><label class='label'>Nombre *</label><input type='text' name='assistants[${assistantIndex}][first_name]' class='form-control' required placeholder='Nombre'></div><div><label class='label'>Apellido *</label><input type='text' name='assistants[${assistantIndex}][last_name]' class='form-control' required placeholder='Apellido'></div><div><label class='label'>Teléfono Celular</label><input type='text' name='assistants[${assistantIndex}][phone]' class='form-control' placeholder='+52 222 123 4567'></div><div><label class='label'>Correo Electrónico *</label><input type='email' name='assistants[${assistantIndex}][email]' class='form-control' required placeholder='email@ejemplo.com'></div>`;
    list.appendChild(div);
    assistantIndex++;
}
// Tabs para categorías
const tabBtns = document.querySelectorAll('.tab-btn');
const tabPanels = document.querySelectorAll('.tab-panel');
tabBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanels.forEach(p => p.classList.add('hidden'));
        btn.classList.add('active');
        const tabId = btn.getAttribute('data-tab');
        document.getElementById(tabId).classList.remove('hidden');
    });
});
// Checkbox para copiar datos de contacto al primer asistente
const sameAsContact = document.querySelector('input[name="same_as_contact"]');
if (sameAsContact) {
    sameAsContact.addEventListener('change', function() {
        if (this.checked) {
            document.querySelector('input[name="assistants[0][first_name]"]').value = document.querySelector('input[name="contact_first_name"]').value;
            document.querySelector('input[name="assistants[0][last_name]"]').value = document.querySelector('input[name="contact_last_name"]').value;
            document.querySelector('input[name="assistants[0][email]"]').value = document.querySelector('input[name="email"]').value;
            document.querySelector('input[name="assistants[0][phone]"]').value = document.querySelector('input[name="phone"]').value;
        }
    });
}

// Script para mostrar el nombre del archivo del logo seleccionado
const logoUploadInput = document.getElementById('logo-upload');
const logoUploadFeedback = document.getElementById('logo-upload-feedback');

if (logoUploadInput && logoUploadFeedback) {
    logoUploadInput.addEventListener('change', function(event) {
        if (event.target.files && event.target.files.length > 0) {
            logoUploadFeedback.textContent = event.target.files[0].name;
        } else {
            logoUploadFeedback.textContent = 'Arrastre una imagen o haga clic para seleccionar'; // Texto por defecto
        }
    });
}

// Habilitar/Deshabilitar inputs según el checkbox
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.req-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const row = checkbox.closest('tr');
            row.querySelector('.req-budget').disabled = !checkbox.checked;
            row.querySelector('.req-qty').disabled = !checkbox.checked;
            row.querySelector('.req-unit').disabled = !checkbox.checked;
        });
    });
});
</script>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

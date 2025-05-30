<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="container mx-auto py-8 max-w-2xl">
    <h1 class="text-3xl font-bold text-center mb-2">Registro para Proveedores</h1>
    <p class="text-center text-gray-600 mb-6">Complete el siguiente formulario para registrarse como proveedor en este evento. Podrá especificar sus ofertas y productos disponibles.</p>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <form action="<?= BASE_URL ?>/suppliers_registration/<?= (int)$event->getId() ?>/store" method="POST" enctype="multipart/form-data" class="space-y-8 bg-white rounded-xl shadow-lg p-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <!-- Información de la Empresa -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-building"></i> Información de la Empresa</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><label class="block font-semibold mb-1">Nombre de la Empresa *</label><input type="text" name="company_name" class="form-control w-full" required placeholder="Nombre de su empresa"></div>
                <div><label class="block font-semibold mb-1">Sitio Web</label><input type="text" name="website" class="form-control w-full" placeholder="www.example.com"></div>
            </div>
            <div class="mb-2"><label class="block font-semibold mb-1">Descripción de la Empresa *</label><textarea name="description" class="form-control w-full" required placeholder="Describe brevemente tu empresa"></textarea></div>
            <div class="mb-2">
                <label class="block font-semibold mb-1">Logo de la Empresa</label>
                <input type="file" name="company_logo" class="form-control-file">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><label class="block font-semibold mb-1">Ciudad</label><input type="text" name="city" class="form-control w-full" placeholder="Ciudad"></div>
                <div><label class="block font-semibold mb-1">País</label><input type="text" name="country" class="form-control w-full" placeholder="País"></div>
            </div>
        </fieldset>
        <!-- Datos de Contacto -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-user"></i> Datos de Contacto</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><label class="block font-semibold mb-1">Nombre *</label><input type="text" name="contact_first_name" class="form-control w-full" required></div>
                <div><label class="block font-semibold mb-1">Apellido *</label><input type="text" name="contact_last_name" class="form-control w-full" required></div>
                <div><label class="block font-semibold mb-1">Teléfono Celular *</label><input type="text" name="phone" class="form-control w-full" required placeholder="+52 222 123 4567"></div>
                <div><label class="block font-semibold mb-1">Correo Electrónico *</label><input type="email" name="email" class="form-control w-full" required></div>
                <div><label class="block font-semibold mb-1">Ciudad</label><input type="text" name="contact_city" class="form-control w-full"></div>
                <div><label class="block font-semibold mb-1">País</label><input type="text" name="contact_country" class="form-control w-full"></div>
            </div>
        </fieldset>
        <!-- Datos para Registro de la Cuenta -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-id-card"></i> Datos para Registro de la Cuenta</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><label class="block font-semibold mb-1">Correo Electrónico *</label><input type="email" name="account_email" class="form-control w-full" required></div>
                <div><label class="block font-semibold mb-1">Contraseña *</label><input type="password" name="password" class="form-control w-full" required></div>
                <div><label class="block font-semibold mb-1">Repetir Contraseña *</label><input type="password" name="confirm_password" class="form-control w-full" required></div>
            </div>
        </fieldset>
        <!-- Registro de Asistentes al Evento -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-users"></i> Registro de Asistentes al Evento</legend>
            <div class="mb-2">
                <label class="inline-flex items-center"><input type="checkbox" name="is_self_attendee" class="mr-2" id="is_self_attendee" onclick="toggleSelfAttendee()"> Hacer clic aquí en caso de que sea la misma persona que está llenando este formulario</label>
            </div>
            <div id="assistants-list">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2 assistant-item">
                    <div><label class="block font-semibold mb-1">Nombre *</label><input type="text" name="assistant_first_name[]" class="form-control w-full" required id="assistant_first_name_0"></div>
                    <div><label class="block font-semibold mb-1">Apellido *</label><input type="text" name="assistant_last_name[]" class="form-control w-full" required id="assistant_last_name_0"></div>
                    <div><label class="block font-semibold mb-1">Teléfono Celular *</label><input type="text" name="assistant_phone[]" class="form-control w-full" required id="assistant_phone_0"></div>
                    <div><label class="block font-semibold mb-1">Correo Electrónico *</label><input type="email" name="assistant_email[]" class="form-control w-full" required id="assistant_email_0"></div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-primary mt-2" onclick="addAssistant()">+ Agregar otro asistente</button>
        </fieldset>
        <!-- Ofertas de Proveedor -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-box"></i> Ofertas de Proveedor</legend>
            <p class="text-gray-500 text-sm mb-2">Seleccione los productos o servicios que ofrece. Puede seleccionar varias opciones.</p>
            <div>
                <div class="flex flex-wrap gap-2 mb-4" id="offer-tabs">
                    <?php $firstTab = true; foreach ($categories as $cat): ?>
                        <button type="button" class="tab-btn px-3 py-1 rounded-full text-sm font-semibold <?php if ($firstTab) echo 'bg-gray-800 text-white'; else echo 'bg-gray-100 text-gray-800'; ?>" data-tab="tab-<?= (int)$cat['event_category_id'] ?>" onclick="showTab('tab-<?= (int)$cat['event_category_id'] ?>', this)">
                            <?= htmlspecialchars($cat['name']) ?>
                        </button>
                    <?php $firstTab = false; endforeach; ?>
                </div>
                <?php $firstTab = true; foreach ($categories as $cat): ?>
                    <div class="tab-content" id="tab-<?= (int)$cat['event_category_id'] ?>" style="<?php if (!$firstTab) echo 'display:none;'; ?>">
                        <?php if (!empty($subcategories[$cat['event_category_id']])): ?>
                            <?php foreach ($subcategories[$cat['event_category_id']] as $sub): ?>
                                <label class="inline-flex items-center mr-4 mb-2">
                                    <input type="checkbox" name="supplier_offers[]" value="<?= (int)$sub['event_subcategory_id'] ?>" class="mr-2">
                                    <?= htmlspecialchars($sub['name']) ?>
                                </label><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">No hay subcategorías para esta categoría.</span>
                        <?php endif; ?>
                    </div>
                <?php $firstTab = false; endforeach; ?>
            </div>
        </fieldset>
        <!-- Días de Asistencia -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-calendar-alt"></i> Días de Asistencia</legend>
            <p class="text-gray-500 text-sm mb-2">Seleccione los días que planea asistir al evento.</p>
            <div class="flex flex-wrap gap-4">
                <?php if (!empty($eventDays)): ?>
                    <?php foreach ($eventDays as $day): ?>
                        <label class="inline-flex items-center mr-4 mb-2">
                            <input type="checkbox" name="attendance_days[]" value="<?= htmlspecialchars($day) ?>" class="mr-2">
                            <?= htmlspecialchars(date('d/m/Y', strtotime($day))) ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-gray-500">No hay fechas configuradas para este evento.</span>
                <?php endif; ?>
            </div>
        </fieldset>
        <div class="text-center mt-8">
            <button type="submit" class="btn btn-primary px-8 py-2 text-lg">Registrarse</button>
        </div>
    </form>
</div>
<script>
function addAssistant() {
    const list = document.getElementById('assistants-list');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-2 assistant-item';
    div.innerHTML = `
        <div><label class='block font-semibold mb-1'>Nombre *</label><input type='text' name='assistant_first_name[]' class='form-control w-full' required></div>
        <div><label class='block font-semibold mb-1'>Apellido *</label><input type='text' name='assistant_last_name[]' class='form-control w-full' required></div>
        <div><label class='block font-semibold mb-1'>Teléfono Celular *</label><input type='text' name='assistant_phone[]' class='form-control w-full' required></div>
        <div><label class='block font-semibold mb-1'>Correo Electrónico *</label><input type='email' name='assistant_email[]' class='form-control w-full' required></div>
    `;
    list.appendChild(div);
}

function toggleSelfAttendee() {
    const checked = document.getElementById('is_self_attendee').checked;
    const contactFirstName = document.querySelector('input[name="contact_first_name"]').value;
    const contactLastName = document.querySelector('input[name="contact_last_name"]').value;
    const contactPhone = document.querySelector('input[name="phone"]').value;
    const contactEmail = document.querySelector('input[name="email"]').value;
    document.getElementById('assistant_first_name_0').value = checked ? contactFirstName : '';
    document.getElementById('assistant_last_name_0').value = checked ? contactLastName : '';
    document.getElementById('assistant_phone_0').value = checked ? contactPhone : '';
    document.getElementById('assistant_email_0').value = checked ? contactEmail : '';
}

function showTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(function(tab) {
        tab.style.display = 'none';
    });
    document.getElementById(tabId).style.display = '';
    document.querySelectorAll('#offer-tabs .tab-btn').forEach(function(b) {
        b.classList.remove('bg-gray-800', 'text-white');
        b.classList.add('bg-gray-100', 'text-gray-800');
    });
    btn.classList.remove('bg-gray-100', 'text-gray-800');
    btn.classList.add('bg-gray-800', 'text-white');
}
</script>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

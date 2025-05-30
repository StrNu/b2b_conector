<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Gestión de Asistentes del Evento</h1>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/events/companies/<?= $eventId ?>/create-company" class="btn btn-success"><i class="fas fa-building"></i> Agregar empresa</a>
            <a href="<?= BASE_URL ?>/events/view/<?= $eventId ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al evento</a>
        </div>
    </div>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="mb-6">
        <form action="<?= BASE_URL ?>/events/addParticipant/<?= $eventId ?>" method="POST" class="flex flex-wrap gap-2 items-end bg-gray-50 p-3 rounded shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="w-32">
                <label class="block text-gray-700">Nombre</label>
                <input type="text" name="first_name" class="form-control form-control-sm" required value="<?= htmlspecialchars($_SESSION['form_data']['first_name'] ?? '') ?>">
                <?php if (isset($_SESSION['validation_errors']['first_name'])): ?>
                    <div class="error-message text-xs"><?= $_SESSION['validation_errors']['first_name'] ?></div>
                <?php endif; ?>
            </div>
            <div class="w-32">
                <label class="block text-gray-700">Apellido</label>
                <input type="text" name="last_name" class="form-control form-control-sm" required value="<?= htmlspecialchars($_SESSION['form_data']['last_name'] ?? '') ?>">
                <?php if (isset($_SESSION['validation_errors']['last_name'])): ?>
                    <div class="error-message text-xs"><?= $_SESSION['validation_errors']['last_name'] ?></div>
                <?php endif; ?>
            </div>
            <div class="w-40">
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" class="form-control form-control-sm" required value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>">
                <?php if (isset($_SESSION['validation_errors']['email'])): ?>
                    <div class="error-message text-xs"><?= $_SESSION['validation_errors']['email'] ?></div>
                <?php endif; ?>
            </div>
            <div class="w-32">
                <label class="block text-gray-700">Teléfono</label>
                <input type="text" name="mobile_phone" class="form-control form-control-sm" value="<?= htmlspecialchars($_SESSION['form_data']['mobile_phone'] ?? '') ?>">
            </div>
            <div class="w-40">
                <label class="block text-gray-700">Empresa</label>
                <select name="company_id" class="form-control form-control-sm" required>
                    <option value="">Seleccione una empresa</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['company_id'] ?>" <?= (isset($_SESSION['form_data']['company_id']) && $_SESSION['form_data']['company_id'] == $company['company_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($_SESSION['validation_errors']['company_id'])): ?>
                    <div class="error-message text-xs"><?= $_SESSION['validation_errors']['company_id'] ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary h-8 px-3 text-sm flex items-center"><i class="fas fa-plus mr-1"></i>Agregar</button>
        </form>
        <?php unset($_SESSION['form_data'], $_SESSION['validation_errors']); ?>
    </div>
    <!-- Buscador de asistentes -->
    <div class="mb-4 flex items-center gap-2">
        <input type="text" id="searchParticipant" class="form-control form-control-sm w-64" placeholder="Buscar por nombre, apellido o email...">
        <span class="text-xs text-gray-500">(Búsqueda en tiempo real)</span>
    </div>
    <div class="overflow-x-auto">
        <table id="participantsTable" class="table-auto w-full bg-white rounded shadow border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Nombre</th>
                    <th class="px-4 py-2">Apellido</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Teléfono</th>
                    <th class="px-4 py-2">Empresa</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($participants)): ?>
                    <?php foreach ($participants as $a): ?>
                        <tr>
                            <td class="px-4 py-2"><?= htmlspecialchars($a['first_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($a['last_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($a['email']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($a['mobile_phone']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($a['company_name']) ?></td>
                            <td class="px-4 py-2 flex gap-2">
                                <a href="<?= BASE_URL ?>/events/editParticipant/<?= $eventId ?>/<?= $a['assistant_id'] ?>" class="btn btn-xs btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="<?= BASE_URL ?>/events/deleteParticipant/<?= $eventId ?>/<?= $a['assistant_id'] ?>" method="POST" onsubmit="return confirm('¿Eliminar este asistente?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-gray-500">No hay asistentes registrados para este evento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchParticipant');
    const table = document.getElementById('participantsTable');
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

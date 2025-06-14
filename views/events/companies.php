<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Empresas Registradas</h1>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/events/view/<?= (int)$eventId ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver al Evento</a>
            <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventId ?>/create-company" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nueva Empresa</a>
        </div>
    </div>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="card bg-white p-4 rounded shadow mb-6">
        <form method="GET" class="flex gap-2 items-end mb-4" onsubmit="return false;">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <input type="text" name="search" class="form-control form-control-sm w-64"
                placeholder="Buscar por nombre de empresa..."
                id="searchCompany"
                data-autosearch="companiesTable" data-search-field="company_name"
                autocomplete="off">
            <select name="role" class="form-control form-control-sm w-40" id="roleFilter" data-role-filter>
                <option value="">Todos</option>
                <option value="buyer" <?= (($_GET['role'] ?? '') === 'buyer') ? 'selected' : '' ?>>Comprador</option>
                <option value="supplier" <?= (($_GET['role'] ?? '') === 'supplier') ? 'selected' : '' ?>>Proveedor</option>
            </select>
        </form>
        <div class="overflow-x-auto">
            <table id="companiesTable" class="table-auto w-full bg-white rounded shadow border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2">Logo</th>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">Contacto</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Teléfono</th>
                        <th class="px-3 py-2">Rol</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                            <tr data-role="<?= $company['role'] ?>">
                                <td class="px-3 py-2 text-center">
                                    <?php if (!empty($company['company_logo'])): ?>
                                        <img src="<?= BASE_URL ?>/uploads/logos/<?= htmlspecialchars($company['company_logo']) ?>" alt="Logo" style="max-height:40px;max-width:60px;">
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Sin logo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-800" data-search-field="company_name">
                                    <?= htmlspecialchars($company['company_name']) ?>
                                </td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($company['contact_first_name'] . ' ' . $company['contact_last_name']) ?>
                                </td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($company['email']) ?>
                                </td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($company['phone']) ?>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-block rounded px-2 py-1 text-xs font-semibold <?= $company['role'] === 'buyer' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $company['role'] === 'buyer' ? 'Comprador' : 'Proveedor' ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 flex gap-2">
                                    <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventId ?>/view/<?= (int)$company['company_id'] ?>" class="btn btn-xs btn-secondary" title="Ver"><i class="fas fa-eye"></i></a>
                                    <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventId ?>/full_registration/<?= (int)$company['company_id'] ?>" class="btn btn-xs btn-info" title="Ver registro completo"><i class="fas fa-list"></i> Ver registro completo</a>
                                    <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventId ?>/edit/<?= (int)$company['company_id'] ?>" class="btn btn-xs btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                    <form action="<?= BASE_URL ?>/events/companies/<?= (int)$eventId ?>/delete/<?= (int)$company['company_id'] ?>" method="POST" onsubmit="return confirm('Esta acción eliminará la empresa y todos los matches ligados a ella. ¿Está seguro de continuar?');" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                        <button type="submit" class="btn btn-xs btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-gray-500">No hay empresas registradas para este evento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        // Filtro por rol en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const roleFilter = document.querySelector('[data-role-filter]');
            const table = document.getElementById('companiesTable');
            if (roleFilter && table) {
                roleFilter.addEventListener('change', function() {
                    const value = this.value;
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        if (!value || row.getAttribute('data-role') === value) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
        </script>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

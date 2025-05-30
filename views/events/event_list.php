<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Registros Completos de Empresas</h1>
        <a href="<?= BASE_URL ?>/events/view/<?= (int)$eventId ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver al Evento</a>
    </div>
    <form method="GET" class="flex gap-2 items-end mb-4" onsubmit="return false;">
        <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
        <input type="text" name="search" class="form-control form-control-sm w-64"
            placeholder="Buscar por nombre, contacto, email..."
            id="searchCompany"
            data-autosearch="companiesTable"
            autocomplete="off">
        <select name="role" class="form-control form-control-sm w-40" id="roleFilter">
            <option value="">Todos</option>
            <option value="buyer" <?= (($_GET['role'] ?? '') === 'buyer') ? 'selected' : '' ?>>Comprador</option>
            <option value="supplier" <?= (($_GET['role'] ?? '') === 'supplier') ? 'selected' : '' ?>>Proveedor</option>
        </select>
        <select name="order" class="form-control form-control-sm w-40" id="orderSelect">
            <option value="asc" <?= (($_GET['order'] ?? 'asc') === 'asc') ? 'selected' : '' ?>>Ascendente</option>
            <option value="desc" <?= (($_GET['order'] ?? '') === 'desc') ? 'selected' : '' ?>>Descendente</option>
        </select>
    </form>
    <script src="<?= BASE_URL ?>/assets/js/components/autosearch.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderSelect = document.getElementById('orderSelect');
        const roleFilter = document.getElementById('roleFilter');
        const searchInput = document.getElementById('searchCompany');
        const table = document.getElementById('companiesTable');
        orderSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('order', this.value);
            params.set('event_id', <?= (int)$eventId ?>);
            params.set('role', roleFilter.value);
            window.location.search = params.toString();
        });
        roleFilter.addEventListener('change', filterAndRenumber);
        searchInput.addEventListener('input', filterAndRenumber);
        function filterAndRenumber() {
            const search = searchInput.value.toLowerCase();
            const role = roleFilter.value;
            const order = orderSelect.value;
            // Filtrar filas visibles
            let visibleRows = [];
            table.querySelectorAll('tbody tr').forEach(row => {
                const rowRole = row.getAttribute('data-role');
                const name = row.querySelector('[data-search-field="company_name"]').textContent.toLowerCase();
                const contacto = row.querySelector('[data-search-field="contact"]').textContent.toLowerCase();
                const email = row.querySelector('[data-search-field="email"]').textContent.toLowerCase();
                let visible = true;
                if (role && rowRole !== role) visible = false;
                if (search && !(name.includes(search) || contacto.includes(search) || email.includes(search))) visible = false;
                row.style.display = visible ? '' : 'none';
                if (visible) visibleRows.push(row);
            });
            // Numerar según orden
            let n = (order === 'desc') ? visibleRows.length : 1;
            visibleRows.forEach(row => {
                row.querySelector('td').textContent = '#' + n;
                if (order === 'desc') {
                    n--;
                } else {
                    n++;
                }
            });
        }
        filterAndRenumber();
    });
    </script>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="overflow-x-auto">
        <table id="companiesTable" class="table-auto w-full bg-white rounded shadow border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">#</th>
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
                            <td class="px-3 py-2 text-center">#<?= (int)$company['company_id'] ?></td>
                            <td class="px-3 py-2 font-medium text-gray-800" data-search-field="company_name">
                                <?= htmlspecialchars($company['company_name']) ?>
                            </td>
                            <td class="px-3 py-2" data-search-field="contact">
                                <?= htmlspecialchars($company['contact_first_name'] . ' ' . $company['contact_last_name']) ?>
                            </td>
                            <td class="px-3 py-2" data-search-field="email">
                                <?= htmlspecialchars($company['email']) ?>
                            </td>
                            <td class="px-3 py-2">
                                <?= htmlspecialchars($company['phone']) ?>
                            </td>
                            <td class="px-3 py-2">
                                <?= $company['role'] === 'buyer' ? 'Comprador' : 'Proveedor' ?>
                            </td>
                            <td class="px-3 py-2">
                                <a href="<?= BASE_URL ?>/events/companies/<?= (int)$eventId ?>/full_registration/<?= (int)$company['company_id'] ?>" class="btn btn-xs btn-info" title="Ver registro completo"><i class="fas fa-list"></i> Ver registro completo</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4 text-gray-500">No hay empresas registradas para este evento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

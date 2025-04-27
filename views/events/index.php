<!-- views/events/index.php -->
<?php include(VIEW_DIR . '/shared/header.php'); ?>

<div class="content">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Eventos</h1>
            <p class="text-gray-500 text-sm">Crea y gestiona tus eventos B2B.</p>
        </div>
        <a href="<?= BASE_URL ?>/events/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition">
            <i class="fas fa-plus"></i> Nuevo Evento
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <form method="GET" action="<?= BASE_URL ?>/events" class="flex flex-col md:flex-row gap-3 mb-6">
        <div class="flex-1">
            <label class="sr-only" for="search">Buscar eventos</label>
            <div class="relative">
                <input type="text" name="search" id="search" class="w-full border border-gray-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Buscar eventos..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div>
            <select name="status" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="1" <?= isset($_GET['status']) && $_GET['status'] === '1' ? 'selected' : '' ?>>Activos</option>
                <option value="0" <?= isset($_GET['status']) && $_GET['status'] === '0' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/events" class="px-4 py-2 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 flex items-center gap-2 <?= strpos($_SERVER['REQUEST_URI'], '/events/list') === false ? 'ring-2 ring-blue-500' : '' ?>">
                <i class="fas fa-list"></i> Tabla
            </a>
            <a href="<?= BASE_URL ?>/events/list" class="px-4 py-2 rounded border border-blue-600 text-blue-700 bg-blue-50 hover:bg-blue-100 flex items-center gap-2 <?= strpos($_SERVER['REQUEST_URI'], '/events/list') !== false ? 'ring-2 ring-blue-500' : '' ?>">
                <i class="fas fa-th-large"></i> Tarjetas
            </a>
        </div>
    </form>
    <div class="bg-white rounded-lg shadow-md border border-gray-200">
        <div class="p-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Listado de Eventos</h2>
        </div>
        <div class="p-4">
            <?php if (empty($events)): ?>
                <div class="flex flex-col items-center justify-center py-12 text-center text-gray-500">
                    <div class="text-5xl mb-2"><i class="fas fa-calendar-times"></i></div>
                    <h3 class="text-xl font-semibold mb-1">No hay eventos disponibles</h3>
                    <p class="mb-4">No se encontraron eventos con los criterios de búsqueda actuales.</p>
                    <a href="<?= BASE_URL ?>/events/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition">
                        <i class="fas fa-plus"></i> Crear Nuevo Evento
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Inicio</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Fin</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= $event['event_id'] ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900 font-medium"><?= htmlspecialchars($event['event_name']) ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($event['venue']) ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= dateFromDatabase($event['start_date']) ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= dateFromDatabase($event['end_date']) ?></td>
                                    <td class="px-4 py-2">
                                        <span class="inline-block px-2 py-1 text-xs rounded-full <?= $event['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= $event['is_active'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 flex gap-2">
                                        <a href="<?= BASE_URL ?>/events/view/<?= $event['event_id'] ?>" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/events/edit/<?= $event['event_id'] ?>" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
        class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded transition" 
        onclick="confirmDelete('<?= $event['event_id'] ?>', '<?= htmlspecialchars(addslashes($event['event_name'])) ?>')"
        title="Eliminar">
    <i class="fas fa-trash"></i>
</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Paginación -->
                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                    <div class="flex justify-center mt-6">
                        <?= paginationLinks($pagination, BASE_URL . '/events?page=') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include(VIEW_DIR . '/shared/footer.php'); ?>

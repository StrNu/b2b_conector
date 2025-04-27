<!-- views/events/list.php -->
<?php include(VIEW_DIR . '/shared/header.php'); ?>

<div class="content">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Eventos</h1>
            <p class="text-gray-500 text-sm">Crea y gestiona tus eventos B2B.</p>
        </div>
        <a href="<?= BASE_URL ?>/events/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition">
            <i class="fas fa-plus"></i> Crear Evento
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <form method="GET" action="<?= BASE_URL ?>/events/list" class="flex flex-col md:flex-row gap-3 mb-6">
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
    
    <?php if (empty($events)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h3>No hay eventos disponibles</h3>
            <p>No se encontraron eventos con los criterios de búsqueda actuales.</p>
            <a href="<?= BASE_URL ?>/events/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Crear Nuevo Evento
            </a>
        </div>
    <?php else: ?>
        <!-- Vista de Tarjetas con Tailwind CSS -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($events as $event): ?>
        <div class="bg-white rounded-lg shadow-md border <?= $event['is_active'] ? 'border-green-500' : 'border-gray-300' ?> overflow-hidden hover:shadow-lg transition-shadow">
            <!-- Cabecera de tarjeta -->
            <div class="p-4 border-b">
                <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($event['event_name']) ?></h3>
                <p class="text-gray-600"><?= htmlspecialchars($event['venue']) ?></p>
            </div>
            
            <!-- Cuerpo de tarjeta -->
            <div class="p-4">
                <!-- Fechas -->
                <div class="flex items-center mb-3">
                    <span class="text-blue-500 mr-2">
                        <i class="far fa-calendar-alt"></i>
                    </span>
                    <span class="text-gray-700">
                        <?= dateFromDatabase($event['start_date']) ?> - <?= dateFromDatabase($event['end_date']) ?>
                    </span>
                </div>
                
                <!-- Detalles -->
                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-gray-700">
                        <span class="text-blue-500 mr-2">
                            <i class="fas fa-table"></i>
                        </span>
                        <span>Mesas: <?= htmlspecialchars($event['available_tables']) ?></span>
                    </div>
                    
                    <div class="flex items-center text-gray-700">
                        <span class="text-blue-500 mr-2">
                            <i class="fas fa-clock"></i>
                        </span>
                        <span>Duración de reuniones: <?= htmlspecialchars($event['meeting_duration']) ?> min</span>
                    </div>
                </div>
                
                <!-- Estado -->
                <div class="mb-4">
                    <span class="inline-block px-2 py-1 text-xs rounded-full <?= $event['is_active'] 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-gray-100 text-gray-800' ?>">
                        <?= $event['is_active'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
            </div>
            
            <!-- Pie de tarjeta con acciones -->
            <div class="p-4 bg-gray-50 border-t">
                <a href="<?= BASE_URL ?>/events/view/<?= $event['event_id'] ?>" 
                   class="block w-full py-2 mb-2 text-center bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors">
                    Ir al Evento <i class="fas fa-arrow-right ml-1"></i>
                </a>
                
                <div class="flex justify-between">
                    <a href="<?= BASE_URL ?>/events/edit/<?= $event['event_id'] ?>" 
                       class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 transition-colors">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    
                    <button type="button" 
                        class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded transition-colors" 
                        onclick="confirmDelete('<?= $event['event_id'] ?>', '<?= htmlspecialchars(addslashes($event['event_name'])) ?>')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal de confirmación para eliminación -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-4 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Confirmar Eliminación</h3>
        </div>
        <div class="p-4">
            <p id="deleteMessage" class="mb-2">¿Está seguro de que desea eliminar este evento?</p>
            <p class="text-red-600 text-sm">Esta acción no se puede deshacer y eliminará todos los datos asociados al evento.</p>
        </div>
        <div class="p-4 border-t bg-gray-50 flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-700" onclick="closeDeleteModal()">
                Cancelar
            </button>
            <form id="deleteForm" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
        
        <!-- Paginación -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="pagination-container">
                <?= paginationLinks($pagination, BASE_URL . '/events/list?page=') ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>


<script>
function confirmDelete(eventId, eventName) {
    document.getElementById('deleteMessage').textContent = `¿Está seguro de que desea eliminar el evento "${eventName}"?`;
    document.getElementById('deleteForm').action = '<?= BASE_URL ?>/events/delete/' + eventId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Cerrar modal si se hace clic fuera del contenido
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>


<?php include(VIEW_DIR . '/shared/footer.php'); ?>
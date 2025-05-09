<?php
// DEBUG: Escribir en log
$logFile = __DIR__ . '/../../logs/2025-04-29.log';
$logMsg = date('Y-m-d H:i:s') . " [categories.php] ";
if (isset($eventModel)) {
    $logMsg .= "eventModel: " . print_r($eventModel, true) . "\n";
} else {
    $logMsg .= "eventModel NO está definido\n";
}
if (isset($categoriesWithSubcategories)) {
    $logMsg .= "categoriesWithSubcategories: " . print_r($categoriesWithSubcategories, true) . "\n";
} else {
    $logMsg .= "categoriesWithSubcategories NO está definido\n";
}
if (isset($csrfToken)) {
    $logMsg .= "csrfToken: $csrfToken\n";
} else {
    $logMsg .= "csrfToken NO está definido\n";
}
file_put_contents($logFile, $logMsg, FILE_APPEND);
?>

<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Gestión de Categorías del Evento</h1>
        <?php if (isset($eventModel) && $eventModel): ?>
            <a href="<?= BASE_URL ?>/events/view/<?= $eventModel->getId() ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al evento</a>
        <?php endif; ?>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="mb-6">
        <?php if (isset($eventModel) && $eventModel): ?>
        <form action="<?= BASE_URL ?>/events/addEventCategory/<?= $eventModel->getId() ?>" method="POST" class="flex gap-2 items-end">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nueva Categoría</label>
                <input type="text" name="name" class="form-control" required placeholder="Nombre de la categoría">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Agregar</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($categoriesWithSubcategories as $cat): ?>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <div class="category-name-container">
                    <h2 class="font-semibold text-blue-700 flex items-center gap-2 category-name-text" id="cat-name-<?= $cat['category']['event_category_id'] ?>">
                        <i class="fas fa-folder"></i> <?= htmlspecialchars($cat['category']['name']) ?>
                    </h2>
                </div>
                <div class="flex gap-2">
                    <?php if (isset($eventModel) && $eventModel): ?>
                    <button type="button" class="btn btn-xs btn-warning edit-category-btn-modal" data-cat-id="<?= $cat['category']['event_category_id'] ?>" data-cat-name="<?= htmlspecialchars($cat['category']['name']) ?>"><i class="fas fa-edit"></i></button>
                    <form action="<?= BASE_URL ?>/events/deleteEventCategory/<?= $cat['category']['event_category_id'] ?>" method="POST" onsubmit="return confirm('¿Eliminar esta categoría y todas sus subcategorías?');">
                        <input type="hidden" name="event_id" value="<?= $eventModel->getId() ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <ul class="list-disc pl-6 mb-2">
                <?php foreach ($cat['subcategories'] as $sub): ?>
                <li class="flex items-center justify-between">
                    <span class="subcategory-name-container">
                        <span class="subcategory-name-text" id="subcat-name-<?= $sub['event_subcategory_id'] ?>"><?= htmlspecialchars($sub['name']) ?></span>
                    </span>
                    <span class="flex gap-1">
                        <?php if (isset($eventModel) && $eventModel): ?>
                        <button type="button" class="btn btn-xs btn-warning edit-subcategory-btn-modal" data-subcat-id="<?= $sub['event_subcategory_id'] ?>" data-subcat-name="<?= htmlspecialchars($sub['name']) ?>"><i class="fas fa-edit"></i></button>
                        <form action="<?= BASE_URL ?>/events/deleteEventSubcategory/<?= $sub['event_subcategory_id'] ?>/<?= $eventModel->getId() ?>" method="POST" onsubmit="return confirm('¿Eliminar esta subcategoría?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (isset($eventModel) && $eventModel): ?>
            <form action="<?= BASE_URL ?>/events/addEventSubcategory/<?= $eventModel->getId() ?>/<?= $cat['category']['event_category_id'] ?>" method="POST" class="flex gap-2 mt-2">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="text" name="subcategory_name" class="form-control" required placeholder="Nueva subcategoría">
                <button type="submit" class="btn btn-xs btn-primary"><i class="fas fa-plus"></i></button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div id="editNameModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96">
        <h2 class="text-lg font-bold mb-4">Editar Nombre</h2>
        <form id="editNameForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="mb-4">
                <label for="editNameInput" class="block text-sm font-medium text-gray-700">Nuevo Nombre</label>
                <input type="text" id="editNameInput" name="name" class="form-control" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelEditName" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal edición nombre categoría/subcategoría
    const editNameModal = document.getElementById('editNameModal');
    const editNameInput = document.getElementById('editNameInput');
    const editNameForm = document.getElementById('editNameForm');
    let currentEditType = null;
    let currentEditId = null;
    let currentEventId = "<?= isset($eventModel) ? $eventModel->getId() : '' ?>";

    // Abrir modal para categoría
    document.querySelectorAll('.edit-category-btn-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            currentEditType = 'category';
            currentEditId = this.getAttribute('data-cat-id');
            editNameInput.value = this.getAttribute('data-cat-name');
            editNameInput.name = 'category_name'; // Cambia el name para categoría
            editNameForm.action = `<?= BASE_URL ?>/events/editEventCategory/${currentEditId}/${currentEventId}`;
            editNameModal.classList.remove('hidden');
        });
    });
    // Abrir modal para subcategoría
    document.querySelectorAll('.edit-subcategory-btn-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            currentEditType = 'subcategory';
            currentEditId = this.getAttribute('data-subcat-id');
            editNameInput.value = this.getAttribute('data-subcat-name');
            editNameInput.name = 'subcategory_name'; // Cambia el name para subcategoría
            editNameForm.action = `<?= BASE_URL ?>/events/editEventSubcategory/${currentEditId}/${currentEventId}`;
            editNameModal.classList.remove('hidden');
        });
    });
    // Cancelar modal
    document.getElementById('cancelEditName').addEventListener('click', function() {
        editNameModal.classList.add('hidden');
        editNameInput.value = '';
        editNameForm.action = '';
    });
    // Cerrar modal al hacer click fuera del contenido
    editNameModal.addEventListener('click', function(e) {
        if (e.target === editNameModal) {
            editNameModal.classList.add('hidden');
            editNameInput.value = '';
            editNameForm.action = '';
        }
    });
});
</script>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

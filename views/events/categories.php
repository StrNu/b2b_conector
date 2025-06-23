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
                    <form action="<?= BASE_URL ?>/events/deleteEventCategory/<?= $eventModel->getId() ?>/<?= $cat['category']['event_category_id'] ?>" method="POST" onsubmit="return confirm('¿Eliminar esta categoría y todas sus subcategorías?');">
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
                        <form action="<?= BASE_URL ?>/events/deleteEventSubcategory/<?= $eventModel->getId() ?>/<?= $sub['event_subcategory_id'] ?>" method="POST" onsubmit="return confirm('¿Eliminar esta subcategoría?');">
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
                <input type="text" name="name" class="form-control" required placeholder="Nueva subcategoría">
                <button type="submit" class="btn btn-xs btn-primary"><i class="fas fa-plus"></i></button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
<?php if (isset($eventModel) && $eventModel): ?>
window.eventModelId = "<?= $eventModel->getId() ?>";
<?php endif; ?>
</script>
<!-- El modal de edición de nombre ha sido movido a shared/modals.php para uso global. -->
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

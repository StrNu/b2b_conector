<?php
// Asegurarnos de que la variable $eventId está definida
$eventId = isset($eventModel) ? $eventModel->getId() : (isset($eventId) ? $eventId : 0);
?>
<!-- Modal para importar categorías -->
<div id="importCategoriesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Importar Categorías y Subcategorías</h3>
            <span class="modal-close" onclick="closeImportModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p class="mb-4">Sube un archivo CSV con las categorías y subcategorías para este evento.</p>
            
            <div class="mb-4">
                <h4 class="section-subtitle">Formato del CSV:</h4>
                <div class="code-example">
                    <p>Tipo,Nombre,Descripción</p>
                    <p>Categoría,Electrónica,Productos electrónicos</p>
                    <p>Subcategoría,Smartphones,Teléfonos móviles</p>
                </div>
            </div>
            
            <form action="<?= BASE_URL ?>/categories/import/<?= $eventId ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="form-group">
                    <label for="csv_file">Archivo CSV</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="form-control">
                    <small class="form-text text-muted">Solo archivos CSV (.csv)</small>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
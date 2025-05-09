<!-- Modal para importar categorías -->
<div id="importCategoriesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">Importar Categorías</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeImportCategoriesModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4">
            <form action="<?= BASE_URL ?>/categories/import/<?= $eventModel->getId() ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="event_id" value="<?= $eventModel->getId() ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category_file">
                        Archivo de categorías (CSV, Excel)
                    </label>
                    <input type="file" name="csv_file" id="csv_file" 
       class="block w-full text-sm text-gray-500
              file:mr-4 file:py-2 file:px-4
              file:rounded-md file:border-0
              file:text-sm file:font-semibold
              file:bg-blue-50 file:text-blue-700
              hover:file:bg-blue-100"
       accept=".csv, .xls, .xlsx" required>
                    <p class="text-xs text-gray-500 mt-1">Formatos permitidos: CSV, Excel</p>
                </div>
                
                <div class="p-4 border-t bg-gray-50 flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-700" onclick="closeImportCategoriesModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
                        Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
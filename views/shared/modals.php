<!-- Modal de confirmación para eliminación (global) -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">Confirmar Eliminación</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-action="close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4">
            <p id="deleteMessage" class="mb-2">¿Está seguro de que desea eliminar esta entidad?</p>
            <p class="text-red-600 text-sm">Esta acción no se puede deshacer y eliminará todos los datos asociados.</p>
        </div>
        <div class="p-4 border-t bg-gray-50 flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-700" data-action="close">
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
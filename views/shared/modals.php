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

<!-- Modal de edición de nombre (global) -->
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

<!-- Modal edición match potencial (matches) -->
<div id="potentialMatchModal" class="modal hidden fixed inset-0 z-[9999] bg-black bg-opacity-40 flex items-center justify-center" style="z-index:9999; display: none;">
  <div class="modal-content bg-white rounded-lg shadow-lg p-6" style="min-width:340px;max-width:700px;">
    <div class="modal-header flex justify-between items-center">
      <span class="font-bold text-lg">Editar match potencial</span>
      <button type="button" class="close-modal-btn text-gray-400 hover:text-gray-700 text-xl font-bold" aria-label="Cerrar">&times;</button>
    </div>
    <form id="potentialMatchForm" autocomplete="off">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Columna izquierda: Comprador (solo lectura) -->
        <div>
          <div class="mb-2 font-semibold text-blue-700">Comprador</div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Company</label>
            <div id="pm-buyer-company" class="pm-readonly"></div>
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Requirements</label>
            <div id="pm-buyer-requirements" class="pm-readonly"></div>
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
            <div id="pm-buyer-description" class="pm-readonly"></div>
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Keywords</label>
            <div id="pm-buyer-keywords" class="pm-readonly"></div>
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Attendance Days</label>
            <div id="pm-buyer-attendance" class="pm-readonly"></div>
          </div>
        </div>
        <!-- Columna derecha: Proveedor (editable) -->
        <div>
          <div class="mb-2 font-semibold text-green-700">Proveedor</div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Company</label>
            <div id="pm-supplier-company" class="pm-readonly"></div>
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Supplier Offers</label>
            <div id="pm-supplier-offers" class="pm-readonly"></div>
          </div>
          <div class="mb-3">
            <label for="pm-supplier-description" class="block text-xs font-semibold text-gray-700 mb-1">Description</label>
            <textarea id="pm-supplier-description" name="supplier_description" class="pm-editable" rows="2" required></textarea>
          </div>
          <div class="mb-3">
            <label for="pm-supplier-keywords" class="block text-xs font-semibold text-gray-700 mb-1">Keywords</label>
            <input type="text" id="pm-supplier-keywords" name="supplier_keywords" class="pm-editable" placeholder="Palabras clave, separadas por coma">
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Attendance Days</label>
            <div id="pm-supplier-attendance-list"></div>
            <button type="button" id="add-supplier-attendance-date" class="mt-2 px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Agregar fecha</button>
          </div>
        </div>
      </div>
      <div class="modal-footer flex justify-end gap-2 mt-6">
        <button type="button" class="btn btn-secondary close-modal-btn">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal para optimizar empresa -->
<div id="optimizeCompanyModal" class="modal hidden fixed inset-0 z-[9999] bg-black bg-opacity-40 flex items-center justify-center" style="z-index:9999; display: none;">
    <div class="modal-content bg-white rounded-lg shadow-lg p-6" style="min-width:400px;max-width:800px;">
        <div class="modal-header flex justify-between items-center mb-4">
            <h3 class="modal-title text-lg font-semibold">
                <i class="fas fa-rocket text-blue-600"></i>
                Optimizar Empresa
            </h3>
            <button type="button" class="close-modal-btn text-gray-400 hover:text-gray-700 text-xl font-bold" aria-label="Cerrar modal">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="optimizeCompanyForm">
                <input type="hidden" id="optimize-company-id" name="company_id">
                <input type="hidden" id="optimize-event-id" name="event_id">
                <input type="hidden" id="optimize-company-role" name="company_role">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                
                <!-- Información de la empresa -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-building"></i>
                        <span id="optimize-company-name">Empresa</span>
                    </h4>
                    <p class="text-sm text-blue-700">
                        Tipo: <span id="optimize-company-type" class="font-medium"></span>
                    </p>
                </div>
                
                <!-- Campo Description -->
                <div class="mb-4">
                    <label for="optimize-description" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left text-gray-500"></i>
                        Descripción de la empresa
                    </label>
                    <textarea 
                        id="optimize-description" 
                        name="description" 
                        rows="4" 
                        class="form-control w-full border border-gray-300 rounded-md px-3 py-2" 
                        placeholder="Describe los servicios y capacidades de la empresa..."
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1">Mejora la descripción para aumentar las posibilidades de match</p>
                </div>
                
                <!-- Campo Keywords -->
                <div class="mb-6">
                    <label for="optimize-keywords" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tags text-gray-500"></i>
                        Palabras clave
                    </label>
                    <input 
                        type="text" 
                        id="optimize-keywords" 
                        name="keywords" 
                        class="form-control w-full border border-gray-300 rounded-md px-3 py-2" 
                        placeholder="Ej: manufactura, soldadura, CNC, maquinado..."
                    >
                    <p class="text-xs text-gray-500 mt-1">Separa las palabras clave con comas. Usa términos que otros puedan buscar.</p>
                </div>
                
                <!-- Sugerencias de optimización -->
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h5 class="font-medium text-green-800 mb-3">
                        <i class="fas fa-lightbulb text-green-600"></i>
                        Sugerencias basadas en el evento
                    </h5>
                    <div id="optimization-suggestions-for-company" class="grid grid-cols-2 gap-4">
                        <div>
                            <h6 class="text-sm font-medium text-green-700 mb-2">Keywords populares:</h6>
                            <div id="suggested-keywords" class="flex flex-wrap gap-1"></div>
                        </div>
                        <div>
                            <h6 class="text-sm font-medium text-green-700 mb-2">Palabras en descripciones:</h6>
                            <div id="suggested-words" class="flex flex-wrap gap-1"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="modal-footer flex items-center justify-between w-full">
            <!-- Botón de editar requerimientos/ofertas (lado izquierdo) -->
            <button 
                type="button" 
                id="edit-requirements-offers-btn" 
                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2"
            >
                <i class="fas fa-edit"></i>
                <span id="edit-btn-text">Editar requerimientos</span>
            </button>
            
            <!-- Botones de acción (lado derecho) -->
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-secondary close-modal-btn">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="submit" form="optimizeCompanyForm" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar optimización
                </button>
            </div>
        </div>
    </div>
</div>
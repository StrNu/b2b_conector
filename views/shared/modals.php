<!-- Modal de confirmación para eliminación (global) -->
<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirmar Eliminación</h3>
            <button type="button" class="modal-close" data-action="close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="deleteMessage" class="mb-2">¿Está seguro de que desea eliminar esta entidad?</p>
            <p class="text-danger text-sm">Esta acción no se puede deshacer y eliminará todos los datos asociados.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-material btn-material--outlined" data-action="close">
                Cancelar
            </button>
            <form id="deleteForm" action="" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                <button type="submit" class="btn-material btn-material--filled btn-danger">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal de edición de nombre (global) -->
<div id="editNameModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Nombre</h3>
            <button type="button" class="modal-close" data-action="close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="editNameForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                <div class="textfield-material">
                    <input type="text" id="editNameInput" name="name" class="textfield-material__input" required placeholder=" ">
                    <label for="editNameInput" class="textfield-material__label">Nuevo Nombre</label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-material btn-material--outlined modal-close">Cancelar</button>
            <button type="submit" form="editNameForm" class="btn-material btn-material--filled">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal edición match potencial (matches) -->
<div id="potentialMatchModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999999999;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Editar Match Potencial</h3>
      <button type="button" class="close-modal-btn" aria-label="Cerrar">&times;</button>
    </div>
    
    <form id="potentialMatchForm" autocomplete="off">
      <div class="modal-body">
        <!-- Columna izquierda: Comprador (solo lectura) -->
        <div class="buyer-column">
          <div class="column-header">
            <i class="fas fa-shopping-cart"></i>
            <span>Empresa Compradora</span>
          </div>
          
          <div class="field-group">
            <label class="field-label">Empresa</label>
            <div id="pm-buyer-company" class="field-value readonly"></div>
          </div>
          
          <div class="field-group">
            <label class="field-label">Requerimientos</label>
            <div id="pm-buyer-requirements" class="field-value readonly"></div>
          </div>
          
          <div class="field-group">
            <label class="field-label">Descripción</label>
            <div id="pm-buyer-description" class="field-value readonly"></div>
          </div>
          
          <div class="field-group">
            <label class="field-label">Palabras Clave</label>
            <div id="pm-buyer-keywords" class="field-value readonly"></div>
          </div>
          
          <div class="field-group">
            <label class="field-label">Días de Asistencia</label>
            <div id="pm-buyer-attendance" class="field-value readonly"></div>
          </div>
        </div>
        
        <!-- Columna derecha: Proveedor (editable) -->
        <div class="supplier-column">
          <div class="column-header">
            <i class="fas fa-industry"></i>
            <span>Empresa Proveedora</span>
          </div>
          
          <div class="field-group">
            <label class="field-label">Empresa</label>
            <div id="pm-supplier-company" class="field-value readonly"></div>
          </div>
          
          <div class="field-group">
            <label class="field-label">Ofertas</label>
            <div id="pm-supplier-offers" class="field-value readonly"></div>
          </div>
          
          <div class="field-group">
            <label class="field-label">Fechas Coincidentes</label>
            <div id="pm-coincident-dates" class="field-value readonly" style="color: #059669; font-weight: 500;"></div>
          </div>
          
          <div class="field-group">
            <label for="pm-supplier-description" class="field-label">Descripción</label>
            <textarea id="pm-supplier-description" name="supplier_description" class="field-value editable" rows="3" required></textarea>
          </div>
          
          <div class="field-group">
            <label for="pm-supplier-keywords" class="field-label">Palabras Clave</label>
            <input type="text" id="pm-supplier-keywords" name="supplier_keywords" class="field-value editable" placeholder="Separadas por coma">
          </div>
          
          <div class="field-group">
            <label class="field-label">Días de Asistencia</label>
            <div id="pm-supplier-attendance-list" style="margin-bottom: 8px;"></div>
            <button type="button" id="add-supplier-attendance-date" class="btn-modal btn-cancel" style="font-size: 12px; padding: 6px 12px;">
              <i class="fas fa-plus"></i> Agregar fecha
            </button>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-cancel close-modal-btn">Cancelar</button>
        <button type="submit" class="btn-modal btn-save">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal para optimizar empresa -->
<div id="optimizeCompanyModal" class="modal hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center" style="z-index:9999; display: none;">
    <div class="modal-content bg-white rounded-lg shadow-xl p-6" style="min-width:400px;max-width:800px; max-height:90vh; overflow-y:auto;">
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
                        class="textfield-material__input form-control w-full border border-gray-300 rounded-md px-3 py-2" 
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
                        class="textfield-material__input form-control w-full border border-gray-300 rounded-md px-3 py-2" 
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
                class="btn-material btn-material--filled bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2"
            >
                <i class="fas fa-edit"></i>
                <span id="edit-btn-text">Editar requerimientos</span>
            </button>
            
            <!-- Botones de acción (lado derecho) -->
            <div class="flex items-center gap-3">
                <button type="button" class="btn-material btn-material--outlined close-modal-btn">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="submit" form="optimizeCompanyForm" class="btn-material btn-material--filled">
                    <i class="fas fa-save"></i>
                    Guardar optimización
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal styles for Material Design 3 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
}

.modal-overlay.hidden {
    display: none;
}

.modal-content {
    background: var(--md-surface-container-high, #ffffff);
    border-radius: var(--md-shape-corner-large, 16px);
    box-shadow: var(--md-elevation-3, 0 4px 8px rgba(0,0,0,0.12));
    max-width: 400px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--md-outline-variant, #e0e0e0);
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0;
    font-family: 'Montserrat', sans-serif;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: var(--md-on-surface-variant);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: var(--md-shape-corner-full);
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: var(--md-surface-container-highest);
    color: var(--md-on-surface);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1rem 1.5rem 1.5rem;
    border-top: 1px solid var(--md-outline-variant, #e0e0e0);
}

.text-danger {
    color: var(--md-error-40, #dc3545);
}

.btn-danger {
    background: var(--md-error-40, #dc3545) !important;
    color: var(--md-on-error, white) !important;
}

.btn-danger:hover {
    background: var(--md-error-30, #c82333) !important;
}
</style>

<script>
// Global modal functions
window.openEditModal = function(type, id, name) {
    const modal = document.getElementById('editNameModal');
    const form = document.getElementById('editNameForm');
    const input = document.getElementById('editNameInput');
    const title = modal.querySelector('.modal-title');
    
    if (!modal || !form || !input || !title) {
        console.error('Modal elements not found');
        return;
    }
    
    // Set modal title based on type
    if (type === 'category') {
        title.textContent = 'Editar Categoría';
        form.action = `${window.BASE_URL || ''}/events/updateEventCategory/${window.eventModelId || ''}/${id}`;
    } else if (type === 'subcategory') {
        title.textContent = 'Editar Subcategoría';
        form.action = `${window.BASE_URL || ''}/events/updateEventSubcategory/${window.eventModelId || ''}/${id}`;
    }
    
    // Set current name
    input.value = name;
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Focus on input
    setTimeout(() => {
        input.focus();
        input.select();
    }, 100);
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
};

// Initialize modal event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Close modal handlers
    document.querySelectorAll('.modal-close, [data-action="close"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
    
    // Escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });
    
    // Form validation for edit name modal
    const editNameForm = document.getElementById('editNameForm');
    if (editNameForm) {
        editNameForm.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('editNameInput');
            if (nameInput) {
                const value = nameInput.value.trim();
                if (value.length < 2) {
                    e.preventDefault();
                    alert('El nombre debe tener al menos 2 caracteres.');
                    nameInput.focus();
                    return false;
                }
                if (value.length > 100) {
                    e.preventDefault();
                    alert('El nombre no puede exceder 100 caracteres.');
                    nameInput.focus();
                    return false;
                }
            }
        });
    }
});
</script>
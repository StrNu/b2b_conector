/**
 * Funciones para manejar modales en la aplicación
 */

// Modal de eliminación
const deleteModal = {
    // Elementos del DOM
    modal: null,
    messageElement: null,
    formElement: null,
    
    // Inicializar el modal
    init: function() {
        this.modal = document.getElementById('deleteModal');
        this.messageElement = document.getElementById('deleteMessage');
        this.formElement = document.getElementById('deleteForm');
        
        // Solo continuar si encontramos el modal
        if (!this.modal) return;
        
        // Añadir evento para cerrar al hacer clic fuera
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
        
        // Buscar botón de cerrar y añadir evento
        const closeButton = this.modal.querySelector('[data-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', () => this.close());
        }
    },
    
    // Abrir el modal de eliminación
    confirm: function(id, name, baseUrl, entityType = 'events') {
        if (!this.modal || !this.messageElement || !this.formElement) {
            console.error('Modal elements not initialized');
            return;
        }
        
        // Actualizar el mensaje
        this.messageElement.textContent = `¿Está seguro de que desea eliminar ${entityType === 'events' ? 'el evento' : 'la entidad'} "${name}"?`;
        
        // Actualizar la acción del formulario
        this.formElement.action = `${baseUrl}/${entityType}/delete/${id}`;
        
        // Mostrar el modal
        this.modal.classList.remove('hidden');
    },
    
    // Cerrar el modal
    close: function() {
        if (this.modal) {
            this.modal.classList.add('hidden');
        }
    }
};

// Inicializar modales cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    deleteModal.init();
});

// Exponer funciones globalmente
window.confirmDelete = function(id, name, baseUrl = window.BASE_URL, entityType = 'events') {
    deleteModal.confirm(id, name, baseUrl, entityType);
};

window.closeDeleteModal = function() {
    deleteModal.close();
};
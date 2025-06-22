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

// Modal genérico reutilizable
const genericModal = {
    modal: null,
    titleElement: null,
    bodyElement: null,
    footerElement: null,
    onClose: null,

    init: function(modalId) {
        this.modal = document.getElementById(modalId);
        if (!this.modal) return;
        this.titleElement = this.modal.querySelector('.modal-title');
        this.bodyElement = this.modal.querySelector('.modal-body');
        this.footerElement = this.modal.querySelector('.modal-footer');
        // Cerrar al hacer click fuera del contenido
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });
        // Botón de cerrar
        const closeButton = this.modal.querySelector('[data-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', () => this.close());
        }
    },
    open: function({title = '', body = '', footer = '', onClose = null} = {}) {
        if (!this.modal) return;
        if (this.titleElement) this.titleElement.innerHTML = title;
        if (this.bodyElement) this.bodyElement.innerHTML = body;
        if (this.footerElement) this.footerElement.innerHTML = footer;
        this.onClose = onClose;
        this.modal.classList.remove('hidden');
    },
    close: function() {
        if (this.modal) {
            this.modal.classList.add('hidden');
            if (typeof this.onClose === 'function') this.onClose();
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

// Función global para abrir el modal de edición de match potencial
window.openEditPotentialMatchModal = function({buyerId, supplierId, eventId}) {
    console.log('openEditPotentialMatchModal llamada', {buyerId, supplierId, eventId});
    console.trace();
    // Inicializar el modal si no está
    if (!genericModal.modal) genericModal.init('potentialMatchModalId');
    // Mostrar loader mientras se cargan los datos
    genericModal.open({
        title: 'Editar match potencial',
        body: '<div class="text-center py-6 text-gray-400">Cargando datos de las empresas...</div>',
        footer: '',
    });
    // Llamar al endpoint AJAX para obtener los datos de ambas empresas
    fetch(`${window.BASE_URL || ''}/matches?action=getCompanyFullDetailsAjax`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `buyer_id=${encodeURIComponent(buyerId)}&supplier_id=${encodeURIComponent(supplierId)}&event_id=${encodeURIComponent(eventId)}&csrf_token=${encodeURIComponent(window.csrfToken || '')}`
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            genericModal.open({
                title: 'Error',
                body: `<div class='text-red-500 text-center py-4'>${data.message || 'No se pudieron cargar los datos.'}</div>`,
                footer: '<button class="btn btn-secondary" data-action="close">Cerrar</button>'
            });
            return;
        }
        // Renderizar los datos de ambas empresas
        const buyer = data.buyer || {};
        const supplier = data.supplier || {};
        const bodyHtml = `
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <h3 class="font-bold mb-1">Comprador</h3>
                    <div><b>Nombre:</b> ${buyer.name || '-'}</div>
                    <div><b>Keywords:</b> ${buyer.keywords || '-'}</div>
                    <div><b>Descripción:</b> ${buyer.description || '-'}</div>
                </div>
                <div>
                    <h3 class="font-bold mb-1">Proveedor</h3>
                    <div><b>Nombre:</b> ${supplier.name || '-'}</div>
                    <div><b>Keywords:</b> ${supplier.keywords || '-'}</div>
                    <div><b>Descripción:</b> ${supplier.description || '-'}</div>
                </div>
            </div>
            <div class="mb-2 text-sm text-gray-500">Puedes editar los datos antes de guardar el match real.</div>
        `;
        const footerHtml = `
            <button class="btn btn-primary" id="btn-save-potential-match">Guardar match</button>
            <button class="btn btn-secondary" data-action="close">Cancelar</button>
        `;
        genericModal.open({
            title: 'Editar match potencial',
            body: bodyHtml,
            footer: footerHtml
        });
        // Evento para guardar el match
        document.getElementById('btn-save-potential-match').onclick = function() {
            // Aquí puedes implementar la lógica para guardar el match real (AJAX a savePotentialMatchAjax)
            // ...
        };
    })
    .catch(() => {
        genericModal.open({
            title: 'Error',
            body: '<div class="text-red-500 text-center py-4">Error de red al cargar los datos.</div>',
            footer: '<button class="btn btn-secondary" data-action="close">Cerrar</button>'
        });
    });
};

// Eliminar cualquier ejemplo de apertura automática del modal genérico
// (No debe haber genericModal.open(...) fuera de openEditPotentialMatchModal)
// Si necesitas abrir el modal, hazlo solo desde openEditPotentialMatchModal o desde un evento de usuario.
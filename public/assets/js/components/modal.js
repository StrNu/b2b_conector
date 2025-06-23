// public/assets/js/components/modal.js
// Componente para modal de edición de nombre (categoría/subcategoría)

function initEditNameModal({
    modalSelector = '#editNameModal',
    inputSelector = '#editNameInput',
    formSelector = '#editNameForm',
    editCategoryBtnSelector = '.edit-category-btn-modal',
    editSubcategoryBtnSelector = '.edit-subcategory-btn-modal',
    cancelBtnSelector = '#cancelEditName',
    eventId = ''
} = {}) {
    const editNameModal = document.querySelector(modalSelector);
    const editNameInput = document.querySelector(inputSelector);
    const editNameForm = document.querySelector(formSelector);
    const cancelBtn = document.querySelector(cancelBtnSelector);

    // Validación de existencia de elementos
    if (!editNameModal || !editNameInput || !editNameForm || !cancelBtn) {
        console.warn('Modal de edición: Elementos no encontrados en el DOM');
        return;
    }

    // Abrir modal para categoría
    document.querySelectorAll(editCategoryBtnSelector).forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Click en editar categoría', this);
            editNameInput.value = this.getAttribute('data-cat-name');
            editNameInput.name = 'name';
            const catId = this.getAttribute('data-cat-id');
            editNameForm.action = `/b2b_conector/events/editEventCategory/${eventId}/${catId}`;
            editNameModal.classList.remove('hidden');
        });
    });
    // Abrir modal para subcategoría
    document.querySelectorAll(editSubcategoryBtnSelector).forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Click en editar subcategoría', this);
            editNameInput.value = this.getAttribute('data-subcat-name');
            editNameInput.name = 'name'; // <-- Corregido: debe ser 'name' para que el backend lo reciba
            const subcatId = this.getAttribute('data-subcat-id');
            editNameForm.action = `/b2b_conector/events/editEventSubcategory/${eventId}/${subcatId}`;
            editNameModal.classList.remove('hidden');
        });
    });
    // Cancelar modal
    cancelBtn.addEventListener('click', function() {
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
}

// Inicialización automática al cargar el script
document.addEventListener('DOMContentLoaded', function() {
    if (window.eventModelId !== undefined) {
        initEditNameModal({ eventId: window.eventModelId });
    } else {
        // fallback: buscar el id en el DOM si es necesario
        initEditNameModal();
    }
});

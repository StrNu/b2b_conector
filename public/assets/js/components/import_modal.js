/**
 * Funcionalidad para el modal de importación de categorías
 */

// Función para abrir el modal de importación
function openImportModal() {
    const modal = document.getElementById('importCategoriesModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// Función para cerrar el modal de importación
function closeImportModal() {
    const modal = document.getElementById('importCategoriesModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Inicializar eventos cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Configurar cierre de modal al hacer clic fuera del contenido
    const modal = document.getElementById('importCategoriesModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeImportModal();
            }
        });
    }
    
    // Configurar botón de apertura si existe
    const importButton = document.getElementById('importCategoriesButton');
    if (importButton) {
        importButton.addEventListener('click', function(e) {
            e.preventDefault();
            openImportModal();
        });
    }
});
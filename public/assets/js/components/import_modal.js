function openImportCategoriesModal() {
    // Asumiendo que el modal tiene un ID "importCategoriesModal"
    const modal = document.getElementById('importCategoriesModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// También puedes agregar una función para cerrar el modal
function closeImportCategoriesModal() {
    const modal = document.getElementById('importCategoriesModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Escuchar clics fuera del contenido del modal para cerrarlo
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('importCategoriesModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeImportCategoriesModal();
            }
        });
    }
});
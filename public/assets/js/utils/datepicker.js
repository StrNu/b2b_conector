// Simple datepicker UX para inputs tipo text
// Uso: addDatePicker(inputElement)
function addDatePicker(input) {
    input.setAttribute('type', 'date');
    input.classList.add('datepicker-input');
}
// Inicialización automática para inputs con clase .datepicker
window.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input.datepicker').forEach(addDatePicker);
});

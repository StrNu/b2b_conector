document.addEventListener('DOMContentLoaded', function() {
    // Mostrar u ocultar la sección de breaks según el checkbox
    const hasBreakCheckbox = document.getElementById('has_break');
    const breaksContainer = document.getElementById('breaks-container');
    if (hasBreakCheckbox && breaksContainer) {
        hasBreakCheckbox.addEventListener('change', function() {
            if (this.checked) {
                breaksContainer.classList.remove('d-none');
            } else {
                breaksContainer.classList.add('d-none');
            }
        });
    }
    // Agregar nuevo break
    const addBreakBtn = document.getElementById('add-break');
    const breaksList = document.querySelector('.breaks-list');
    if (addBreakBtn && breaksList) {
        addBreakBtn.addEventListener('click', function() {
            const breakItem = document.querySelector('.break-item').cloneNode(true);
            const inputs = breakItem.querySelectorAll('input');
            inputs.forEach(input => input.value = '');
            // Configurar el botón de eliminar
            const removeBtn = breakItem.querySelector('.remove-break');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    if (document.querySelectorAll('.break-item').length > 1) {
                        breakItem.remove();
                    }
                });
            }
            breaksList.appendChild(breakItem);
        });
    }
    // Configurar el botón de eliminar para los breaks existentes
    document.querySelectorAll('.remove-break').forEach(button => {
        button.addEventListener('click', function() {
            if (document.querySelectorAll('.break-item').length > 1) {
                this.closest('.break-item').remove();
            }
        });
    });
    // Calcular capacidad estimada
    function calculateCapacity() {
        const meetingDuration = parseInt(document.getElementById('meeting_duration')?.value) || 30;
        const availableTables = parseInt(document.getElementById('available_tables')?.value) || 10;
        const startTime = document.getElementById('start_time')?.value || '09:00';
        const endTime = document.getElementById('end_time')?.value || '18:00';
        // Calcular horas disponibles
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        let availableMinutes = (end - start) / 60000; // diferencia en minutos
        // Restar tiempo de breaks si están activados
        if (hasBreakCheckbox && hasBreakCheckbox.checked) {
            const breakItems = document.querySelectorAll('.break-item');
            breakItems.forEach(item => {
                const breakStart = item.querySelector('[name="break_start_time[]"]')?.value;
                const breakEnd = item.querySelector('[name="break_end_time[]"]')?.value;
                if (breakStart && breakEnd) {
                    const bStart = new Date(`2000-01-01T${breakStart}`);
                    const bEnd = new Date(`2000-01-01T${breakEnd}`);
                    const breakDuration = (bEnd - bStart) / 60000; // diferencia en minutos
                    availableMinutes -= breakDuration;
                }
            });
        }
        // Calcular número de slots por mesa
        const slotsPerTable = Math.floor(availableMinutes / meetingDuration);
        // Calcular capacidad total
        const capacity = slotsPerTable * availableTables;
        // Actualizar el campo
        const estimatedCapacity = document.getElementById('estimated_capacity');
        if (estimatedCapacity) {
            estimatedCapacity.value = capacity > 0 ? capacity : 0;
        }
    }
    // Eventos para recalcular la capacidad
    if (document.getElementById('meeting_duration'))
        document.getElementById('meeting_duration').addEventListener('input', calculateCapacity);
    if (document.getElementById('available_tables'))
        document.getElementById('available_tables').addEventListener('input', calculateCapacity);
    if (document.getElementById('start_time'))
        document.getElementById('start_time').addEventListener('input', calculateCapacity);
    if (document.getElementById('end_time'))
        document.getElementById('end_time').addEventListener('input', calculateCapacity);
    if (hasBreakCheckbox)
        hasBreakCheckbox.addEventListener('change', calculateCapacity);
    // Calcular capacidad inicial
    calculateCapacity();
    // Actualizar capacidad cuando se agregan o eliminan breaks
    if (addBreakBtn)
        addBreakBtn.addEventListener('click', function() {
            setTimeout(calculateCapacity, 100);
        });
    document.querySelectorAll('.remove-break').forEach(button => {
        button.addEventListener('click', function() {
            setTimeout(calculateCapacity, 100);
        });
    });
    // Inicializar datepicker para las fechas
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            minDate: 'today'
        });
    }
    // Mostrar nombre de archivo en input file
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Seleccionar archivo';
            this.nextElementSibling.textContent = fileName;
        });
    });
});
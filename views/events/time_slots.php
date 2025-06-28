<?php 
// Vista de time_slots con Material Design 3
if (file_exists(CONFIG_DIR . '/material-config.php')) {
    require_once CONFIG_DIR . '/material-config.php';
}

// Fallback functions if Material Design helpers are not available
if (!function_exists('materialButton')) {
    function materialButton($text, $variant = 'filled', $icon = '', $attributes = '', $size = '') {
        $class = 'btn btn-primary';
        if ($variant === 'outlined') $class = 'btn btn-secondary';
        if ($variant === 'tonal') $class = 'btn btn-info';
        if ($size === 'small') $class .= ' btn-sm';
        return '<button class="' . $class . '" ' . $attributes . '>' . $text . '</button>';
    }
}

if (!function_exists('materialCard')) {
    function materialCard($title, $content, $variant = 'elevated', $actions = '') {
        return '<div class="card">
                    <div class="card-header"><h5>' . $title . '</h5></div>
                    <div class="card-body">' . $content . '</div>
                    ' . ($actions ? '<div class="card-footer">' . $actions . '</div>' : '') . '
                </div>';
    }
}

if (!function_exists('displayFlashMessages')) {
    function displayFlashMessages() {
        include(VIEW_DIR . '/shared/notifications.php');
    }
}

if (!function_exists('isEventUserAuthenticated')) {
    function isEventUserAuthenticated() {
        return false;
    }
}

$pageTitle = 'Horarios y Capacidad del Evento';
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Eventos', 'url' => BASE_URL . '/events'],
    ['title' => 'Time Slots']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Horarios y Capacidad del Evento</h1>
            <p class="page-subtitle">Visualiza la capacidad y distribución de horarios</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-sync-alt"></i> Actualizar Ahora',
                'filled',
                '',
                'id="manual-refresh-btn" onclick="if(window.timeSlotRefresh) window.timeSlotRefresh.refreshScheduleData()"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . (int)$eventModel->getId() . '\'"'
            ) ?>
        </div>
    </div>
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>
    
    <div class="time-slots-container">
        <!-- Event Capacity Card -->
        <div class="capacity-section">
            <?php
            ob_start();
            ?>
                <div class="capacity-stats">
                    <div class="stat-item">
                        <div class="stat-item__icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value"><?= htmlspecialchars($eventDurationDays ?? 0) ?></div>
                            <div class="stat-item__label">Días de evento</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon">
                            <i class="fas fa-table"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value"><?= htmlspecialchars($availableTables ?? 0) ?></div>
                            <div class="stat-item__label">Mesas disponibles</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value"><?= htmlspecialchars($slotsPerDay ?? 0) ?></div>
                            <div class="stat-item__label">Horarios por día</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">
                                <?php $reunionesPorDia = (int)($slotsPerDay ?? 0) * (int)($availableTables ?? 0); echo $reunionesPorDia; ?>
                            </div>
                            <div class="stat-item__label">Reuniones por día</div>
                        </div>
                    </div>
                    
                    <div class="stat-item stat-item--highlight">
                        <div class="stat-item__icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">
                                <?php $capacidadTotal = $reunionesPorDia * (int)($eventDurationDays ?? 0); echo $capacidadTotal; ?>
                            </div>
                            <div class="stat-item__label">Capacidad total</div>
                        </div>
                    </div>
                </div>
            <?php
            $capacityContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-chart-bar"></i> Capacidad del Evento',
                $capacityContent,
                'elevated'
            );
            ?>
        </div>
        
        <!-- Breaks Section -->
        <div class="breaks-section-container">
            <?php
            ob_start();
            ?>
                <h4 class="breaks-title">
                    <i class="fas fa-pause"></i>
                    Descansos Programados
                </h4>
                
                <div class="table-responsive">
                    <table class="table-material table-material--compact">
                        <thead class="table-material__header">
                            <tr>
                                <th class="table-material__cell table-material__cell--header">Hora inicio</th>
                                <th class="table-material__cell table-material__cell--header">Hora fin</th>
                                <th class="table-material__cell table-material__cell--header">Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($breaks)): ?>
                                <?php foreach ($breaks as $break): ?>
                                    <tr class="table-material__row">
                                        <td class="table-material__cell text-center"><?= htmlspecialchars($break['start_time']) ?></td>
                                        <td class="table-material__cell text-center"><?= htmlspecialchars($break['end_time']) ?></td>
                                        <td class="table-material__cell text-center">
                                            <?php
                                            if (is_array($break)) {
                                                $start = isset($break['start_time']) && !empty($break['start_time']) ? strtotime($break['start_time']) : false;
                                                $end = isset($break['end_time']) && !empty($break['end_time']) ? strtotime($break['end_time']) : false;
                                                if ($start !== false && $end !== false && is_int($start) && is_int($end) && $end > $start) {
                                                    $duration = floor(($end - $start) / 60);
                                                    echo $duration . ' min';
                                                } else {
                                                    echo '-';
                                                }
                                            } elseif (is_object($break) && method_exists($break, 'getDuration')) {
                                                echo $break->getDuration() . ' min';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="table-material__cell text-center text-muted">No hay descansos programados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php
            $breaksContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-pause"></i> Descansos Programados',
                $breaksContent,
                'outlined'
            );
            ?>
        </div>
        <!-- Schedule Breakdown Section -->
        <div class="schedule-breakdown-section">
            <?php
            // --- Generar contenido HTML para el desglose de horarios ---
            ob_start();
            $eventDurationDays = $eventDurationDays ?? 0;
            $slotsPerDay = $slotsPerDay ?? 0;
            $availableTables = $availableTables ?? 0;
            $breaks = is_array($breaks) ? $breaks : [];
            $slotsByDate = is_array($slotsByDate) ? $slotsByDate : [];
            $appointments = is_array($appointments) ? $appointments : [];
            $ocupados = [];
            foreach ($appointments as $appt) {
                $date = substr($appt['start_datetime'], 0, 10);
                $start = substr($appt['start_datetime'], 11, 5);
                $table = $appt['table_number'];
                $ocupados[$date][$start][$table] = $appt;
            }
            
            // LOG: Debug de appointments del servidor
            error_log("DEBUG SLOTS: Total appointments cargados desde servidor: " . count($appointments));
            error_log("DEBUG SLOTS: Active day: " . ($activeDay ?? 'null'));
            if (!empty($appointments)) {
                error_log("DEBUG SLOTS: Primer appointment: " . json_encode($appointments[0]));
            }
            error_log("DEBUG SLOTS: Ocupados structure: " . json_encode(array_keys($ocupados)));
            $days = array_keys($slotsByDate);
            $activeDay = $_GET['day'] ?? ($days[0] ?? null);
            $mesas = range(1, (int)$availableTables);
            $horas = [];
            if (!empty($slotsByDate[$activeDay])) {
                foreach ($slotsByDate[$activeDay] as $slot) {
                    $start = substr($slot['start_datetime'], 11, 5);
                    $end = substr($slot['end_datetime'], 11, 5);
                    $horas[$start.'-'.$end] = true;
                }
                $horas = array_keys($horas);
                sort($horas);
            }
            ?>
            
            <?php if (!empty($days)): ?>
                <!-- Tabs para días -->
                <div class="tabs-material">
                    <div class="tabs-material__list">
                        <?php foreach ($days as $day): ?>
                            <a href="?day=<?= urlencode($day) ?>" 
                               class="tabs-material__tab <?= ($day === $activeDay) ? 'tabs-material__tab--active' : '' ?>">
                                <div class="tabs-material__label">
                                    <?= date('D j/n', strtotime($day)) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($activeDay && !empty($horas)): ?>
                    <!-- Tabla de horarios -->
                    <div class="table-responsive">
                        <table class="table-material table-material--schedule">
                            <thead class="table-material__header">
                                <tr>
                                    <th class="table-material__cell table-material__cell--header table-material__cell--time">
                                        Horario
                                    </th>
                                    <?php for ($mesa = 1; $mesa <= $availableTables; $mesa++): ?>
                                        <th class="table-material__cell table-material__cell--header">
                                            Mesa <?= $mesa ?>
                                        </th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horas as $hora): ?>
                                    <tr class="table-material__row">
                                        <td class="table-material__cell table-material__cell--time">
                                            <?= htmlspecialchars($hora) ?>
                                        </td>
                                        <?php for ($mesa = 1; $mesa <= $availableTables; $mesa++): ?>
                                            <td class="table-material__cell table-material__cell--slot">
                                                <?php
                                                $horaStart = explode('-', $hora)[0];
                                                $isOcupado = isset($ocupados[$activeDay][$horaStart][$mesa]);
                                                
                                                // LOG: Debug de cada slot individual
                                                if ($mesa == 1 && $hora == array_slice($horas, 0, 1)[0]) { // Solo log para el primer slot de la primera hora
                                                    error_log("DEBUG SLOT INDIVIDUAL: activeDay=$activeDay, hora=$hora, horaStart=$horaStart, mesa=$mesa");
                                                    error_log("DEBUG SLOT INDIVIDUAL: isOcupado=" . ($isOcupado ? 'true' : 'false'));
                                                    error_log("DEBUG SLOT INDIVIDUAL: ocupados[$activeDay] exists: " . (isset($ocupados[$activeDay]) ? 'true' : 'false'));
                                                    if (isset($ocupados[$activeDay])) {
                                                        error_log("DEBUG SLOT INDIVIDUAL: ocupados[$activeDay] keys: " . json_encode(array_keys($ocupados[$activeDay])));
                                                    }
                                                }
                                                ?>
                                                <div class="slot-material <?= $isOcupado ? 'slot-material--tooltip' : '' ?>">
                                                    <div class="slot-material__status slot-material__status--<?= $isOcupado ? 'occupied' : 'available' ?>">
                                                        <?php if ($isOcupado): ?>
                                                            <span class="slot-material__text">Ocupado</span>
                                                            <?php $appt = $ocupados[$activeDay][$horaStart][$mesa]; ?>
                                                            <span class="slot-material__company">
                                                                <?= htmlspecialchars(substr($appt['buyer_company'] ?? 'N/A', 0, 12)) ?>
                                                            </span>
                                                            <span class="slot-material__company">
                                                                <?= htmlspecialchars(substr($appt['supplier_company'] ?? 'N/A', 0, 12)) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="slot-material__text">Disponible</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($isOcupado): ?>
                                                        <div class="tooltip-content">
                                                            <strong>Cita programada</strong><br>
                                                            <strong>Comprador:</strong> <?= htmlspecialchars($appt['buyer_company'] ?? 'N/A') ?><br>
                                                            <strong>Proveedor:</strong> <?= htmlspecialchars($appt['supplier_company'] ?? 'N/A') ?><br>
                                                            <strong>Horario:</strong> <?= htmlspecialchars($hora) ?><br>
                                                            <strong>Mesa:</strong> <?= $mesa ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state__icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="empty-state__text">
                            No hay horarios disponibles para el día seleccionado.
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state__icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="empty-state__text">
                        No hay días programados para este evento.
                    </div>
                </div>
            <?php endif; ?>
            
            <?php $scheduleBreakdownContent = ob_get_clean(); ?>
            <?= materialCard(
                '<i class="fas fa-calendar-alt"></i> Desglose de Horarios',
                $scheduleBreakdownContent . 
                '<div class="last-updated" id="last-updated-indicator">
                    <span class="auto-refresh-indicator">
                        <i class="fas fa-sync-alt"></i>
                        Actualización automática activa
                    </span>
                    <span id="last-updated-time">Última actualización: ' . date('H:i:s') . '</span>
                </div>'
            ) ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh para time slots
class TimeSlotAutoRefresh {
    constructor() {
        this.eventId = <?= json_encode($eventId) ?>;
        this.activeDay = <?= json_encode($activeDay) ?>;
        this.refreshInterval = 30000; // 30 segundos
        this.intervalId = null;
        this.isUpdating = false;
        this.init();
    }
    
    init() {
        console.log('TimeSlot Auto-refresh iniciado para evento:', this.eventId, 'día activo:', this.activeDay);
        
        // Hacer una actualización inmediata al cargar
        this.refreshScheduleData();
        
        // Luego iniciar el auto-refresh
        this.startAutoRefresh();
        
        // Detener auto-refresh cuando la página no está visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopAutoRefresh();
            } else {
                this.startAutoRefresh();
            }
        });
    }
    
    startAutoRefresh() {
        if (this.intervalId) return; // Ya está ejecutándose
        
        this.intervalId = setInterval(() => {
            this.refreshScheduleData();
        }, this.refreshInterval);
        
        console.log('Auto-refresh iniciado con intervalo de', this.refreshInterval / 1000, 'segundos');
    }
    
    stopAutoRefresh() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            console.log('Auto-refresh detenido');
        }
    }
    
    async refreshScheduleData() {
        if (this.isUpdating) return;
        
        this.isUpdating = true;
        const scheduleTable = document.querySelector('.table-material--schedule');
        
        if (scheduleTable) {
            scheduleTable.classList.add('schedule-updating');
        }
        
        try {
            console.log('Enviando petición AJAX para event_id:', this.eventId, 'day:', this.activeDay);
            const response = await fetch(`<?= BASE_URL ?>/events/getTimeSlotDataAjax`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=${this.eventId}&day=${encodeURIComponent(this.activeDay)}&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>`
            });
            
            console.log('Respuesta recibida:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error en respuesta:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Datos recibidos:', data);
            
            if (data.success) {
                this.updateScheduleDisplay(data.slots);
                this.updateLastUpdatedTime();
                console.log('Schedule actualizado:', data.slots.length, 'slots procesados');
            } else {
                console.warn('Error en respuesta del servidor:', data.message);
            }
            
        } catch (error) {
            console.error('Error actualizando schedule:', error);
        } finally {
            if (scheduleTable) {
                scheduleTable.classList.remove('schedule-updating');
            }
            this.isUpdating = false;
        }
    }
    
    updateScheduleDisplay(slots) {
        console.log('=== DEBUG UPDATESCHEDULEDISPLAY ===');
        console.log('Total slots recibidos:', slots.length);
        console.log('Primer slot:', slots[0]);
        
        // Contar slots por estado
        const byStatus = {};
        slots.forEach(slot => {
            byStatus[slot.status] = (byStatus[slot.status] || 0) + 1;
        });
        console.log('Slots por estado:', byStatus);
        
        // Crear mapa de slots ocupados
        const ocupados = {};
        let ocupadosCount = 0;
        slots.forEach(slot => {
            if (slot.status === 'occupied' && slot.appointment) {
                const date = slot.date;
                // Normalizar formato de tiempo: convertir "09:00:00" a "09:00"
                const time = slot.start_time.substring(0, 5); // Tomar solo HH:MM
                const table = slot.table_number;
                
                if (!ocupados[date]) ocupados[date] = {};
                if (!ocupados[date][time]) ocupados[date][time] = {};
                ocupados[date][time][table] = slot.appointment;
                ocupadosCount++;
            }
        });
        
        console.log('Slots marcados como ocupados:', ocupadosCount);
        console.log('Estructura ocupados:', ocupados);
        console.log('Claves de tiempo en ocupados:', ocupados[this.activeDay] ? Object.keys(ocupados[this.activeDay]) : 'N/A');
        console.log('Active day:', this.activeDay);
        
        // Actualizar cada celda de la tabla
        const cells = document.querySelectorAll('.table-material__cell--slot');
        console.log('Total celdas encontradas en DOM:', cells.length);
        
        let celdasProcesadas = 0;
        let celdasActualizadas = 0;
        
        cells.forEach(cell => {
            const row = cell.parentElement;
            const timeCell = row.querySelector('.table-material__cell--time');
            const mesa = Array.from(row.children).indexOf(cell);
            
            if (timeCell && mesa > 0) {
                celdasProcesadas++;
                const horaCompleta = timeCell.textContent.trim();
                const horaStart = horaCompleta.split('-')[0];
                
                // LOG DETALLADO: Debug de cada comparación
                if (celdasProcesadas <= 5) { // Solo los primeros 5 para no saturar
                    console.log(`DEBUG CELDA ${celdasProcesadas}:`, {
                        horaCompleta: horaCompleta,
                        horaStart: horaStart,
                        mesa: mesa,
                        activeDay: this.activeDay,
                        'ocupados[activeDay] exists': !!ocupados[this.activeDay],
                        'ocupados[activeDay][horaStart] exists': !!(ocupados[this.activeDay] && ocupados[this.activeDay][horaStart]),
                        'ocupados[activeDay][horaStart][mesa] exists': !!(ocupados[this.activeDay] && ocupados[this.activeDay][horaStart] && ocupados[this.activeDay][horaStart][mesa]),
                        'horas disponibles en ocupados': ocupados[this.activeDay] ? Object.keys(ocupados[this.activeDay]) : 'N/A'
                    });
                }
                
                const isOcupado = ocupados[this.activeDay] && 
                                 ocupados[this.activeDay][horaStart] && 
                                 ocupados[this.activeDay][horaStart][mesa];
                
                if (isOcupado) {
                    celdasActualizadas++;
                    console.log(`Actualizando celda OCUPADA: hora=${horaStart}, mesa=${mesa}`, ocupados[this.activeDay][horaStart][mesa]);
                }
                
                this.updateSlotCell(cell, isOcupado, ocupados[this.activeDay]?.[horaStart]?.[mesa], horaCompleta, mesa);
            }
        });
        
        console.log(`Celdas procesadas: ${celdasProcesadas}, Celdas marcadas como ocupadas: ${celdasActualizadas}`);
    }
    
    updateSlotCell(cell, isOcupado, appointmentData, hora, mesa) {
        const slotDiv = cell.querySelector('.slot-material');
        if (!slotDiv) return;
        
        const statusDiv = slotDiv.querySelector('.slot-material__status');
        const existingTooltip = slotDiv.querySelector('.tooltip-content');
        
        // Actualizar clases y contenido
        if (isOcupado) {
            slotDiv.classList.add('slot-material--tooltip');
            statusDiv.className = 'slot-material__status slot-material__status--occupied';
            statusDiv.innerHTML = `
                <span class="slot-material__text">Ocupado</span>
                <span class="slot-material__company">${appointmentData.buyer_company ? appointmentData.buyer_company.substring(0, 12) : 'N/A'}</span>
                <span class="slot-material__company">${appointmentData.supplier_company ? appointmentData.supplier_company.substring(0, 12) : 'N/A'}</span>
            `;
            
            // Actualizar o crear tooltip
            if (existingTooltip) {
                existingTooltip.innerHTML = this.generateTooltipContent(appointmentData, hora, mesa);
            } else {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-content';
                tooltip.innerHTML = this.generateTooltipContent(appointmentData, hora, mesa);
                slotDiv.appendChild(tooltip);
            }
        } else {
            slotDiv.classList.remove('slot-material--tooltip');
            statusDiv.className = 'slot-material__status slot-material__status--available';
            statusDiv.innerHTML = '<span class="slot-material__text">Disponible</span>';
            
            // Remover tooltip si existe
            if (existingTooltip) {
                existingTooltip.remove();
            }
        }
    }
    
    generateTooltipContent(appointment, hora, mesa) {
        return `
            <strong>Cita programada</strong><br>
            <strong>Comprador:</strong> ${appointment.buyer_company || 'N/A'}<br>
            <strong>Proveedor:</strong> ${appointment.supplier_company || 'N/A'}<br>
            <strong>Horario:</strong> ${hora}<br>
            <strong>Mesa:</strong> ${mesa}
        `;
    }
    
    updateLastUpdatedTime() {
        const timeElement = document.getElementById('last-updated-time');
        if (timeElement) {
            timeElement.textContent = `Última actualización: ${new Date().toLocaleTimeString()}`;
        }
    }
}

// Inicializar auto-refresh cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.timeSlotRefresh = new TimeSlotAutoRefresh();
});

// Limpiar interval al salir de la página
window.addEventListener('beforeunload', function() {
    if (window.timeSlotRefresh) {
        window.timeSlotRefresh.stopAutoRefresh();
    }
});
</script>

<style>
/* Time Slots Material Design 3 Styles */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.page-header__content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.page-subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    font-size: 1rem;
}

.page-header__actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.time-slots-container {
    display: grid;
    gap: 2rem;
}

.capacity-section,
.schedule-breakdown-section {
    margin-bottom: 2rem;
}

/* Capacity Stats */
.capacity-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    border: 1px solid var(--md-outline-variant);
}

.stat-item__icon {
    width: 3rem;
    height: 3rem;
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
    border-radius: var(--md-shape-corner-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-item__content {
    flex: 1;
    min-width: 0;
}

.stat-item__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--md-on-surface);
    line-height: 1.2;
}

.stat-item__label {
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
    margin-top: 0.25rem;
}

/* Breaks Table */
.breaks-section {
    margin-top: 2rem;
}

.breaks-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin-bottom: 1rem;
}

/* Material Design 3 Tabs for Days */
.tabs-material {
    width: 100%;
    margin-bottom: 2rem;
}

.tabs-material__list {
    display: flex;
    border-bottom: 1px solid var(--md-outline-variant);
    overflow-x: auto;
    gap: 0;
}

.tabs-material__tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: var(--md-on-surface-variant);
    position: relative;
    min-width: 120px;
    transition: all 200ms cubic-bezier(0.2, 0.0, 0, 1.0);
    border-bottom: 2px solid transparent;
}

.tabs-material__tab:hover {
    background: var(--md-surface-container-lowest);
    color: var(--md-on-surface);
}

.tabs-material__tab--active {
    color: var(--md-primary-40);
    border-bottom-color: var(--md-primary-40);
    background: var(--md-primary-container);
}

.tabs-material__label {
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
}

/* Schedule Table */
.table-material--schedule {
    min-width: 800px;
}

.table-material__cell--time {
    font-weight: 600;
    color: var(--md-on-surface);
    text-align: center;
    white-space: nowrap;
    background: var(--md-surface-container-lowest);
}

.table-material__cell--slot {
    text-align: center;
    min-width: 100px;
    padding: 0.5rem;
}

.table-material__cell--empty {
    text-align: center;
    color: var(--md-on-surface-variant);
    font-style: italic;
    padding: 2rem;
}

/* Material Design 3 Slots */
.slot-material {
    position: relative;
    display: inline-block;
    width: 100%;
    overflow: visible;
}

.table-material__cell--slot {
    position: relative;
    overflow: visible;
}

.slot-material--tooltip:hover .tooltip-content {
    display: block !important;
}

.slot-material__status {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    padding: 0.4rem;
    border-radius: var(--md-shape-corner-small);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: help;
    transition: all 200ms ease;
    min-height: 3.5rem;
    justify-content: center;
}

.slot-material__status--occupied {
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
}

.slot-material__status--available {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.slot-material__status--unavailable {
    background: var(--md-surface-container);
    color: var(--md-on-surface-variant);
}

.slot-material__text {
    font-size: 0.75rem;
    line-height: 1;
}

.slot-material__company {
    font-size: 0.6rem;
    line-height: 1.1;
    opacity: 0.9;
    text-align: center;
    font-weight: 500;
}

/* Tooltip */
.tooltip-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    background: #2d3748;
    color: #ffffff;
    padding: 0.75rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    font-size: 0.75rem;
    line-height: 1.4;
    white-space: nowrap;
    min-width: 200px;
    margin-top: 0.5rem;
    border: 1px solid #4a5568;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

.slot-material--tooltip:hover .tooltip-content {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Indicadores de actualización */
.schedule-updating {
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.last-updated {
    font-size: 0.75rem;
    color: var(--md-on-surface-variant);
    text-align: right;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-small);
}

.auto-refresh-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.625rem;
    color: var(--md-primary);
}

.auto-refresh-indicator .fa-sync-alt {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.tooltip-content::before {
    content: '';
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid #2d3748;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state__icon {
    font-size: 4rem;
    color: var(--md-outline);
    margin-bottom: 1rem;
}

.empty-state__text {
    color: var(--md-on-surface-variant);
    font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .capacity-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-item {
        padding: 0.75rem;
    }
    
    .stat-item__icon {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 1rem;
    }
    
    .stat-item__value {
        font-size: 1.25rem;
    }
    
    .tabs-material__tab {
        min-width: 100px;
        padding: 0.75rem 1rem;
    }
    
    .table-material__cell--slot {
        min-width: 80px;
        padding: 0.25rem;
    }
    
    .slot-material__status {
        min-height: 2.5rem;
        padding: 0.25rem;
    }
    
    .tooltip-content {
        min-width: 150px;
        font-size: 0.6875rem;
        padding: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Asegurar que los tooltips funcionen correctamente
    const tooltipElements = document.querySelectorAll('.slot-material--tooltip');
    
    tooltipElements.forEach(element => {
        const tooltip = element.querySelector('.tooltip-content');
        
        if (tooltip) {
            element.addEventListener('mouseenter', function() {
                tooltip.style.display = 'block';
                tooltip.style.opacity = '1';
                tooltip.style.visibility = 'visible';
            });
            
            element.addEventListener('mouseleave', function() {
                tooltip.style.display = 'none';
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            });
        }
    });
});
</script>

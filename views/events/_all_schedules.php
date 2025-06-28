<?php
// Partial: All appointments with Material Design 3 - Table Layout
// Expects: $agendas (array of agendas agrupadas por company)
?>
<div class="agenda-table-layout">
    <?php 
    // Collect all appointments from all companies
    $allAppointments = [];
    foreach ($agendas as $agenda) {
        if (!empty($agenda['appointments']) && isset($agenda['company'])) {
            foreach ($agenda['appointments'] as $appointment) {
                $appointment['company_data'] = $agenda['company'];
                $allAppointments[] = $appointment;
            }
        }
    }
    
    // Sort appointments by date and time
    usort($allAppointments, function($a, $b) {
        return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
    });
    ?>
    <?php if (!empty($allAppointments)): ?>
        <div class="table-container">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Comprador</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Mesa</th>
                        <th>Horario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allAppointments as $appointment): ?>
                        <tr class="appointment-row">
                            <td class="buyer-cell">
                                <div class="company-info">
                                    <i class="fas fa-shopping-cart company-icon company-icon--buyer"></i>
                                    <span class="company-name"><?= htmlspecialchars($appointment['buyer_name'] ?? '') ?></span>
                                </div>
                            </td>
                            <td class="supplier-cell">
                                <div class="company-info">
                                    <i class="fas fa-industry company-icon company-icon--supplier"></i>
                                    <span class="company-name"><?= htmlspecialchars($appointment['supplier_name'] ?? '') ?></span>
                                </div>
                            </td>
                            <td class="date-cell">
                                <div class="date-info">
                                    <i class="fas fa-calendar-day"></i>
                                    <span><?= date('d/m/Y', strtotime($appointment['start_datetime'])) ?></span>
                                </div>
                            </td>
                            <td class="table-cell">
                                <div class="table-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Mesa <?= htmlspecialchars($appointment['table_number'] ?? '') ?></span>
                                </div>
                            </td>
                            <td class="time-cell">
                                <div class="time-info">
                                    <i class="fas fa-clock"></i>
                                    <span><?= date('H:i', strtotime($appointment['start_datetime'])) ?> - <?= date('H:i', strtotime($appointment['end_datetime'])) ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-agendas">
            <i class="fas fa-calendar-alt"></i>
            <h3>No hay citas programadas</h3>
            <p>Las citas aparecerán aquí una vez que sean programadas</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Material Design 3 Styles for Agenda Table Layout */
.agenda-table-layout {
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-large);
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
}

.appointments-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--md-surface);
    border: 1px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-medium);
    overflow: hidden;
}

.appointments-table thead th {
    background: #f8f9fa;
    color: #495057;
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e9ecef;
    white-space: nowrap;
}

.appointments-table thead th:first-child {
    border-top-left-radius: var(--md-shape-corner-medium);
}

.appointments-table thead th:last-child {
    border-top-right-radius: var(--md-shape-corner-medium);
}

.appointment-row {
    transition: background-color var(--md-motion-duration-short2);
    border-bottom: 1px solid #e9ecef;
}

.appointment-row:nth-child(even) {
    background: #f8f9fa;
}

.appointment-row:hover {
    background: #e3f2fd;
}

.appointment-row:last-child {
    border-bottom: none;
}

.appointments-table td {
    padding: 1rem;
    vertical-align: middle;
    border-right: 1px solid #e9ecef;
    font-size: 0.875rem;
}

.appointments-table td:last-child {
    border-right: none;
}

.company-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.date-info,
.time-info,
.table-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.company-icon {
    font-size: 0.875rem;
    flex-shrink: 0;
}

.company-icon--buyer {
    color: var(--md-tertiary-40);
}

.company-icon--supplier {
    color: var(--md-secondary-40);
}

.date-info i,
.time-info i,
.table-info i {
    color: var(--md-on-surface-variant);
    font-size: 0.75rem;
    width: 14px;
}

.company-name {
    font-weight: 500;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

/* Responsive table styling */

.no-agendas {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
    color: var(--md-on-surface-variant);
}

.no-agendas i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.6;
}

.no-agendas h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: var(--md-on-surface);
}

.no-agendas p {
    margin: 0;
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .appointments-table th,
    .appointments-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .company-name {
        font-size: 0.8rem;
    }
    
    .company-info {
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .appointments-table th:nth-child(4),
    .appointments-table td:nth-child(4) {
        display: none;
    }
    
    .appointments-table th,
    .appointments-table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.75rem;
    }
}
</style>

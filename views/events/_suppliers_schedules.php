<?php
// Partial: Agenda for suppliers with Material Design 3 - Table Layout
// Expects: $agendas (array of suppliers with their agendas)
?>
<div class="agenda-table-layout">
    <?php 
    // Filter only suppliers with appointments
    $suppliersWithAppointments = array_filter($agendas, function($agenda) {
        return !empty($agenda['appointments']) && 
               isset($agenda['company']) && 
               ($agenda['company']['role'] ?? null) === 'supplier';
    });
    ?>
    <?php if (!empty($suppliersWithAppointments)): ?>
        <div class="table-container">
            <table class="companies-agenda-table">
                <thead>
                    <tr>
                        <th>Empresa Proveedora</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliersWithAppointments as $agenda): ?>
                        <?php
                        if (!isset($agenda['company']) || !is_array($agenda['company'])) continue;
                        $supplier = $agenda['company'];
                        ?>
                        <tr class="company-row company-row--supplier">
                            <td class="company-name-cell">
                                <div class="company-info">
                                    <i class="fas fa-industry company-icon"></i>
                                    <span class="company-name"><?= htmlspecialchars($supplier['company_name'] ?? '') ?></span>
                                </div>
                            </td>
                            <td class="appointments-status-cell">
                                <?php $appointmentCount = count($agenda['appointments'] ?? []); ?>
                                <span class="status-badge status-badge--active">
                                    <i class="fas fa-check-circle"></i>
                                    <?= $appointmentCount ?> citas
                                </span>
                            </td>
                            <td class="actions-cell">
                                <div class="action-buttons">
                                    <a href="<?= BASE_URL ?>/agendas/preview?event_id=<?= (int)($supplier['event_id'] ?? $event->getId() ?? 0) ?>&company_id=<?= (int)($supplier['company_id'] ?? 0) ?>" 
                                       class="btn-action btn-action--view" target="_blank" title="Ver agenda">
                                        <i class="fas fa-eye"></i>
                                        Ver
                                    </a>
                                    <a href="#" class="btn-action btn-action--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-agendas">
            <i class="fas fa-industry"></i>
            <h3>No hay proveedores con citas programadas</h3>
            <p>Los proveedores aparecerán aquí una vez que tengan citas asignadas</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Material Design 3 Styles for Suppliers Agenda Table Layout */
.agenda-table-layout {
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-large);
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
}

.companies-agenda-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--md-surface);
    border: 1px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-medium);
    overflow: hidden;
}

.companies-agenda-table thead th {
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

.companies-agenda-table thead th:first-child {
    border-top-left-radius: var(--md-shape-corner-medium);
}

.companies-agenda-table thead th:last-child {
    border-top-right-radius: var(--md-shape-corner-medium);
}

.company-row--supplier {
    border-left: 3px solid var(--md-secondary-40);
}

.company-row {
    transition: background-color var(--md-motion-duration-short2);
    border-bottom: 1px solid #e9ecef;
}

.company-row:nth-child(even) {
    background: #f8f9fa;
}

.company-row:hover {
    background: #e3f2fd;
}

.company-row:last-child {
    border-bottom: none;
}

.companies-agenda-table td {
    padding: 1rem;
    vertical-align: middle;
    border-right: 1px solid #e9ecef;
    font-size: 0.875rem;
}

.companies-agenda-table td:last-child {
    border-right: none;
}

.company-name-cell .company-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.company-icon {
    color: var(--md-secondary-40);
    font-size: 1rem;
    flex-shrink: 0;
}

.company-name {
    font-weight: 500;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

.status-badge--active {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
}

.status-badge--inactive {
    background: var(--md-surface-container-high);
    color: var(--md-on-surface-variant);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: all var(--md-motion-duration-short2);
    white-space: nowrap;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-action--view {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
    border: 1px solid var(--md-primary-40);
}

.btn-action--view:hover {
    background: var(--md-primary-40);
    color: var(--md-on-primary);
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: var(--md-elevation-1);
}

.btn-action--edit {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
    border: 1px solid var(--md-secondary-40);
}

.btn-action--edit:hover {
    background: var(--md-secondary-40);
    color: var(--md-on-secondary);
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: var(--md-elevation-1);
}

.btn-action--disabled {
    background: var(--md-surface-container);
    color: var(--md-on-surface-variant);
    border: 1px solid var(--md-outline-variant);
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-action--disabled:hover {
    transform: none;
    box-shadow: none;
}

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
    .companies-agenda-table th,
    .companies-agenda-table td {
        padding: 0.75rem 1rem;
    }
    
    .company-name {
        font-size: 0.8rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .btn-action {
        font-size: 0.7rem;
        padding: 0.375rem 0.5rem;
    }
}

@media (max-width: 480px) {
    .action-buttons {
        flex-direction: row;
        justify-content: center;
    }
}
</style>

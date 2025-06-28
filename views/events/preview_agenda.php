<?php
// Vista de previsualización de agenda para PDF y .ics
// Espera: $company, $appointments, $event
?>
<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">
                <i class="fas fa-calendar-check page-title-icon"></i>
                Previsualización de Agenda
            </h1>
            <p class="page-subtitle">Vista previa de citas programadas</p>
        </div>
        <div class="page-header__actions">
            <a href="<?= BASE_URL ?>/agendas/<?= (int)$event->getId() ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Regresar a Agendas
            </a>
        </div>
    </div>

    <!-- Company Info Card -->
    <div class="company-info-card">
        <div class="company-info-header">
            <div class="company-info-main">
                <h2 class="company-name">
                    <i class="fas <?= ($rol = is_array($company) ? ($company['role'] ?? '') : ($company->getRole() ?? '')) === 'buyer' ? 'fa-shopping-cart' : 'fa-industry' ?> company-type-icon"></i>
                    <?= htmlspecialchars(is_array($company) ? ($company['company_name'] ?? '') : ($company->getCompanyName() ?? '')) ?>
                </h2>
                <span class="company-role <?= $rol === 'buyer' ? 'company-role--buyer' : 'company-role--supplier' ?>">
                    <?php
                    if ($rol === 'buyer') {
                        echo 'Empresa Compradora';
                    } elseif ($rol === 'supplier') {
                        echo 'Empresa Proveedora';
                    } else {
                        echo htmlspecialchars($rol);
                    }
                    ?>
                </span>
            </div>
            <div class="event-info">
                <i class="fas fa-calendar-alt"></i>
                <span><?= htmlspecialchars($event->getEventName()) ?></span>
            </div>
        </div>
    </div>

    <!-- Appointments Table -->
    <?php if (!empty($appointments)): ?>
        <div class="appointments-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-list-ul"></i>
                    Citas Programadas
                </h3>
                <span class="appointments-count"><?= count($appointments) ?> citas</span>
            </div>
            
            <div class="table-container">
                <table class="preview-appointments-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Mesa</th>
                            <th>Comprador</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr class="appointment-row">
                                <td class="date-cell">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= date('d/m/Y', strtotime($appt['start_datetime'])) ?>
                                </td>
                                <td class="time-cell">
                                    <i class="fas fa-clock"></i>
                                    <?= date('H:i', strtotime($appt['start_datetime'])) ?> - <?= date('H:i', strtotime($appt['end_datetime'])) ?>
                                </td>
                                <td class="table-cell">
                                    <span class="table-badge">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Mesa <?= htmlspecialchars($appt['table_number']) ?>
                                    </span>
                                </td>
                                <td class="participant-cell">
                                    <i class="fas fa-shopping-cart participant-icon--buyer"></i>
                                    <?= htmlspecialchars($appt['buyer_name']) ?>
                                </td>
                                <td class="participant-cell">
                                    <i class="fas fa-industry participant-icon--supplier"></i>
                                    <?= htmlspecialchars($appt['supplier_name']) ?>
                                </td>
                                <td class="status-cell">
                                    <span class="status-badge <?= $appt['status'] === 'scheduled' ? 'status-badge--scheduled' : 'status-badge--other' ?>">
                                        <i class="fas <?= $appt['status'] === 'scheduled' ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                                        <?= htmlspecialchars($appt['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions-section">
            <div class="action-buttons">
                <a href="<?= BASE_URL ?>/agendas/download_pdf?event_id=<?= (int)$event->getId() ?>&company_id=<?= (int)(is_array($company) ? $company['company_id'] : $company->getId()) ?>" 
                   class="btn-action btn-action--pdf" target="_blank">
                    <i class="fas fa-file-pdf"></i>
                    <span>Descargar PDF</span>
                </a>
                <a href="<?= BASE_URL ?>/agendas/download_ics?event_id=<?= (int)$event->getId() ?>&company_id=<?= (int)(is_array($company) ? $company['company_id'] : $company->getId()) ?>" 
                   class="btn-action btn-action--calendar" target="_blank">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Descargar .ics</span>
                </a>
                <a href="#" class="btn-action btn-action--send">
                    <i class="fas fa-paper-plane"></i>
                    <span>Enviar Agenda</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="no-appointments">
            <div class="no-appointments-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h3>Sin citas programadas</h3>
            <p>No hay citas agendadas para esta empresa en este evento.</p>
        </div>
    <?php endif; ?>
</div>

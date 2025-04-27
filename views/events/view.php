<!-- views/events/view.php -->
<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($eventModel->getEventName()) ?></h1>
                <p class="text-gray-500 text-sm">Detalles y gestión del evento.</p>
            </div>
            <div class="actions">
                <a href="<?= BASE_URL ?>/events" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                <a href="<?= BASE_URL ?>/events/edit/<?= $eventModel->getId() ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Editar</a>
            </div>
        </div>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="event-view-card p-6 flex flex-col gap-2 col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="event-view-title flex items-center gap-2"><i class="fas fa-info-circle"></i> Información del Evento</h2>
                <a href="<?= BASE_URL ?>/categories/create?event_id=<?= $eventModel->getId() ?>" class="btn btn-sm btn-action-blue"><i class="fas fa-plus"></i> Agregar Categorías</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 event-view-section">
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-map-marker-alt text-blue-500"></i><span class="font-semibold">Sede:</span><span><?= htmlspecialchars($eventModel->getVenue()) ?></span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="far fa-calendar-alt text-blue-500"></i><span class="font-semibold">Fechas:</span><span><?= dateFromDatabase($eventModel->getStartDate()) ?> - <?= dateFromDatabase($eventModel->getEndDate()) ?></span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-clock text-blue-500"></i><span class="font-semibold">Horario:</span><span><?= substr($eventModel->getStartTime(), 0, 5) ?> - <?= substr($eventModel->getEndTime(), 0, 5) ?></span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-table text-blue-500"></i><span class="font-semibold">Mesas:</span><span><?= $eventModel->getAvailableTables() ?></span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-stopwatch text-blue-500"></i><span class="font-semibold">Duración reunión:</span><span><?= $eventModel->getMeetingDuration() ?> min</span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-building text-blue-500"></i><span class="font-semibold">Empresa Organizadora:</span><span><?= !empty($eventModel->getCompanyName()) ? htmlspecialchars($eventModel->getCompanyName()) : '<em>No especificada</em>' ?></span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-user text-blue-500"></i><span class="font-semibold">Contacto:</span><span><?= !empty($eventModel->getContactName()) ? htmlspecialchars($eventModel->getContactName()) : '<em>No especificado</em>' ?></span></div>
                <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-envelope text-blue-500"></i><span class="font-semibold">Email:</span><span><?= !empty($eventModel->getContactEmail()) ? htmlspecialchars($eventModel->getContactEmail()) : '<em>No especificado</em>' ?></span></div>
            </div>
            <div class="flex items-center gap-2 mt-4">
                <span class="event-view-badge <?= $eventModel->isActive() ? 'active' : 'inactive' ?>">
                    <?= $eventModel->isActive() ? 'Activo' : 'Inactivo' ?>
                </span>
            </div>
        </div>
        <div class="event-view-card p-6 flex flex-col gap-2">
            <h2 class="event-view-title mb-4 flex items-center gap-2"><i class="fas fa-chart-bar"></i> Estadísticas</h2>
            <div class="flex flex-col gap-2 event-view-section">
                <div class="flex items-center gap-2"><i class="fas fa-users text-blue-500"></i><span class="font-semibold">Participantes:</span><span><?= count($buyers) + count($suppliers) ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-handshake text-blue-500"></i><span class="font-semibold">Matches:</span><span><?= $matchCount ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-calendar-check text-blue-500"></i><span class="font-semibold">Citas:</span><span><?= $scheduleCount ?></span></div>
            </div>
        </div>
    </div>
    <ul class="event-actions-list mt-4 mb-8">
        <li class="mb-2" style="margin-bottom:2px;">
            <a href="<?= BASE_URL ?>/events/participants/<?= $eventModel->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-users"></i> Ver Participantes</a>
        </li>
        <li class="mb-2" style="margin-bottom:2px;">
            <a href="<?= BASE_URL ?>/events/matches/<?= $eventModel->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-handshake"></i> Ver Matches</a>
        </li>
        <li class="mb-2" style="margin-bottom:2px;">
            <a href="<?= BASE_URL ?>/events/schedules/<?= $eventModel->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-calendar-alt"></i> Ver Citas</a>
        </li>
        <li style="margin-bottom:2px;">
            <a href="<?= BASE_URL ?>/events/time-slots/<?= $eventModel->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-clock"></i> Ver Horarios</a>
        </li>
    </ul>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="event-view-card">
            <div class="card-header flex items-center justify-between">
                <h2 class="event-view-title flex items-center gap-2"><i class="fas fa-coffee"></i> Descansos Programados</h2>
                <a href="<?= BASE_URL ?>/events/breaks/<?= $eventModel->getId() ?>" class="btn btn-sm btn-primary"><i class="fas fa-cog"></i> Gestionar</a>
            </div>
            <div class="card-body event-view-section">
                <?php if (empty($breaks)): ?>
                    <div class="empty-state small"><p>No hay descansos configurados para este evento.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Inicio</th><th>Fin</th><th>Duración</th></tr></thead>
                            <tbody>
                                <?php foreach ($breaks as $break): ?>
                                    <tr>
                                        <td><?= substr($break['start_time'], 0, 5) ?></td>
                                        <td><?= substr($break['end_time'], 0, 5) ?></td>
                                        <td><?php $start = new DateTime($break['start_time']); $end = new DateTime($break['end_time']); $interval = $start->diff($end); echo $interval->format('%H:%I'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="event-view-card">
            <div class="card-header">
                <h2 class="event-view-title flex items-center gap-2"><i class="fas fa-cog"></i> Estado y Configuración</h2>
            </div>
            <div class="card-body event-view-section">
                <form action="<?= BASE_URL ?>/events/toggle-active/<?= $eventModel->getId() ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?= $eventModel->isActive() ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="custom-control-label" for="is_active">
                                <?= $eventModel->isActive() ? 'Evento Activo' : 'Evento Inactivo' ?>
                            </label>
                        </div>
                    </div>
                </form>
                <div class="mt-3">
                    <label>Duración del Evento</label>
                    <p>
                        <?php $startDate = new DateTime($eventModel->getStartDate()); $endDate = new DateTime($eventModel->getEndDate()); $interval = $startDate->diff($endDate); echo $interval->days + 1 . ' días'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
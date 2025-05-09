<!-- views/events/view.php -->
<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($event->getEventName()) ?></h1>
                <p class="text-gray-500 text-sm">Detalles y gestión del evento.</p>
            </div>
            <div class="actions">
                <a href="<?= BASE_URL ?>/events" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                <a href="<?= BASE_URL ?>/events/edit/<?= $event->getId() ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Editar</a>
            </div>
        </div>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Información del evento y menú rápido (izquierda) -->
        <div class="md:col-span-2 flex flex-col gap-6">
            <div class="event-view-card p-6 flex flex-col gap-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="event-view-title flex items-center gap-2"><i class="fas fa-info-circle"></i> Información del Evento</h2>
                    <a href="javascript:void(0);" onclick="openImportCategoriesModal();" class="btn btn-sm btn-action-blue <?= ($hasCategories ? 'pointer-events-none opacity-50 cursor-not-allowed' : '') ?>" <?= $hasCategories ? 'tabindex=\"-1\" aria-disabled=\"true\"' : '' ?>><i class="fas fa-plus"></i> Agregar Categorías</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 event-view-section">
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-map-marker-alt text-blue-500"></i><span class="font-semibold">Sede:</span><span><?= htmlspecialchars($event->getVenue()) ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="far fa-calendar-alt text-blue-500"></i><span class="font-semibold">Fechas:</span><span><?= dateFromDatabase($event->getStartDate()) ?> - <?= dateFromDatabase($event->getEndDate()) ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-clock text-blue-500"></i><span class="font-semibold">Horario:</span><span><?= substr($event->getStartTime(), 0, 5) ?> - <?= substr($event->getEndTime(), 0, 5) ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-table text-blue-500"></i><span class="font-semibold">Mesas:</span><span><?= $event->getAvailableTables() ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-stopwatch text-blue-500"></i><span class="font-semibold">Duración reunión:</span><span><?= $event->getMeetingDuration() ?> min</span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-building text-blue-500"></i><span class="font-semibold">Empresa Organizadora:</span><span><?= !empty($event->getCompanyName()) ? htmlspecialchars($event->getCompanyName()) : '<em>No especificada</em>' ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-user text-blue-500"></i><span class="font-semibold">Contacto:</span><span><?= !empty($event->getContactName()) ? htmlspecialchars($event->getContactName()) : '<em>No especificado</em>' ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-envelope text-blue-500"></i><span class="font-semibold">Email:</span><span><?= !empty($event->getContactEmail()) ? htmlspecialchars($event->getContactEmail()) : '<em>No especificado</em>' ?></span></div>
                </div>
                <div class="flex items-center gap-2 mt-4">
                    <span class="event-view-badge <?= $event->isActive() ? 'active' : 'inactive' ?>">
                        <?= $event->isActive() ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
            </div>
            <?php if ($hasCategories): ?>
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold flex items-center gap-2"><i class="fas fa-tags"></i> Categorías del Evento</h2>
                    <a href="<?= BASE_URL ?>/events/categories/<?= $event->getId() ?>" class="btn btn-sm btn-action-blue"><i class="fas fa-list"></i> Ver más</a>
                </div>
                <div>
                    <div class="border-b border-gray-200 mb-4 overflow-x-auto">
                        <nav class="-mb-px flex space-x-4" id="category-tabs" role="tablist">
                            <?php foreach ($categoriesWithSubcategories as $i => $cat): ?>
                                <button class="tab-btn px-4 py-2 text-sm font-medium text-gray-700 border-b-2 border-transparent focus:outline-none focus:text-blue-600 focus:border-blue-600 <?= $i === 0 ? 'active border-blue-600 text-blue-600' : '' ?>" id="tab-<?= $i ?>" data-tab="tab-panel-<?= $i ?>" type="button" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                    <?= htmlspecialchars($cat['category']['name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                    <div id="tab-content">
                        <?php foreach ($categoriesWithSubcategories as $i => $cat): ?>
                            <div class="tab-panel <?= $i === 0 ? '' : 'hidden' ?> bg-white rounded-lg shadow p-4 border border-gray-200" id="tab-panel-<?= $i ?>" role="tabpanel">
                                <h3 class="font-semibold text-blue-700 mb-2">Subcategorías de "<?= htmlspecialchars($cat['category']['name']) ?>"</h3>
                                <?php if (empty($cat['subcategories'])): ?>
                                    <div class="text-gray-500 italic">No hay subcategorías para esta categoría.</div>
                                <?php else: ?>
                                    <ul class="list-disc pl-6">
                                        <?php foreach ($cat['subcategories'] as $sub): ?>
                                            <li><?= htmlspecialchars($sub['name']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <!-- Menú rápido y estadísticas (derecha) -->
        <div class="event-view-card p-6 flex flex-col gap-2">
            <h2 class="event-view-title mb-4 flex items-center gap-2"><i class="fas fa-chart-bar"></i> Estadísticas</h2>
            <div class="flex flex-col gap-2 event-view-section">
                <?php $buyers = isset($buyers) && is_array($buyers) ? $buyers : []; ?>
                <?php $suppliers = isset($suppliers) && is_array($suppliers) ? $suppliers : []; ?>
                <div class="flex items-center gap-2"><i class="fas fa-users text-blue-500"></i><span class="font-semibold">Participantes:</span><span><?= count($buyers) + count($suppliers) ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-handshake text-blue-500"></i><span class="font-semibold">Matches:</span><span><?= $matchCount ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-calendar-check text-blue-500"></i><span class="font-semibold">Citas:</span><span><?= $scheduleCount ?></span></div>
            </div>
            <ul class="event-actions-list mt-4 mb-8">
                <li class="mb-2" style="margin-bottom:2px;">
                <a href="<?= BASE_URL ?>/events/registration_details?event_id=<?= (int)$event->getId() ?>&company_id=<?= (isset($company) && isset($company['company_id'])) ? (int)$company['company_id'] : '' ?>" class="btn btn-info btn-sm">
                Ver Registro Completo
                </a>
                </li>
                <li class="mb-2" style="margin-bottom:2px;">
                    <a href="<?= BASE_URL ?>/events/participants/<?= $event->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-users"></i> Ver Participantes</a>
                </li>
                <li class="mb-2" style="margin-bottom:2px;">
                    <a href="<?= BASE_URL ?>/events/matches/<?= $event->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-handshake"></i> Ver Matches</a>
                </li>
                <li class="mb-2" style="margin-bottom:2px;">
                    <a href="<?= BASE_URL ?>/events/schedules/<?= $event->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-calendar-alt"></i> Ver Citas</a>
                </li>
                <li style="margin-bottom:2px;">
                    <a href="<?= BASE_URL ?>/events/time_slots/<?= $event->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-clock"></i> Ver Horarios</a>
                </li>
                <li class="mb-2" style="margin-bottom:2px;">
                    <a href="<?= BASE_URL ?>/events/companies/<?= $event->getId() ?>" class="btn btn-sm btn-action-blue w-full"><i class="fas fa-building"></i> Ver Empresas</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="event-view-card">
            <div class="card-header flex items-center justify-between">
                <h2 class="event-view-title flex items-center gap-2"><i class="fas fa-coffee"></i> Descansos Programados</h2>
                <a href="<?= BASE_URL ?>/events/breaks/<?= $event->getId() ?>" class="btn btn-sm btn-primary"><i class="fas fa-cog"></i> Gestionar</a>
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
                <form action="<?= BASE_URL ?>/events/toggle-active/<?= $event->getId() ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?= $event->isActive() ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="custom-control-label" for="is_active">
                                <?= $event->isActive() ? 'Evento Activo' : 'Evento Inactivo' ?>
                            </label>
                        </div>
                    </div>
                </form>
                <div class="mt-3">
                    <label>Duración del Evento</label>
                    <p>
                        <?php $startDate = new DateTime($event->getStartDate()); $endDate = new DateTime($event->getEndDate()); $interval = $startDate->diff($endDate); echo $interval->days + 1 . ' días'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(VIEW_DIR . '/components/modals/import_categories.php'); ?>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
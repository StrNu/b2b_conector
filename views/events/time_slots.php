<?php include(VIEW_DIR . '/shared/header.php'); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components/cards.css">
<div class="content">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Horarios y Capacidad del Evento</h1>
        <a href="<?= BASE_URL ?>/events/view/<?= (int)$eventModel->getId() ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Evento
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="flex flex-wrap gap-6">
        <!-- Card Capacidad del Evento -->
        <div class="flex-1 min-w-[340px] max-w-lg">
            <div class="card bg-white p-6 rounded shadow mb-6">
                <div class="text-xl font-semibold mb-4 text-blue-700">Capacidad del Evento</div>
                <div class="flex flex-col gap-3 mb-4">
                    <div>
                        <div class="text-gray-500 text-xs mb-1">Duración del evento</div>
                        <div class="text-lg font-bold text-gray-800"><?= htmlspecialchars($eventDurationDays ?? '-') ?> días</div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-1">No. de mesas</div>
                        <div class="text-lg font-bold text-gray-800"><?= htmlspecialchars($availableTables ?? '-') ?></div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-1">Horarios por día</div>
                        <div class="text-lg font-bold text-gray-800"><?= htmlspecialchars($slotsPerDay ?? '-') ?></div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-1">Reuniones por día</div>
                        <div class="text-lg font-bold text-gray-800">
                            <?php $reunionesPorDia = ($slotsPerDay ?? 0) * ($availableTables ?? 0); echo $reunionesPorDia; ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-1">Capacidad total</div>
                        <div class="text-lg font-bold text-blue-700">
                            <?php $capacidadTotal = $reunionesPorDia * ($eventDurationDays ?? 0); echo $capacidadTotal; ?>
                        </div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="mb-2 font-semibold text-gray-700">Descansos Programados</div>
                <div class="overflow-x-auto">
                    <table class="table-auto w-full bg-white rounded border border-gray-200 mb-2">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2">Hora de inicio</th>
                                <th class="px-3 py-2">Hora de fin</th>
                                <th class="px-3 py-2">Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($breaks)): ?>
                                <?php foreach ($breaks as $break): ?>
                                    <tr>
                                        <td class="px-3 py-2 text-center"><?= htmlspecialchars($break['start_time']) ?></td>
                                        <td class="px-3 py-2 text-center"><?= htmlspecialchars($break['end_time']) ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php
                                            // Si es array, calculamos duración aquí
                                            if (is_array($break)) {
                                                $start = strtotime($break['start_time']);
                                                $end = strtotime($break['end_time']);
                                                $duration = ($end && $start) ? floor(($end - $start) / 60) : 0;
                                                echo $duration . ' minutos';
                                            } elseif (is_object($break) && method_exists($break, 'getDuration')) {
                                                echo $break->getDuration() . ' minutos';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-gray-400">No hay descansos programados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Card Desglose de Horarios -->
        <div class="flex-1 min-w-[340px] max-w-3xl">
            <div class="card bg-white p-4 rounded shadow">
                <h2 class="text-xl font-semibold mb-4 text-blue-700">Desglose de Horarios</h2>
                <?php
                // Obtener rango de días del evento
                $days = array_keys($slotsByDate);
                $activeDay = $_GET['day'] ?? ($days[0] ?? null);
                ?>
                <?php if (!empty($days)): ?>
                    <div class="tabs mb-4">
                        <ul class="flex border-b">
                            <?php foreach ($days as $day): ?>
                                <li class="mr-2">
                                    <a href="?day=<?= $day ?>" class="inline-block px-4 py-2 <?= $activeDay === $day ? 'border-b-2 border-blue-600 font-bold text-blue-700' : 'text-gray-600' ?>">
                                        <?= date('d/m/Y', strtotime($day)) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full bg-white rounded shadow border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2">Hora Inicio</th>
                                    <th class="px-3 py-2">Hora Fin</th>
                                    <th class="px-3 py-2">Mesa</th>
                                    <th class="px-3 py-2">Disponibilidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($slotsByDate[$activeDay])): ?>
                                    <?php foreach ($slotsByDate[$activeDay] as $slot): ?>
                                        <tr>
                                            <td class="px-3 py-2 text-center"><?= htmlspecialchars(substr($slot['start_time'] ?? $slot['start_datetime'], 11, 5)) ?></td>
                                            <td class="px-3 py-2 text-center"><?= htmlspecialchars(substr($slot['end_time'] ?? $slot['end_datetime'], 11, 5)) ?></td>
                                            <td class="px-3 py-2 text-center"><?= htmlspecialchars($slot['table_number'] ?? '-') ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <?php $disponible = empty($slot['match_id']) || (isset($slot['status']) && $slot['status'] !== 'scheduled'); ?>
                                                <?php if ($disponible): ?>
                                                    <span class="inline-block rounded px-2 py-1 text-xs font-semibold bg-green-100 text-green-800">Disponible</span>
                                                <?php else: ?>
                                                    <span class="inline-block rounded px-2 py-1 text-xs font-semibold bg-red-100 text-red-800">Ocupado</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-gray-400">No hay slots para este día.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-8">No hay slots de tiempo generados para este evento.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
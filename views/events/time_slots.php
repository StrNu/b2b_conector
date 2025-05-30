<?php include(VIEW_DIR . '/shared/header.php'); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components/cards.css">
<style>
/* Compactar tabla de horarios */
.compact-table th, .compact-table td {
    font-size: 0.85rem;
    padding: 0.25rem 0.4rem;
}
.compact-table th {
    font-weight: 600;
}
/* Compactar tabla de descansos */
.breaks-table th, .breaks-table td {
    font-size: 0.8rem;
    padding: 0.18rem 0.3rem;
}
/* Reducir margen inferior de la card de descansos */
.card-breaks { margin-bottom: 1rem !important; }
@media (min-width: 1024px) {
  /* Dar más espacio a la tabla principal en desktop */
  .main-flex {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
  }
  .card-breaks { flex: 0 0 320px; max-width: 340px; }
  .card-slots { flex: 1 1 0%; min-width: 340px; max-width: 100%; }
}
</style>
<div class="content">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Horarios y Capacidad del Evento</h1>
        <a href="<?= BASE_URL ?>/events/view/<?= (int)$eventModel->getId() ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Evento
        </a>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="main-flex">
        <!-- Card Capacidad del Evento y Descansos -->
        <div class="card-breaks">
            <div class="card bg-white p-4 rounded shadow mb-2">
                <div class="text-base font-semibold mb-2 text-blue-700">Capacidad del Evento</div>
                <div class="flex flex-col gap-2 mb-2">
                    <div>
                        <div class="text-gray-500 text-xs mb-0.5">Duración del evento</div>
                        <div class="text-base font-bold text-gray-800"><?= htmlspecialchars($eventDurationDays ?? '-') ?> días</div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-0.5">No. de mesas</div>
                        <div class="text-base font-bold text-gray-800"><?= htmlspecialchars($availableTables ?? '-') ?></div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-0.5">Horarios por día</div>
                        <div class="text-base font-bold text-gray-800"><?= htmlspecialchars($slotsPerDay ?? '-') ?></div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-0.5">Reuniones por día</div>
                        <div class="text-base font-bold text-gray-800">
                            <?php $reunionesPorDia = ($slotsPerDay ?? 0) * ($availableTables ?? 0); echo $reunionesPorDia; ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs mb-0.5">Capacidad total</div>
                        <div class="text-base font-bold text-blue-700">
                            <?php $capacidadTotal = $reunionesPorDia * ($eventDurationDays ?? 0); echo $capacidadTotal; ?>
                        </div>
                    </div>
                </div>
                <hr class="my-2">
                <div class="mb-1 font-semibold text-gray-700" style="font-size:0.95em">Descansos Programados</div>
                <div class="overflow-x-auto">
                    <table class="table-auto w-full bg-white rounded border border-gray-200 mb-1 breaks-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th>Hora de inicio</th>
                                <th>Hora de fin</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($breaks)): ?>
                                <?php foreach ($breaks as $break): ?>
                                    <tr>
                                        <td class="text-center"><?= htmlspecialchars($break['start_time']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($break['end_time']) ?></td>
                                        <td class="text-center">
                                            <?php
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
        <div class="card-slots">
            <div class="card bg-white p-3 rounded shadow">
                <h2 class="text-lg font-semibold mb-3 text-blue-700">Desglose de Horarios</h2>
                <?php
                $eventDurationDays = $eventDurationDays ?? ($eventDurationDays ?? 0);
                $slotsPerDay = $slotsPerDay ?? ($slotsPerDay ?? 0);
                $availableTables = $availableTables ?? ($availableTables ?? 0);
                $breaks = $breaks ?? ($breaks ?? []);
                $slotsByDate = $slotsByDate ?? ($slotsByDate ?? []);
                $appointments = $appointments ?? ($appointments ?? []);
                $ocupados = [];
                foreach ($appointments as $appt) {
                    $date = substr($appt['start_datetime'], 0, 10);
                    $start = substr($appt['start_datetime'], 11, 5);
                    $table = $appt['table_number'];
                    $ocupados[$date][$start][$table] = $appt;
                }
                $days = array_keys($slotsByDate);
                $activeDay = $_GET['day'] ?? ($days[0] ?? null);
                // --- Obtener lista de mesas (usando el número de mesas del evento, no solo los slots generados) ---
                $mesas = range(1, (int)$availableTables);
                // --- Agrupar slots por hora ---
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
                    <div class="tabs mb-3">
                        <ul class="flex border-b">
                            <?php foreach ($days as $day): ?>
                                <li class="mr-2">
                                    <a href="?day=<?= $day ?>" class="inline-block px-3 py-1 <?= $activeDay === $day ? 'border-b-2 border-blue-600 font-bold text-blue-700' : 'text-gray-600' ?>" style="font-size:0.95em">
                                        <?= date('d/m/Y', strtotime($day)) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full bg-white rounded shadow border border-gray-200 compact-table" style="min-width:600px">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th>Hora</th>
                                    <?php foreach ($mesas as $mesa): ?>
                                        <th>Mesa <?= htmlspecialchars($mesa) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horas as $hora): ?>
                                    <tr>
                                        <td class="text-center font-semibold" style="white-space:nowrap;"> <?= htmlspecialchars($hora) ?> </td>
                                        <?php foreach ($mesas as $mesa): ?>
                                            <?php
                                            // Buscar slot para esta hora y mesa
                                            $start = explode('-', $hora)[0];
                                            $slot = null;
                                            foreach ($slotsByDate[$activeDay] as $s) {
                                                if (substr($s['start_datetime'], 11, 5) === $start && $s['table_number'] == $mesa) {
                                                    $slot = $s;
                                                    break;
                                                }
                                            }
                                            $appt = $slot ? ($ocupados[$activeDay][$start][$mesa] ?? null) : null;
                                            ?>
                                            <td class="text-center" style="min-width:90px;">
                                                <?php if ($appt): ?>
                                                    <span class="inline-block rounded px-1.5 py-0.5 text-xs font-semibold bg-red-100 text-red-800" style="font-size:0.8em; cursor:pointer;" title="<?= htmlspecialchars(($appt['buyer_name'] ?? '').' / '.($appt['supplier_name'] ?? '')) ?>">
                                                        Ocupado<br><?= htmlspecialchars($appt['buyer_name'] ?? '') ?> / <?= htmlspecialchars($appt['supplier_name'] ?? '') ?>
                                                    </span>
                                                <?php elseif ($slot): ?>
                                                    <span class="inline-block rounded px-1.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800" style="font-size:0.8em">Disponible</span>
                                                <?php else: ?>
                                                    <span class="inline-block rounded px-1.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-400" style="font-size:0.8em">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($horas)): ?>
                                    <tr><td colspan="<?= count($mesas)+1 ?>" class="text-center text-gray-400">No hay slots para este día.</td></tr>
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
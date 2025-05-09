<?php include(VIEW_DIR . '/shared/header.php'); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Horarios del Evento</h1>
        <form method="POST" action="" class="inline">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <button type="submit" name="regenerate_schedules" class="btn btn-warning">
                <i class="fas fa-sync-alt"></i> Recalcular Horarios
            </button>
        </form>
    </div>
    <?php displayFlashMessages(); ?>
    <?php
    // Suponemos que $eventModel, $schedulesByDay, $days, $tables, $matches están disponibles
    // $days = array de fechas (Y-m-d) del evento
    // $schedulesByDay = [ 'Y-m-d' => [schedules...] ]
    // $tables = array de números de mesa
    // $matches = [match_id => [datos del match]]
    $activeDay = $_GET['day'] ?? ($days[0] ?? null);
    ?>
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
    <div class="card bg-white p-4 rounded shadow">
        <?php if (!empty($schedulesByDay[$activeDay])): ?>
            <div class="overflow-x-auto">
                <table class="table-auto w-full bg-white rounded shadow border border-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2">Hora Inicio</th>
                            <th class="px-3 py-2">Hora Fin</th>
                            <th class="px-3 py-2">Mesa</th>
                            <th class="px-3 py-2">Comprador</th>
                            <th class="px-3 py-2">Proveedor</th>
                            <th class="px-3 py-2">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedulesByDay[$activeDay] as $schedule): ?>
                            <?php $match = $matches[$schedule['match_id']] ?? null; ?>
                            <tr>
                                <td class="px-3 py-2"><?= date('H:i', strtotime($schedule['start_datetime'])) ?></td>
                                <td class="px-3 py-2"><?= date('H:i', strtotime($schedule['end_datetime'])) ?></td>
                                <td class="px-3 py-2 text-center"><?= htmlspecialchars($schedule['table_number']) ?></td>
                                <td class="px-3 py-2">
                                    <?= $match ? htmlspecialchars($match['buyer_name']) : '<span class="text-gray-400">-</span>' ?>
                                </td>
                                <td class="px-3 py-2">
                                    <?= $match ? htmlspecialchars($match['supplier_name']) : '<span class="text-gray-400">-</span>' ?>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-block rounded px-2 py-1 text-xs font-semibold <?= $schedule['status'] === 'scheduled' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>">
                                        <?= htmlspecialchars($schedule['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-gray-500 py-8">No hay horarios programados para este día.</div>
        <?php endif; ?>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
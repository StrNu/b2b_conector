<?php
session_start();
if (isset($_SESSION['event_id'])) {
    $eventId = $_SESSION['event_id'];
}
include(VIEW_DIR . '/shared/header.php'); ?>
<?php
// Debug temporal para ver el contenido de $customMatches
/*if (isset($customMatches)) {
    echo '<pre style="background:#fffbe6;border:1px solid #e6c200;padding:10px;">';
    echo '<b>DEBUG $customMatches:</b> ';
    var_dump($customMatches);
    echo '</pre>';
}*/
?>
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Matches del Evento</h1>
        <div class="flex items-center">
            <form method="post" action="<?= BASE_URL ?>/matches/generateAll" id="generate-matches-form" class="ml-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                <button type="submit" class="bg-indigo-600 text-white text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-indigo-700 transition flex items-center gap-2" id="generate-matches-btn">
                    <span id="generate-matches-btn-text">Generar matches</span>
                    <svg id="generate-matches-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                </button>
            </form>
            <form method="post" action="<?= BASE_URL ?>/appointments/scheduleAll" id="schedule-all-form" class="ml-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                <button type="submit" class="bg-green-600 text-white text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-green-700 transition flex items-center gap-2">
                    <span>Programar todo</span>
                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
            </form>
            <a href="<?= BASE_URL ?>/events/view/<?= isset($eventId) && is_numeric($eventId) && $eventId > 0 ? $eventId : (isset($_GET['event_id']) ? (int)$_GET['event_id'] : '') ?>" class="ml-2 bg-gray-200 text-gray-700 text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-gray-300 transition flex items-center gap-2">Regresar al evento</a>
        </div>
    </div>
    <script>
    document.getElementById('generate-matches-form').addEventListener('submit', function() {
        document.getElementById('generate-matches-btn-text').textContent = 'Generando...';
        document.getElementById('generate-matches-spinner').classList.remove('hidden');
        document.getElementById('generate-matches-btn').setAttribute('disabled', 'disabled');
    });
    </script>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="bg-white p-4 rounded-xl shadow mb-6">
        <div class="grid grid-cols-12 text-xs font-semibold text-gray-500 px-2 mb-2">
            <div class="col-span-4">Empresas</div>
            <div class="col-span-3">Categorías</div>
            <div class="col-span-3">Asistencia</div>
            <div class="col-span-2 text-right">Acciones</div>
        </div>
        <?php if (!empty($customMatches)): ?>
            <div class="flex flex-col gap-4">
                <?php foreach ($customMatches as $match): ?>
                    <div class="grid grid-cols-12 gap-2 bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm items-center">
                        <!-- Empresas -->
                        <div class="col-span-12 md:col-span-4 min-w-0">
                            <div class="font-semibold text-blue-900 text-base mb-1">
                                <?= htmlspecialchars($match['buyer_name']) ?> - <?= htmlspecialchars($match['supplier_name']) ?>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600 mb-1">
                                <span class="flex items-center gap-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                                    <?php
                                        $cats = isset($match['matched_categories']) ? json_decode($match['matched_categories'], true) : [];
                                        $num_coincidencias = count($cats);
                                        $total_reqs = isset($match['total_buyer_requirements']) ? (int)$match['total_buyer_requirements'] : null;
                                        $percent = 0;
                                        if ($total_reqs === null) {
                                            echo "Match: $num_coincidencias coincidencias";
                                        } else if ($total_reqs > 0) {
                                            $percent = round(($num_coincidencias / $total_reqs) * 100);
                                            echo "Match: $percent%";
                                        } else {
                                            echo "Match: 0%";
                                        }
                                    ?>
                                    <?php if ($total_reqs !== null && $total_reqs > 0): ?>
                                        <span style="display:inline-block;width:48px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;vertical-align:middle;margin-left:6px;">
                                            <span style="display:block;height:100%;background:#2563eb;width:<?= $percent ?>%;transition:width 0.3s;"></span>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <!-- Categorías -->
                        <div class="col-span-12 md:col-span-3 min-w-[180px]">
                            <div class="text-xs text-gray-500 font-medium mb-1">Categorías</div>
                            <?php $cats = isset($match['matched_categories']) ? json_decode($match['matched_categories'], true) : []; ?>
                            <?php if ($cats): ?>
                                <?php foreach ($cats as $cat): ?>
                                    <span class="inline-block bg-blue-50 border border-blue-200 text-blue-700 rounded-full px-3 py-1 text-xs mr-1 mb-1 font-semibold">
                                        <?= htmlspecialchars($cat['subcategory_name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Sin categorías</span>
                            <?php endif; ?>
                        </div>
                        <!-- Asistencia -->
                        <div class="col-span-12 md:col-span-3 min-w-[180px]">
                            <div class="text-xs text-gray-500 font-medium mb-1">Asistencia</div>
                            <?php 
                                $buyer_days = isset($match['buyer_days']) ? $match['buyer_days'] : [];
                                $supplier_days = isset($match['supplier_days']) ? $match['supplier_days'] : [];
                                $common_days = array_intersect($buyer_days, $supplier_days);
                                // Debug log visual
                                echo '<div style="font-size:10px;color:#b91c1c;background:#fffbe6;border:1px solid #e6c200;padding:2px 6px;margin-bottom:2px;">';
                                echo '<b>buyer_days:</b> ' . htmlspecialchars(json_encode($buyer_days)) . ' | ';
                                echo '<b>supplier_days:</b> ' . htmlspecialchars(json_encode($supplier_days)) . ' | ';
                                echo '<b>common_days:</b> ' . htmlspecialchars(json_encode($common_days));
                                echo '</div>';
                            ?>
                            <?php if ($common_days && count($common_days)): ?>
                                <span class="inline-block bg-blue-200 text-blue-900 rounded-full px-3 py-1 text-xs font-semibold">
                                    <?= implode(', ', $common_days) ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-block bg-gray-100 text-gray-500 rounded-full px-3 py-1 text-xs font-semibold">No hay días comunes</span>
                            <?php endif; ?>
                        </div>
                        <!-- Acciones -->
                        <div class="col-span-12 md:col-span-2 flex items-center justify-end gap-2 min-w-[140px] mt-2 md:mt-0">
                            <?php
                            $isAccepted = ($match['status'] === 'accepted');
                            $hasAppointment = false;
                            if (function_exists('appointmentExistsForMatch')) {
                                $hasAppointment = appointmentExistsForMatch($match['match_id']);
                            } elseif (isset($match['has_appointment'])) {
                                $hasAppointment = $match['has_appointment'];
                            }
                            $disableActions = !empty($match['programed']);
                            ?>
                            <form method="post" action="<?= BASE_URL ?>/appointments/schedule" style="display:inline;">
                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($match['event_id']) ?>">
                                <input type="hidden" name="match_id" value="<?= htmlspecialchars($match['match_id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit"
                                    class="text-xs px-4 py-1 rounded-full font-semibold transition flex items-center gap-1
                                        <?= $disableActions ? 'bg-gray-200 text-gray-400 border border-gray-300 cursor-not-allowed' : 'bg-white border border-green-600 text-green-700 hover:bg-green-600 hover:text-white' ?>"
                                    <?= $disableActions ? 'disabled' : '' ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    Programar
                                </button>
                            </form>
                            <form method="post" action="<?= BASE_URL ?>/matches/delete/<?= htmlspecialchars($match['match_id']) ?>" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este match?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit"
                                    class="text-xs px-4 py-1 rounded-full font-semibold transition
                                        <?= $disableActions ? 'bg-gray-200 text-gray-400 border border-gray-300 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700' ?>"
                                    <?= $disableActions ? 'disabled' : '' ?>>Eliminar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-gray-500">No hay matches para mostrar.</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($buyersWithoutMatches)): ?>
    <div class="bg-white p-4 rounded-xl shadow mb-6 mt-8">
        <h2 class="text-lg font-bold text-blue-900 mb-2">Compradores sin matches</h2>
        <div class="table-responsive">
            <table class="responsive-table">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 text-left">Empresa</th>
                        <th class="p-2 text-left">Requerimientos</th>
                        <th class="p-2 text-left">Días de Asistencia</th>
                        <th class="p-2 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($buyersWithoutMatches as $item): ?>
                    <tr class="border-b">
                        <td class="p-2 font-semibold text-blue-900"><?= htmlspecialchars($item['company']['company_name']) ?></td>
                        <td class="p-2">
                            <?php if (!empty($item['requirements'])): ?>
                                <?php foreach ($item['requirements'] as $req): ?>
                                    <div class="mb-1">
                                        <span class="inline-block bg-blue-50 border border-blue-200 text-blue-700 rounded-full px-2 py-0.5 text-xs font-semibold mr-1">
                                            <?= htmlspecialchars($req['subcategory_name']) ?>
                                        </span>
                                        <span class="text-gray-500 text-xs">(<?= htmlspecialchars($req['category_name']) ?>)</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Sin requerimientos</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2">
                            <?php if (!empty($item['attendance_days'])): ?>
                                <span class="inline-block bg-blue-200 text-blue-900 rounded-full px-2 py-0.5 text-xs font-semibold">
                                    <?= implode(', ', $item['attendance_days']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Sin días</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2">
                            <form method="post" action="<?= BASE_URL ?>/matches/generateForBuyer" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                                <?php if (!empty($item['company']['company_id'])): ?>
                                    <input type="hidden" name="buyer_id" value="<?= htmlspecialchars($item['company']['company_id']) ?>">
                                    <button type="submit" class="bg-blue-600 text-white text-xs px-3 py-1 rounded-full font-semibold hover:bg-blue-700 transition">Generar matches</button>
                                <?php else: ?>
                                    <span class="text-red-500 text-xs">ID no disponible</span>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($suppliersWithoutMatches)): ?>
    <div class="bg-white p-4 rounded-xl shadow mb-6 mt-8">
        <h2 class="text-lg font-bold text-green-900 mb-2">Proveedores sin matches</h2>
        <div class="table-responsive">
            <table class="responsive-table">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 text-left">Empresa</th>
                        <th class="p-2 text-left">Ofertas</th>
                        <th class="p-2 text-left">Días de Asistencia</th>
                        <th class="p-2 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliersWithoutMatches as $item): ?>
                    <tr class="border-b">
                        <td class="p-2 font-semibold text-green-900"><?= htmlspecialchars($item['company']['company_name']) ?></td>
                        <td class="p-2">
                            <?php if (!empty($item['offers'])): ?>
                                <?php foreach ($item['offers'] as $offer): ?>
                                    <div class="mb-1">
                                        <span class="inline-block bg-green-50 border border-green-200 text-green-700 rounded-full px-2 py-0.5 text-xs font-semibold mr-1">
                                            <?= htmlspecialchars($offer['subcategory_name']) ?>
                                        </span>
                                        <span class="text-gray-500 text-xs">(<?= htmlspecialchars($offer['category_name']) ?>)</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Sin ofertas</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2">
                            <?php if (!empty($item['attendance_days'])): ?>
                                <span class="inline-block bg-green-200 text-green-900 rounded-full px-2 py-0.5 text-xs font-semibold">
                                    <?= implode(', ', $item['attendance_days']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Sin días</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2">
                            <form method="post" action="<?= BASE_URL ?>/matches/generateForSupplier" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                                <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($item['company']['company_id']) ?>">
                                <button type="submit" class="bg-green-600 text-white text-xs px-3 py-1 rounded-full font-semibold hover:bg-green-700 transition">Generar matches</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

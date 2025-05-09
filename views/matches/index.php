<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Matches Potenciales</h1>
        <div class="flex gap-2">
            <form action="<?= BASE_URL ?>/matches/saveAll" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Guardar Todos</button>
            </form>
            <form action="<?= BASE_URL ?>/matches/autoScheduleAll" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-calendar-check"></i> Auto-programar Todo</button>
            </form>
        </div>
    </div>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="card bg-white p-4 rounded shadow mb-6">
        <table class="table-auto w-full bg-white rounded shadow border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">Comprador</th>
                    <th class="px-3 py-2">Proveedor</th>
                    <th class="px-3 py-2">Categorías Coincidentes</th>
                    <th class="px-3 py-2">Días Coincidentes</th>
                    <th class="px-3 py-2">Strength Match</th>
                    <th class="px-3 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($matches)): ?>
                    <?php foreach ($matches as $match): ?>
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                <?= htmlspecialchars($match['buyer_name']) ?>
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                <?= htmlspecialchars($match['supplier_name']) ?>
                            </td>
                            <td class="px-3 py-2">
                                <?php foreach ($match['matched_categories'] as $cat): ?>
                                    <span class="inline-block bg-gray-200 text-gray-700 rounded px-2 py-1 text-xs mr-1 mb-1">
                                        <?= htmlspecialchars($cat['category_name']) ?> / <?= htmlspecialchars($cat['subcategory_name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td class="px-3 py-2">
                                <?php if (!empty($match['common_days'])): ?>
                                    <?php foreach ($match['common_days'] as $day): ?>
                                        <span class="inline-block bg-gray-100 text-gray-700 rounded px-2 py-1 text-xs mr-1 mb-1">
                                            <?= htmlspecialchars($day) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Sin coincidencia</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2">
                                <div style="background:#eee;border-radius:4px;overflow:hidden;width:100px;">
                                    <div style="background:#4caf50;width:<?= (float)$match['match_strength'] ?>%;color:#fff;text-align:center;font-size:12px;height:18px;white-space:nowrap;">
                                        <?= $match['match_strength'] ?>%
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <form action="<?= BASE_URL ?>/matches/save/<?= $match['buyer_id'] ?>/<?= $match['supplier_id'] ?>/<?= $match['event_id'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn btn-primary btn-xs"><i class="fas fa-save"></i> Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-gray-500">No hay matches potenciales para mostrar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

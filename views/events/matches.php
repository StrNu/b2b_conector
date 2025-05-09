<?php include(VIEW_DIR . '/shared/header.php'); ?>

<?php
// Debug temporal para ver el contenido de $customMatches
if (isset($customMatches)) {
    echo '<pre style="background:#fffbe6;border:1px solid #e6c200;padding:10px;">';
    echo '<b>DEBUG $customMatches:</b> ';
    var_dump($customMatches);
    echo '</pre>';
}
?>

<?php if (!empty($potentialMatchesList)): ?>
    <div class="mb-8">
        <h2 class="text-xl font-bold text-blue-800 mb-3">Matches Potenciales</h2>
        <div class="card bg-white p-4 rounded shadow mb-6">
            <table class="table-auto w-full bg-white rounded shadow border border-blue-200">
                <thead class="bg-blue-100">
                    <tr>
                        <th class="px-3 py-2">Comprador</th>
                        <th class="px-3 py-2">Proveedor</th>
                        <th class="px-3 py-2">Match %</th>
                        <th class="px-3 py-2">Categorías Coincidentes</th>
                        <th class="px-3 py-2">Días Comunes</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($potentialMatchesList as $pm): ?>
                        <tr>
                            <td class="px-3 py-2 font-semibold text-blue-700">
                                <?= htmlspecialchars($companyNames[$pm['buyer_id']] ?? $pm['buyer_id']) ?>
                            </td>
                            <td class="px-3 py-2 font-semibold text-blue-700">
                                <?= htmlspecialchars($companyNames[$pm['supplier_id']] ?? $pm['supplier_id']) ?>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-block bg-blue-100 text-blue-800 rounded px-2 py-1 text-xs font-semibold">
                                    <?= round($pm['match_strength']) ?>%
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <?php $cats = json_decode($pm['matched_categories'], true); ?>
                                <?php if ($cats): ?>
                                    <?php foreach ($cats as $cat): ?>
                                        <span class="inline-block bg-blue-50 border border-blue-200 text-blue-700 rounded px-2 py-1 text-xs mr-1 mb-1">
                                            <?= htmlspecialchars($cat['category_name']) ?> / <?= htmlspecialchars($cat['subcategory_name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">Sin categorías</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2">
                                <?php $days = json_decode($pm['matched_days'], true); ?>
                                <?php if ($days && count($days)): ?>
                                    <span class="text-xs text-gray-700">
                                        <?= implode(', ', $days) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">No hay días comunes</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded transition">Guardar</button>
                                <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded transition">Programar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Matches del Evento</h1>
    </div>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="card bg-white p-4 rounded shadow mb-6">
        <table class="table-auto w-full bg-white rounded shadow border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">ID</th>
                    <th class="px-3 py-2">Comprador</th>
                    <th class="px-3 py-2">Proveedor</th>
                    <th class="px-3 py-2">Strength Match</th>
                    <th class="px-3 py-2">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customMatches)): ?>
                    <?php foreach ($customMatches as $match): ?>
                        <tr>
                            <td class="px-3 py-2 text-gray-700">#<?= htmlspecialchars($match['match_id']) ?></td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                <?= htmlspecialchars($match['buyer_name']) ?>
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                <?= htmlspecialchars($match['supplier_name']) ?>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-block bg-gray-300 text-gray-800 rounded px-2 py-1 text-xs font-semibold">
                                    <?= htmlspecialchars($match['match_strength']) ?>%
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-block bg-gray-200 text-gray-700 rounded px-2 py-1 text-xs">
                                    <?= htmlspecialchars($match['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-gray-500">No hay matches para mostrar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

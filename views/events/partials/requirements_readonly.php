<?php
/**
 * Partial: requirements_readonly.php
 * Muestra los requerimientos en modo solo lectura, agrupados por categoría y subcategoría,
 * con la misma estructura visual que el registro, pero deshabilitado.
 * Variables requeridas:
 * - $categories
 * - $subcategories
 * - $requirements (array indexado por event_subcategory_id)
 */
?>
<div class="mb-2">
    <div class="flex flex-wrap gap-1 mb-2">
        <?php foreach ($categories as $i => $cat): ?>
            <button type="button" class="tab-btn btn btn-light px-2 py-1 text-xs md:text-sm <?= $i === 0 ? 'active' : '' ?>" data-tab="cat-<?= (int)$cat['event_category_id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php foreach ($categories as $i => $cat): ?>
        <div class="tab-panel <?= $i === 0 ? '' : 'hidden' ?>" id="cat-<?= (int)$cat['event_category_id'] ?>">
            <?php if (!empty($subcategories[$cat['event_category_id']])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs md:text-sm border">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Requerimiento</th>
                                <th class="p-2 text-left">Presupuesto en dólares</th>
                                <th class="p-2 text-left">Cantidad</th>
                                <th class="p-2 text-left">Unidad de medida</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subcategories[$cat['event_category_id']] as $sub):
                            $req = $requirements[$sub['event_subcategory_id']] ?? null;
                        ?>
                            <tr class="border-b">
                                <td class="p-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" disabled <?= $req ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($sub['name']) ?></span>
                                    </label>
                                </td>
                                <td class="p-2">
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400">$</span>
                                        <input type="number" step="0.01" min="0" class="form-control w-24" value="<?= $req ? htmlspecialchars($req['budget_usd']) : '' ?>" readonly>
                                    </div>
                                </td>
                                <td class="p-2">
                                    <input type="number" min="1" class="form-control w-16" value="<?= $req ? htmlspecialchars($req['quantity']) : '' ?>" readonly>
                                </td>
                                <td class="p-2">
                                    <input type="text" class="form-control" value="<?= $req ? htmlspecialchars($req['unit_of_measurement']) : '' ?>" readonly>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <span class="text-xs text-gray-400">No hay subcategorías para esta categoría.</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<script>
// Tabs solo visuales (no interactivos en modo readonly)
</script>

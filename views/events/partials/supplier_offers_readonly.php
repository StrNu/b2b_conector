<?php
/**
 * Partial: supplier_offers_readonly.php
 * Muestra las ofertas del proveedor en modo solo lectura, agrupadas por categoría y subcategoría.
 * Variables requeridas:
 * - $categories
 * - $subcategories
 * - $offers (array de ofertas, cada una con event_subcategory_id, subcategory_name, category_id, category_name)
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
                                <th class="p-2 text-left">Oferta</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subcategories[$cat['event_category_id']] as $sub):
                            $hasOffer = false;
                            if (!empty($offers)) {
                                foreach ($offers as $offer) {
                                    if ($offer['event_subcategory_id'] == $sub['event_subcategory_id']) {
                                        $hasOffer = true;
                                        break;
                                    }
                                }
                            }
                        ?>
                            <tr class="border-b">
                                <td class="p-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" disabled <?= $hasOffer ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($sub['name']) ?></span>
                                    </label>
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

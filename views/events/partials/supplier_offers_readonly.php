<?php
/**
 * Partial: supplier_offers_readonly.php
 * Muestra las ofertas del proveedor en modo solo lectura, agrupadas por categoría y subcategoría.
 * Variables requeridas:
 * - $categories: array de categorías del evento
 * - $subcategories: array indexado por event_category_id con las subcategorías
 * - $offers: array de ofertas con datos completos (event_subcategory_id, subcategory_name, category_id, category_name)
 */

?>
<?php
// Organizar ofertas por categoría para mostrar solo las que tienen datos
$offersByCategory = [];
$categoriesWithOffers = [];

if (!empty($offers)) {
    foreach ($offers as $offer) {
        $categoryId = $offer['category_id'];
        $offersByCategory[$categoryId][] = $offer;
        
        // Agregar categoría si no existe
        if (!isset($categoriesWithOffers[$categoryId])) {
            $categoriesWithOffers[$categoryId] = [
                'event_category_id' => $categoryId,
                'name' => $offer['category_name']
            ];
        }
    }
}

// Si no hay ofertas, mostrar mensaje
if (empty($offersByCategory)): ?>
    <div class="no-data-message">
        <i class="fas fa-box"></i>
        <p>No se registraron ofertas para esta empresa.</p>
    </div>
<?php else: ?>
<div class="mb-2">
    <div class="flex flex-wrap gap-1 mb-2">
        <?php $i = 0; foreach ($categoriesWithOffers as $cat): ?>
            <button type="button" class="tab-btn btn btn-light px-2 py-1 text-xs md:text-sm <?= $i === 0 ? 'active' : '' ?>" data-tab="cat-<?= (int)$cat['event_category_id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php $i++; endforeach; ?>
    </div>
    <?php $i = 0; foreach ($categoriesWithOffers as $cat): ?>
        <div class="tab-panel" id="cat-<?= (int)$cat['event_category_id'] ?>" style="<?= $i === 0 ? 'display: block;' : 'display: none;' ?>">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 text-left">Oferta</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offersByCategory[$cat['event_category_id']] as $offer): ?>
                        <tr class="border-b">
                            <td class="p-2">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" disabled checked>
                                    <span><?= htmlspecialchars($offer['subcategory_name']) ?></span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php $i++; endforeach; ?>
</div>
<?php endif; ?>
<script>
// Funcionalidad de tabs para supplier offers readonly
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn[data-tab]');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Ocultar todos los paneles
            tabPanels.forEach(panel => {
                panel.style.display = 'none';
            });
            
            // Remover clase active de todos los botones
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar el panel objetivo y activar el botón
            const targetPanel = document.getElementById(targetTab);
            if (targetPanel) {
                targetPanel.style.display = 'block';
            }
            this.classList.add('active');
        });
    });
});
</script>

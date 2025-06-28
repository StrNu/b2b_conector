<?php
/**
 * Partial: requirements_readonly.php
 * Muestra los requerimientos en modo solo lectura, agrupados por categoría y subcategoría,
 * con la misma estructura visual que el registro, pero deshabilitado.
 * Variables requeridas:
 * - $categories: array de categorías del evento
 * - $subcategories: array indexado por event_category_id con las subcategorías
 * - $requirements: array de requerimientos con datos completos (budget_usd, quantity, unit_of_measurement)
 */

?>
<?php
// Organizar requirements por categoría para mostrar solo las que tienen datos
$reqsByCategory = [];
$categoriesWithReqs = [];

if (!empty($requirements)) {
    foreach ($requirements as $req) {
        $categoryId = $req['category_id'];
        $reqsByCategory[$categoryId][] = $req;
        
        // Agregar categoría si no existe
        if (!isset($categoriesWithReqs[$categoryId])) {
            $categoriesWithReqs[$categoryId] = [
                'event_category_id' => $categoryId,
                'name' => $req['category_name']
            ];
        }
    }
}

// Si no hay requirements, mostrar mensaje
if (empty($reqsByCategory)): ?>
    <div class="no-data-message">
        <i class="fas fa-shopping-cart"></i>
        <p>No se registraron requerimientos para esta empresa.</p>
    </div>
<?php else: ?>
<div class="mb-2">
    <div class="flex flex-wrap gap-1 mb-2">
        <?php $i = 0; foreach ($categoriesWithReqs as $cat): ?>
            <button type="button" class="tab-btn btn btn-light px-2 py-1 text-xs md:text-sm <?= $i === 0 ? 'active' : '' ?>" data-tab="cat-<?= (int)$cat['event_category_id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php $i++; endforeach; ?>
    </div>
    <?php $i = 0; foreach ($categoriesWithReqs as $cat): ?>
        <div class="tab-panel" id="cat-<?= (int)$cat['event_category_id'] ?>" style="<?= $i === 0 ? 'display: block;' : 'display: none;' ?>">
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
                    <?php foreach ($reqsByCategory[$cat['event_category_id']] as $req): ?>
                        <tr class="border-b">
                            <td class="p-2">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" disabled checked>
                                    <span><?= htmlspecialchars($req['subcategory_name']) ?></span>
                                </label>
                            </td>
                            <td class="p-2">
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-400">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control w-24" value="<?= htmlspecialchars($req['budget_usd'] ?? '') ?>" readonly>
                                </div>
                            </td>
                            <td class="p-2">
                                <input type="number" min="1" class="form-control w-16" value="<?= htmlspecialchars($req['quantity'] ?? '') ?>" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($req['unit_of_measurement'] ?? '') ?>" readonly>
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
// Funcionalidad de tabs para requirements readonly
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

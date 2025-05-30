<?php
// Vista para editar requerimientos de una empresa en un evento
// Variables requeridas: $event, $company, $categories, $subcategories, $requirements, $csrfToken
?>
<div class="container mx-auto py-8 max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Editar Requerimientos</h1>
    <form action="<?= BASE_URL ?>/events/editRequirements/<?= (int)$event->getId() ?>/<?= (int)$company->getId() ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <?php foreach ($categories as $cat): ?>
            <fieldset class="card mb-4">
                <legend class="font-semibold flex items-center gap-2 mb-2">
                    <i class="fas fa-box"></i> <?= htmlspecialchars($cat['name']) ?>
                </legend>
                <?php if (!empty($subcategories[$cat['event_category_id']])): ?>
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
                                        <input type="checkbox" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][selected]" value="1" <?= $req ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($sub['name']) ?></span>
                                    </label>
                                </td>
                                <td class="p-2">
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400">$</span>
                                        <input type="number" step="0.01" min="0" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][budget]" class="form-control w-24" value="<?= $req ? htmlspecialchars($req['budget_usd']) : '' ?>">
                                    </div>
                                </td>
                                <td class="p-2">
                                    <input type="number" min="1" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][quantity]" class="form-control w-16" value="<?= $req ? htmlspecialchars($req['quantity']) : '' ?>">
                                </td>
                                <td class="p-2">
                                    <input type="text" name="requirements[<?= (int)$sub['event_subcategory_id'] ?>][unit]" class="form-control" value="<?= $req ? htmlspecialchars($req['unit_of_measurement']) : '' ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <span class="text-xs text-gray-400">No hay subcategorías para esta categoría.</span>
                <?php endif; ?>
            </fieldset>
        <?php endforeach; ?>
        <div class="text-center mt-6">
            <button type="submit" class="btn btn-primary px-8 py-2 text-lg">Guardar cambios</button>
            <a href="<?= BASE_URL ?>/events/registration_details?event_id=<?= (int)$event->getId() ?>&company_id=<?= (int)$company->getId() ?>" class="btn btn-secondary ml-2">Cancelar</a>
        </div>
    </form>
</div>

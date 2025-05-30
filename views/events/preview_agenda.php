<?php
// Vista de previsualización de agenda para PDF y .ics
// Espera: $company, $appointments, $event
?>
<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Previsualización de Agenda</h1>
        <a href="<?= BASE_URL ?>/agendas/<?= (int)$event->getId() ?>" class="btn btn-outline-primary" style="min-width:160px;">&larr; Regresar a Agendas</a>
    </div>
    <div class="mb-4">
        <strong>Empresa:</strong> <?= htmlspecialchars(is_array($company) ? ($company['company_name'] ?? '') : ($company->getCompanyName() ?? '')) ?><br>
        <strong>Rol:</strong> <?php
            $rol = is_array($company) ? ($company['role'] ?? '') : ($company->getRole() ?? '');
            if ($rol === 'buyer') {
                echo 'Comprador';
            } elseif ($rol === 'supplier') {
                echo 'Proveedor';
            } else {
                echo htmlspecialchars($rol);
            }
        ?><br>
        <strong>Evento:</strong> <?= htmlspecialchars($event->getEventName()) ?>
    </div>
    <?php if (!empty($appointments)): ?>
        <table class="table-auto w-full bg-white rounded shadow border border-gray-200 mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">Fecha</th>
                    <th class="px-3 py-2">Hora Inicio</th>
                    <th class="px-3 py-2">Hora Fin</th>
                    <th class="px-3 py-2">Mesa</th>
                    <th class="px-3 py-2">Comprador</th>
                    <th class="px-3 py-2">Proveedor</th>
                    <th class="px-3 py-2">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td class="px-3 py-2"><?= date('d/m/Y', strtotime($appt['start_datetime'])) ?></td>
                        <td class="px-3 py-2"><?= date('H:i', strtotime($appt['start_datetime'])) ?></td>
                        <td class="px-3 py-2"><?= date('H:i', strtotime($appt['end_datetime'])) ?></td>
                        <td class="px-3 py-2 text-center"><?= htmlspecialchars($appt['table_number']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($appt['buyer_name']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($appt['supplier_name']) ?></td>
                        <td class="px-3 py-2">
                            <span class="inline-block rounded px-2 py-1 text-xs font-semibold <?= $appt['status'] === 'scheduled' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>">
                                <?= htmlspecialchars($appt['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="flex gap-2 mt-4">
            <a href="<?= BASE_URL ?>/agendas/download_pdf?event_id=<?= (int)$event->getId() ?>&company_id=<?= (int)(is_array($company) ? $company['company_id'] : $company->getId()) ?>" class="btn btn-primary" target="_blank"><i class="fas fa-file-pdf"></i> Descargar PDF</a>
            <a href="<?= BASE_URL ?>/agendas/download_ics?event_id=<?= (int)$event->getId() ?>&company_id=<?= (int)(is_array($company) ? $company['company_id'] : $company->getId()) ?>" class="btn btn-secondary" target="_blank"><i class="fas fa-calendar-alt"></i> Descargar .ics</a>
            <a href="#" class="btn btn-success"><i class="fas fa-paper-plane"></i> Enviar Agenda</a>
        </div>
    <?php else: ?>
        <div class="text-gray-500">No hay citas agendadas para esta empresa.</div>
    <?php endif; ?>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

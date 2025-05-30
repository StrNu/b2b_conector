<?php
// Partial: Agenda for buyers
// Expects: $buyers (array of buyers with sus agendas)
?>
<div class="agenda-list">
    <?php if (!empty($buyers)): ?>
        <?php foreach ($buyers as $buyer): ?>
            <?php
            // Compatibilidad: $buyer puede ser ['company'=>..., 'appointments'=>...] o ['company_name'=>..., ...]
            $company = $buyer['company'] ?? $buyer;
            $appointments = $buyer['appointments'] ?? ($buyer['appointments'] ?? []);
            if (($company['role'] ?? null) !== 'buyer') continue;
            if (empty($appointments)) continue;
            ?>
            <div class="mb-6">
                <h3 class="font-bold text-lg text-blue-800 mb-2">
                    <?= htmlspecialchars($company['company_name'] ?? $company['name'] ?? 'Sin nombre') ?>
                    <span class="text-xs text-gray-500">(Compradora)</span>
                </h3>
                <table class="table-auto w-full bg-white rounded shadow border border-gray-200 mb-2">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2">Hora Inicio</th>
                            <th class="px-3 py-2">Hora Fin</th>
                            <th class="px-3 py-2">Mesa</th>
                            <th class="px-3 py-2">Proveedor</th>
                            <th class="px-3 py-2">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td class="px-3 py-2"><?= date('H:i', strtotime($appt['start_datetime'])) ?></td>
                                <td class="px-3 py-2"><?= date('H:i', strtotime($appt['end_datetime'])) ?></td>
                                <td class="px-3 py-2 text-center"><?= htmlspecialchars($appt['table_number']) ?></td>
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
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-gray-500">No hay agendas para mostrar.</div>
    <?php endif; ?>
</div>

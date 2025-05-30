<?php
// Partial: Agenda for all companies
// Expects: $agendas (array of agendas agrupadas por company)
?>
<div class="agenda-list">
    <?php if (!empty($agendas)): ?>
        <?php foreach ($agendas as $agenda): ?>
            <?php if (!isset($agenda['company']) || !is_array($agenda['company'])) continue; ?>
            <?php $company = $agenda['company']; ?>
            <div class="mb-6">
                <h3 class="font-bold text-lg text-blue-800 mb-2">
                    <?= htmlspecialchars($company['company_name'] ?? '') ?>
                    <span class="text-xs text-gray-500">(<?= htmlspecialchars(($company['role'] ?? '') === 'buyer' ? 'Compradora' : 'Proveedora') ?>)</span>
                </h3>
                <?php if (!empty($agenda['appointments'])): ?>
                    <table class="table-auto w-full bg-white rounded shadow border border-gray-200 mb-2">
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
                            <?php foreach ($agenda['appointments'] as $appt): ?>
                                <tr>
                                    <td class="px-3 py-2">
                                        <?= date('d/m/Y', strtotime($appt['start_datetime'])) ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <?= date('H:i', strtotime($appt['start_datetime'])) ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <?= date('H:i', strtotime($appt['end_datetime'])) ?>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <?= htmlspecialchars($appt['table_number']) ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <?= htmlspecialchars($appt['buyer_name']) ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <?= htmlspecialchars($appt['supplier_name']) ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block rounded px-2 py-1 text-xs font-semibold <?= $appt['status'] === 'scheduled' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>">
                                            <?= htmlspecialchars($appt['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-gray-500">Sin citas agendadas.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-gray-500">No hay agendas disponibles.</div>
    <?php endif; ?>
</div>

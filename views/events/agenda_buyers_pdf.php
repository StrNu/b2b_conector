<?php
// PDF version of the buyers agenda tab (for a single company)
// Expects: $company, $appointments, $event
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda de <?= htmlspecialchars($company['company_name']) ?> - <?= htmlspecialchars($event->getEventName()) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; }
        h1 { font-size: 1.5em; margin-bottom: 0.5em; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .meta { margin-bottom: 1em; }
    </style>
</head>
<body>
    <h1>Agenda de Citas</h1>
    <div class="meta">
        <strong>Empresa:</strong> <?= htmlspecialchars($company['company_name']) ?><br>
        <strong>Rol:</strong> <?= htmlspecialchars($company['role']) ?><br>
        <strong>Evento:</strong> <?= htmlspecialchars($event->getEventName()) ?><br>
        <strong>Fecha de generación:</strong> <?= date('d/m/Y H:i') ?>
    </div>
    <?php if (!empty($appointments)): ?>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Mesa</th>
                <th>Proveedor</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appt): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($appt['start_datetime'])) ?></td>
                <td><?= date('H:i', strtotime($appt['start_datetime'])) ?></td>
                <td><?= date('H:i', strtotime($appt['end_datetime'])) ?></td>
                <td><?= htmlspecialchars($appt['table_number']) ?></td>
                <td><?= htmlspecialchars($appt['supplier_name']) ?></td>
                <td><?= htmlspecialchars($appt['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No hay citas agendadas para esta empresa.</p>
    <?php endif; ?>
</body>
</html>

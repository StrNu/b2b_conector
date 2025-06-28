<?php
// Plantilla PDF moderna para agenda de reuniones
// Variables esperadas: $company, $appointments, $event
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda de <?= htmlspecialchars($company['company_name']) ?> - <?= htmlspecialchars($event->getEventName()) ?></title>
    <style>
        @page {
            margin: 18mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #22223b;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .header-bar {
            background:  #1e3a8a;
            color: #fff;
            padding: 18px 28px 12px 28px;
            display: flex;
            align-items: center;
            border-radius: 12px 12px 0 0;
            margin-bottom: 0;
        }
        .event-logo {
            width: 60px;
            height: 36px;
            object-fit: contain;
            background: #fff;
            border-radius: 6px;
            margin-right: 18px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header-title-block {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .event-title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
            color: #fff;
        }
        .event-date {
            font-size: 12px;
            font-weight: 400;
            color: #e0e7ef;
        }
        .company-block {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            margin-top: 18px;
            padding-left: 28px;
        }
        .company-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: #f3f4f6;
            border-radius: 8px;
            margin-right: 16px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
            letter-spacing: 0.5px;
        }
        .agenda-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(30,64,175,0.04);
            padding: 18px 18px 10px 18px;
            margin-bottom: 18px;
            border: 1px solid #e5e7eb;
            width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .appointments-table th {
            background: #f1f5f9;
            color: #1e40af;
            padding: 7px 5px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid #3b82f6;
            text-transform: uppercase;
            font-size: 10px;
        }
        .appointments-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .appointments-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .appointments-table tr:last-child td {
            border-bottom: none;
        }
        .promo-row {
            background: #e0e7ef;
            text-align: center;
            color: #64748b;
            font-size: 10px;
            font-style: italic;
            height: 32px;
        }
        .promo-section {
            width: 90%;
            margin: 0 auto 10px auto;
        }
        .promo-banner {
            background: #e0e7ef;
            color: #64748b;
            font-size: 10px;
            font-style: italic;
            text-align: center;
            border-radius: 6px;
            margin-bottom: 6px;
            padding: 8px 0;
        }
        .pdf-footer {
            background: #1e3a8a;
            color: #fff;
            padding: 8px 0;
            border-radius: 0 0 10px 10px;
            text-align: center;
            font-size: 9px;
            margin-top: 18px;
            opacity: 0.92;
            width: 100%;
        }
    </style>
</head>
<body>
<?php
// Construir rutas absolutas y relativas para los logos (compatibles con dompdf)
// Obtener el logo del evento correctamente (soporta objeto con método o array, nunca propiedad privada)
$eventLogoValue = null;
if (is_object($event) && method_exists($event, 'getEventLogo')) {
    $eventLogoValue = $event->getEventLogo();
} elseif (is_array($event) && isset($event['event_logo'])) {
    $eventLogoValue = $event['event_logo'];
}
$eventLogoFile = !empty($eventLogoValue) ? ltrim($eventLogoValue, '/') : '';
$eventLogoAbsPath = $eventLogoFile ? __DIR__ . '/../../public/uploads/logos/' . $eventLogoFile : '';
$eventLogoExists = $eventLogoAbsPath && file_exists($eventLogoAbsPath);
$eventLogoRelPath = $eventLogoExists ? 'uploads/logos/' . $eventLogoFile : '';
$eventLogoFileUrl = $eventLogoExists ? 'file://' . realpath($eventLogoAbsPath) : '';
$eventLogoImageInfo = $eventLogoExists ? @getimagesize($eventLogoAbsPath) : false;

// El logo de la empresa participante sigue usando company_logo
$companyLogoValue = $company['company_logo'] ?? null;
$companyLogoFile = !empty($companyLogoValue) ? ltrim($companyLogoValue, '/') : '';
$companyLogoAbsPath = $companyLogoFile ? __DIR__ . '/../../public/uploads/logos/' . $companyLogoFile : '';
$companyLogoExists = $companyLogoAbsPath && file_exists($companyLogoAbsPath);
$companyLogoRelPath = $companyLogoExists ? 'uploads/logos/' . $companyLogoFile : '';
$companyLogoFileUrl = $companyLogoExists ? 'file://' . realpath($companyLogoAbsPath) : '';
$companyLogoImageInfo = $companyLogoExists ? @getimagesize($companyLogoAbsPath) : false;

// LOG: Revisar valores de background y logos
if (class_exists('Logger')) {
    Logger::debug('PDF LOG header-bar background', [
        'header-bar_css' => 'background: #1e40af;',
    ]);
    Logger::debug('PDF LOG event_company_logo', [
        'event_company_logo' => $eventLogoValue,
        'event_company_logo_exists' => !empty($eventLogoValue),
        'event_company_logo_path' => $eventLogoAbsPath,
        'event_logo_file_exists' => $eventLogoExists,
        'event_logo_rel_path' => $eventLogoRelPath,
        'event_logo_file_url' => $eventLogoFileUrl,
        'event_logo_image_info' => $eventLogoImageInfo,
    ]);
    Logger::debug('PDF LOG company_logo', [
        'company_logo' => $company['company_logo'] ?? null,
        'company_logo_exists' => !empty($company['company_logo']),
        'company_logo_path' => $companyLogoAbsPath,
        'company_logo_file_exists' => $companyLogoExists,
        'company_logo_rel_path' => $companyLogoRelPath,
        'company_logo_file_url' => $companyLogoFileUrl,
        'company_logo_image_info' => $companyLogoImageInfo,
    ]);
}
?>
    <div class="header-bar">
        <?php if ($eventLogoExists): ?>
            <?php if ($eventLogoFileUrl): ?>
                <img src="<?= htmlspecialchars($eventLogoFileUrl) ?>" class="event-logo" alt="Logo Evento">
            <?php else: ?>
                <img src="<?= htmlspecialchars($eventLogoRelPath) ?>" class="event-logo" alt="Logo Evento">
            <?php endif; ?>
        <?php else: ?>
            <div class="event-logo" style="font-size:9px;color:#1e40af;">LOGO<br>EVENTO</div>
        <?php endif; ?>
        <div class="header-title-block">
            <div class="event-title"><?= htmlspecialchars($event->getEventName()) ?></div>
            <div class="event-date">
                <?= date('d/m/Y', strtotime($event->getStartDate())) ?> - <?= date('d/m/Y', strtotime($event->getEndDate())) ?>
            </div>
        </div>
    </div>
    <div class="company-block">
        <?php if ($companyLogoExists): ?>
            <?php if ($companyLogoFileUrl): ?>
                <img src="<?= htmlspecialchars($companyLogoFileUrl) ?>" class="company-logo" alt="Logo Empresa">
            <?php else: ?>
                <img src="<?= htmlspecialchars($companyLogoRelPath) ?>" class="company-logo" alt="Logo Empresa">
            <?php endif; ?>
        <?php else: ?>
            <div class="company-logo" style="font-size:8px;color:#64748b;">LOGO<br>EMPRESA</div>
        <?php endif; ?>
        <div class="company-name">
            <?= htmlspecialchars($company['company_name']) ?>
        </div>
    </div>
    <div class="agenda-section">
        <div class="section-title">Citas programadas</div>
        <?php if (!empty($appointments)): ?>
        <table class="appointments-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Mesa</th>
                    <th>Empresa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($appt['start_datetime'])) ?></td>
                    <td><?= date('H:i', strtotime($appt['start_datetime'])) ?> - <?= date('H:i', strtotime($appt['end_datetime'])) ?></td>
                    <td><?= htmlspecialchars($appt['table_number']) ?></td>
                    <td>
                        <?php
                        $currentRole = $company['role'] ?? '';
                        if ($currentRole === 'buyer') {
                            echo htmlspecialchars($appt['supplier_name']);
                        } else {
                            echo htmlspecialchars($appt['buyer_name']);
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center;padding:30px 0;color:#64748b;font-size:13px;">No hay citas agendadas para esta empresa en este evento.</div>
        <?php endif; ?>
    </div>
    <div class="promo-section">
        <?php
        // Puedes definir $promoImages = ['url1', 'url2', 'url3']; antes de renderizar la vista
        $promoImages = $promoImages ?? [];
        for ($i = 0; $i < 3; $i++):
            if (!empty($promoImages[$i])):
        ?>
            <div class="promo-banner" style="padding:0;">
                <img src="<?= htmlspecialchars($promoImages[$i]) ?>" alt="Banner Promocional <?= $i+1 ?>" style="max-width:420px;max-height:80px;width:auto;height:auto;display:block;margin:0 auto;">
            </div>
        <?php else: ?>
            <div class="promo-banner"><span style="font-style:italic;">ESPACIO PROMOCIONAL <?= $i+1 ?>, 420 x 80</span></div>
        <?php endif; endfor; ?>
    </div>
    <div class="pdf-footer">
        <?= htmlspecialchars($event->getEventName()) ?> | Agenda personalizada para <?= htmlspecialchars($company['company_name']) ?>
    </div>
</body>
</html>
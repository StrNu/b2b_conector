<?php
// views/shared/header_public.php
// Header simplificado para páginas públicas: solo logo y nombre del evento
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Se espera que $event esté definido y tenga getCompanyName() y getEventLogo() o similar
$companyName = method_exists($event, 'getCompanyName') ? $event->getCompanyName() : '';
$eventLogo = method_exists($event, 'getEventLogo') ? $event->getEventLogo() : null;
$eventName = method_exists($event, 'getEventName') ? $event->getEventName() : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($eventName ?: $companyName) ?> - B2B Conector</title>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/normalize.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS de componentes comunes -->
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/forms.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/buttons.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/notifications.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components/tabs.css">
    <link rel="shortcut icon" href="<?= BASE_PUBLIC_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <style>
        body { background: #f7f7fa; margin: 0; }
        .public-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem 1.5rem 1rem;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.03);
        }
        .public-header-logo {
            width: 70px; height: 70px; border-radius: 12px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-right: 1.5rem;
        }
        .public-header-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .public-header-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.25rem;
        }
        .public-header-company {
            font-size: 1.1rem;
            color: #4b5563;
            font-weight: 400;
        }
        @media (max-width: 600px) {
            .public-header { flex-direction: column; text-align: center; }
            .public-header-logo { margin: 0 0 1rem 0; }
        }
    </style>
</head>
<body>
    <header class="public-header">
        <div class="public-header-logo">
            <?php if ($eventLogo): ?>
                <img src="<?= BASE_URL ?>/uploads/logos/<?= htmlspecialchars($eventLogo) ?>" alt="Logo del evento">
            <?php else: ?>
                <i class="fas fa-building fa-2x text-gray-400"></i>
            <?php endif; ?>
        </div>
        <div>
            <div class="public-header-title">
                <?= htmlspecialchars($eventName ?: $companyName) ?>
            </div>
            <?php if ($companyName && $eventName && $companyName !== $eventName): ?>
                <div class="public-header-company">
                    <?= htmlspecialchars($companyName) ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <div class="main-container" style="max-width:900px;margin:0 auto;">

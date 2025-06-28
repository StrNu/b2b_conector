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
    
    <!-- Material Design 3 CSS -->
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/core.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/material-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="<?= BASE_PUBLIC_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts for Material Design -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap">
    <style>
        body { 
            background: var(--md-surface-bright, #fef7ff); 
            margin: 0; 
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .public-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem 1.5rem 1rem;
            background: linear-gradient(135deg, var(--color-primary-500, #9c27b0) 0%, var(--color-secondary-500, #673ab7) 100%);
            border-bottom: none;
            box-shadow: 0px 4px 8px 3px rgba(0, 0, 0, 0.15);
            color: white;
        }
        .public-header-logo {
            width: 70px; 
            height: 70px; 
            border-radius: 16px; 
            background: rgba(255, 255, 255, 0.1); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden; 
            margin-right: 1.5rem;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .public-header-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .public-header-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .public-header-company {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
        }
        @media (max-width: 600px) {
            .public-header { 
                flex-direction: column; 
                text-align: center; 
                padding: 1.5rem 1rem;
            }
            .public-header-logo { margin: 0 0 1rem 0; }
            .public-header-title { font-size: 1.5rem; }
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

<?php
// views/shared/event_header.php
// Header específico para vistas de eventos

// Verificar que el usuario esté autenticado como usuario de evento
if (!isEventUserAuthenticated()) {
    redirect(BASE_URL . '/auth/event-login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard de Evento') ?> - B2B Conector</title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
    <?php if (isset($moduleCSS)): ?>
        <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/<?= $moduleCSS ?>.css">
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos específicos para layout de eventos -->
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .event-layout {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .event-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .event-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .event-logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .app-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
        }
        
        .app-logo img {
            height: 40px;
            width: auto;
        }
        
        .app-logo-text {
            font-size: 18px;
            font-weight: 600;
        }
        
        .event-divider {
            height: 30px;
            width: 1px;
            background: rgba(255,255,255,0.3);
        }
        
        .event-info {
            flex: 1;
            margin-left: 20px;
        }
        
        .event-name {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .event-details {
            font-size: 14px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .event-detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-email {
            font-size: 14px;
            margin: 0;
            opacity: 0.9;
        }
        
        .user-role {
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 12px;
            margin-top: 2px;
            display: inline-block;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
        }
        
        .event-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            width: 100%;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .breadcrumb-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .breadcrumb-item {
            color: #666;
            font-size: 14px;
        }
        
        .breadcrumb-item:not(:last-child)::after {
            content: '/';
            margin-left: 8px;
            color: #ccc;
        }
        
        .breadcrumb-item.active {
            color: #007bff;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .event-header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .event-logo-section {
                flex-direction: column;
                gap: 10px;
            }
            
            .event-divider {
                display: none;
            }
            
            .event-info {
                margin-left: 0;
            }
            
            .event-details {
                justify-content: center;
            }
            
            .user-section {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="event-layout">
        <header class="event-header">
            <div class="event-header-content">
                <div class="event-logo-section">
                    <a href="<?= BASE_URL ?>/dashboard" class="app-logo">
                        <img src="<?= BASE_PUBLIC_URL ?>/assets/images/logo.png" alt="B2B Conector" onerror="this.style.display='none'">
                        <span class="app-logo-text">B2B Conector</span>
                    </a>
                    
                    <div class="event-divider"></div>
                </div>
                
                <div class="event-info">
                    <h1 class="event-name">
                        <i class="fas fa-calendar-alt"></i>
                        <?= htmlspecialchars($event->getEventName()) ?>
                    </h1>
                    <div class="event-details">
                        <div class="event-detail-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= formatDate($event->getStartDate()) ?> - <?= formatDate($event->getEndDate()) ?></span>
                        </div>
                        <div class="event-detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($event->getVenue()) ?></span>
                        </div>
                        <?php if ($event->getContactEmail()): ?>
                        <div class="event-detail-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($event->getContactEmail()) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="user-section">
                    <div class="user-info">
                        <p class="user-email"><?= htmlspecialchars(getEventUserEmail()) ?></p>
                        <span class="user-role">
                            <?php if (isEventAdmin()): ?>
                                <i class="fas fa-user-cog"></i> Administrador
                            <?php else: ?>
                                <i class="fas fa-user"></i> Asistente
                            <?php endif; ?>
                        </span>
                    </div>
                    <a href="<?= BASE_URL ?>/event-dashboard/logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Salir</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="event-content">
            <!-- Breadcrumb opcional -->
            <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
            <nav class="breadcrumb">
                <ul class="breadcrumb-list">
                    <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                        <li class="breadcrumb-item <?= $index === count($breadcrumbs) - 1 ? 'active' : '' ?>">
                            <?php if (isset($breadcrumb['url']) && $index !== count($breadcrumbs) - 1): ?>
                                <a href="<?= $breadcrumb['url'] ?>"><?= htmlspecialchars($breadcrumb['title']) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($breadcrumb['title']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <!-- Aquí va el contenido de cada vista -->
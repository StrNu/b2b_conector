<?php
// views/event-dashboard/attendee_agenda.php
// Vista pública para asistentes (buyers/suppliers) mostrando solo su agenda

// Configurar variables para el layout de eventos
$pageTitle = 'Mi Agenda - ' . $event->getEventName();
$moduleCSS = 'attendee-agenda';
$moduleJS = 'attendee-agenda';

// Configurar breadcrumbs
$breadcrumbs = [
    ['title' => 'Mi Agenda', 'url' => BASE_URL . '/event-dashboard/attendee-agenda']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/attendee-agenda.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos específicos para la vista de asistentes */
        .attendee-layout {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
        }

        /* Header del evento */
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .event-header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 15px;
        }

        .header-title {
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }

        .header-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .event-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .event-logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .event-details h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .event-meta {
            font-size: 14px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-info {
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .company-role {
            padding: 4px 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        /* Banner publicitario superior - Medidas: 728x90px (Leaderboard) */
        .ad-banner-top {
            width: 728px;
            height: 90px;
            background: white;
            margin: 20px auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            border: 2px dashed #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .ad-banner-top::before {
            content: "728x90px";
            position: absolute;
            top: 5px;
            right: 8px;
            font-size: 10px;
            color: #94a3b8;
            font-weight: 500;
        }

        .ad-banner-top h3 {
            color: #64748b;
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .ad-banner-top p {
            color: #94a3b8;
            margin: 0;
            font-size: 11px;
        }

        /* Contenido principal */
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            align-items: start;
        }

        .agenda-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .agenda-header {
            background: #f8fafc;
            padding: 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .agenda-header h2 {
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .agenda-header .subtitle {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        /* Lista de citas */
        .appointments-list {
            padding: 0;
        }

        .appointment-item {
            padding: 25px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: background 0.3s ease;
        }

        .appointment-item:hover {
            background: #f8fafc;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-time {
            min-width: 120px;
            text-align: center;
        }

        .time-display {
            background: #3b82f6;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .date-display {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .appointment-details {
            flex: 1;
        }

        .partner-company {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .appointment-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #64748b;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .appointment-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-scheduled {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Estado vacío */
        .empty-agenda {
            padding: 60px 25px;
            text-align: center;
            color: #64748b;
        }

        .empty-agenda i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-agenda h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #374151;
        }

        .empty-agenda p {
            margin: 0;
            font-size: 14px;
        }

        /* Banners laterales */
        .sidebar-ads {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Banner lateral - Medidas: 300x250px (Medium Rectangle) */
        .ad-banner-side {
            width: 300px;
            height: 250px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 2px dashed #e2e8f0;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .ad-banner-side::before {
            content: "300x250px";
            position: absolute;
            top: 8px;
            right: 10px;
            font-size: 10px;
            color: #94a3b8;
            font-weight: 500;
        }

        .ad-banner-side h4 {
            color: #64748b;
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .ad-banner-side p {
            color: #94a3b8;
            margin: 0;
            font-size: 12px;
            padding: 0 15px;
        }

        /* Footer del evento */
        .event-footer {
            background: #1e293b;
            color: white;
            padding: 30px 0;
            margin-top: 40px;
        }

        .event-footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .sidebar-ads {
                grid-row: 1;
                flex-direction: row;
                overflow-x: auto;
                justify-content: center;
                gap: 15px;
            }

            .ad-banner-side {
                flex-shrink: 0;
            }
        }

        @media (max-width: 768px) {
            .header-main {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .header-top {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .event-info {
                flex-direction: column;
                text-align: center;
            }

            .event-meta {
                justify-content: center;
                flex-wrap: wrap;
            }

            .company-info {
                text-align: center;
            }

            /* Banner superior responsive - se convierte en mobile banner 320x50px */
            .ad-banner-top {
                width: 320px;
                height: 50px;
            }

            .ad-banner-top::before {
                content: "320x50px";
            }

            .ad-banner-top h3 {
                font-size: 12px;
                margin: 0 0 3px 0;
            }

            .ad-banner-top p {
                font-size: 10px;
            }

            /* Banners laterales se convierten en mobile banners */
            .sidebar-ads {
                flex-direction: column;
                align-items: center;
            }

            .ad-banner-side {
                width: 320px;
                height: 100px;
            }

            .ad-banner-side::before {
                content: "320x100px";
            }

            .ad-banner-side h4 {
                font-size: 13px;
                margin: 0 0 5px 0;
            }

            .ad-banner-side p {
                font-size: 11px;
            }

            .appointment-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .appointment-time {
                min-width: auto;
                text-align: left;
            }

            .appointment-meta {
                flex-wrap: wrap;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body class="attendee-layout">
    <!-- Header del evento -->
    <header class="event-header">
        <div class="event-header-container">
            <!-- Barra superior con título y cerrar sesión -->
            <div class="header-top">
                <div class="header-title">
                    <i class="fas fa-calendar-alt"></i> Panel de Asistentes
                </div>
                <a href="<?= BASE_URL ?>/event-dashboard/logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
            
            <!-- Información principal del evento -->
            <div class="header-main">
                <div class="event-info">
                    <div class="event-logo">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="event-details">
                        <h1><?= htmlspecialchars($event->getEventName()) ?></h1>
                        <div class="event-meta">
                            <?php if ($event->getStartDate() && $event->getEndDate()): ?>
                                <span><i class="fas fa-calendar"></i> <?= formatDate($event->getStartDate()) ?> - <?= formatDate($event->getEndDate()) ?></span>
                            <?php endif; ?>
                            <?php if ($event->getVenue()): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event->getVenue()) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="company-info">
                    <div class="company-name"><?= htmlspecialchars($company->getCompanyName()) ?></div>
                    <div class="company-role role-<?= $company->getRole() ?>">
                        <?= $company->getRole() === 'buyer' ? 'Comprador' : 'Proveedor' ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Banner publicitario superior -->
    <div class="ad-banner-top">
        <h3><i class="fas fa-bullhorn"></i> Espacio Publicitario #1</h3>
        <p>Este espacio está disponible para anunciantes. Contacte al organizador del evento.</p>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Sección de agenda -->
        <div class="agenda-section">
            <div class="agenda-header">
                <h2>
                    <i class="fas fa-calendar-alt"></i>
                    Mi Agenda de Citas
                </h2>
                <p class="subtitle">
                    Reuniones programadas para <?= htmlspecialchars($company->getCompanyName()) ?>
                </p>
            </div>

            <div class="appointments-list">
                <?php if (!empty($appointmentsByDay)): ?>
                    <?php foreach ($appointmentsByDay as $day => $dayAppointments): ?>
                        <?php 
                        // Ordenar citas por hora
                        usort($dayAppointments, function($a, $b) {
                            return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
                        });
                        ?>
                        
                        <?php foreach ($dayAppointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-time">
                                    <div class="time-display">
                                        <?= date('H:i', strtotime($appointment['start_datetime'])) ?>
                                    </div>
                                    <div class="date-display">
                                        <?= formatDate($appointment['start_datetime']) ?>
                                    </div>
                                </div>
                                
                                <div class="appointment-details">
                                    <div class="partner-company">
                                        <?= htmlspecialchars($appointment['partner_company'] ?? 'Empresa no especificada') ?>
                                    </div>
                                    
                                    <div class="appointment-meta">
                                        <?php if (!empty($appointment['table_number'])): ?>
                                            <div class="meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                Mesa <?= htmlspecialchars($appointment['table_number']) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            $duration = 30; // Duración por defecto
                                            if (!empty($appointment['end_datetime'])) {
                                                $start = strtotime($appointment['start_datetime']);
                                                $end = strtotime($appointment['end_datetime']);
                                                $duration = ($end - $start) / 60;
                                            }
                                            echo $duration . ' minutos';
                                            ?>
                                        </div>

                                        <?php if (!empty($appointment['meeting_type'])): ?>
                                            <div class="meta-item">
                                                <i class="fas fa-handshake"></i>
                                                <?= ucfirst(htmlspecialchars($appointment['meeting_type'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="appointment-status status-<?= $appointment['status'] ?? 'scheduled' ?>">
                                    <?php
                                    $status = $appointment['status'] ?? 'scheduled';
                                    switch ($status) {
                                        case 'scheduled':
                                            echo 'Programada';
                                            break;
                                        case 'confirmed':
                                            echo 'Confirmada';
                                            break;
                                        case 'completed':
                                            echo 'Completada';
                                            break;
                                        case 'cancelled':
                                            echo 'Cancelada';
                                            break;
                                        default:
                                            echo 'Programada';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-agenda">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No tienes citas programadas</h3>
                        <p>Cuando se programen reuniones con otras empresas, aparecerán aquí.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banners publicitarios laterales -->
        <div class="sidebar-ads">
            <div class="ad-banner-side">
                <h4><i class="fas fa-ad"></i> Publicidad #2</h4>
                <p>Espacio disponible para patrocinadores del evento</p>
            </div>
            
            <div class="ad-banner-side">
                <h4><i class="fas fa-bullhorn"></i> Promoción #3</h4>
                <p>Contacte al organizador para publicitar aquí</p>
            </div>
        </div>
    </div>

    <!-- Footer del evento -->
    <footer class="event-footer">
        <div class="event-footer-container">
            <div class="footer-content">
                <div class="footer-info">
                    <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($event->getEventName()) ?></span>
                    <?php if ($event->getCompanyName()): ?>
                        <span>•</span>
                        <span>Organizado por <?= htmlspecialchars($event->getCompanyName()) ?></span>
                    <?php endif; ?>
                    <span>•</span>
                    <span>Panel de Asistentes</span>
                </div>
                
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>/pages/help">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </a>
                    <a href="<?= BASE_URL ?>/pages/privacy-policy">
                        <i class="fas fa-shield-alt"></i> Privacidad
                    </a>
                    <a href="<?= BASE_URL ?>/pages/terms">
                        <i class="fas fa-file-contract"></i> Términos
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Auto-refresh cada 5 minutos para mantener la agenda actualizada
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutos

        // Highlight de citas próximas (dentro de 30 minutos)
        document.addEventListener('DOMContentLoaded', function() {
            const appointments = document.querySelectorAll('.appointment-item');
            const now = new Date();
            
            appointments.forEach(function(appointment) {
                const timeDisplay = appointment.querySelector('.time-display');
                if (timeDisplay) {
                    const timeText = timeDisplay.textContent.trim();
                    const [hours, minutes] = timeText.split(':').map(Number);
                    
                    const appointmentTime = new Date();
                    appointmentTime.setHours(hours, minutes, 0, 0);
                    
                    const timeDiff = appointmentTime.getTime() - now.getTime();
                    const minutesDiff = timeDiff / (1000 * 60);
                    
                    // Highlight si está dentro de 30 minutos
                    if (minutesDiff > 0 && minutesDiff <= 30) {
                        appointment.style.background = '#fef3c7';
                        appointment.style.borderLeft = '4px solid #f59e0b';
                        timeDisplay.style.background = '#f59e0b';
                        timeDisplay.style.animation = 'pulse 2s infinite';
                    }
                }
            });
        });

        // Animación de pulso para citas próximas
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.7; }
                100% { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
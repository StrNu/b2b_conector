</main> <!-- Fin del contenido principal de evento -->
        
        <footer class="event-footer">
            <div class="event-footer-content">
                <div class="footer-left">
                    <div class="footer-event-info">
                        <strong><?= htmlspecialchars($event->getEventName()) ?></strong>
                        <span class="footer-event-dates">
                            <?= formatDate($event->getStartDate()) ?> - <?= formatDate($event->getEndDate()) ?>
                        </span>
                    </div>
                    <?php if ($event->getCompanyName()): ?>
                    <div class="footer-organizer">
                        Organizado por: <strong><?= htmlspecialchars($event->getCompanyName()) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer-right">
                    <div class="footer-links">
                        <a href="<?= BASE_URL ?>/event-dashboard" class="footer-link">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <?php if (isEventAdmin()): ?>
                        <a href="<?= BASE_URL ?>/events/<?= $event->getId() ?>" class="footer-link">
                            <i class="fas fa-cog"></i> Administrar
                        </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/event-dashboard/help" class="footer-link">
                            <i class="fas fa-question-circle"></i> Ayuda
                        </a>
                    </div>
                    <div class="footer-copyright">
                        &copy; <?= date('Y') ?> B2B Conector
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- Fin de .event-layout -->

    <!-- Modals compartidos para eventos -->
    <?php
    // Asegurar que $csrfToken esté definido para los modals
    if (!isset($csrfToken)) {
        if (function_exists('generateCSRFToken')) {
            $csrfToken = generateCSRFToken();
        } else {
            $csrfToken = '';
        }
    }
    ?>
    
    <!-- JavaScript base -->
    <script>
        // Define BASE_URL como variable global de JavaScript
        window.BASE_URL = '<?= BASE_PUBLIC_URL ?>';
        window.EVENT_ID = <?= getEventId() ?>;
        window.USER_TYPE = '<?= getEventUserType() ?>';
    </script>
    
    <!-- Scripts principales -->
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/components/modal.js"></script>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/utils/form-validation.js"></script>
    
    <!-- JS específico de módulo -->
    <?php if (isset($moduleJS)): ?>
        <script src="<?= BASE_PUBLIC_URL ?>/assets/js/modules/<?= $moduleJS ?>.js"></script>
    <?php endif; ?>

    <!-- JS adicionales específicos -->
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= BASE_PUBLIC_URL ?>/assets/js/<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        .event-footer {
            background: #343a40;
            color: #fff;
            padding: 20px 0;
            margin-top: auto;
            border-top: 3px solid #007bff;
        }
        
        .event-footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-event-info {
            margin-bottom: 8px;
        }
        
        .footer-event-dates {
            color: #adb5bd;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .footer-organizer {
            color: #adb5bd;
            font-size: 14px;
        }
        
        .footer-right {
            text-align: right;
        }
        
        .footer-links {
            display: flex;
            gap: 20px;
            margin-bottom: 8px;
        }
        
        .footer-link {
            color: #adb5bd;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .footer-link:hover {
            color: #007bff;
        }
        
        .footer-copyright {
            color: #6c757d;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .event-footer-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .footer-right {
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
                flex-wrap: wrap;
                gap: 15px;
            }
        }
    </style>
</body>
</html>
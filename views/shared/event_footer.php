<footer class="footer footer-event">
    <div class="footer-container">
        <div class="footer-main">
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> Panel de Eventos B2B Conector. Todos los derechos reservados.</p>
            </div>
            <div class="footer-links">
                <a href="<?= BASE_URL ?>/pages/privacy-policy" class="footer-link">
                    <i class="fas fa-shield-alt"></i> Política de Privacidad
                </a>
                <a href="<?= BASE_URL ?>/pages/terms" class="footer-link">
                    <i class="fas fa-file-contract"></i> Condiciones de Uso
                </a>
                <a href="<?= BASE_URL ?>/pages/help" class="footer-link">
                    <i class="fas fa-headset"></i> Soporte
                </a>
            </div>
        </div>
        
        <div class="footer-divider">
            <div class="footer-info">
                <div class="footer-info-left">
                    <?php if (isset($event)): ?>
                        <i class="fas fa-calendar-alt"></i>
                        <span style="font-weight: 500;"><?= htmlspecialchars($event->getEventName()) ?></span>
                        <?php if ($event->getStartDate() && $event->getEndDate()): ?>
                            <span>•</span>
                            <span><?= formatDate($event->getStartDate()) ?> - <?= formatDate($event->getEndDate()) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span>Panel de Eventos</span>
                    <?php endif; ?>
                    
                    <?php if (function_exists('getEventUserEmail') && getEventUserEmail()): ?>
                        <span>•</span>
                        <span>
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars(getEventUserEmail()) ?>
                            <?php if (function_exists('isEventAdmin')): ?>
                                <span class="user-badge <?= isEventAdmin() ? 'user-badge-admin' : 'user-badge-assistant' ?>">
                                    <?= isEventAdmin() ? 'Admin' : 'Asistente' ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="footer-info-right">
                    <?php if (isset($event) && $event->getCompanyName()): ?>
                        <span>
                            <i class="fas fa-building"></i>
                            Organizado por: <?= htmlspecialchars($event->getCompanyName()) ?>
                        </span>
                        <span>•</span>
                    <?php endif; ?>
                    <span>Versión 1.0</span>
                </div>
            </div>
        </div>
    </div>
</footer>
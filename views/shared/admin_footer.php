<footer class="modern-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-main">
                <div>
                    <p>&copy; <?= date('Y') ?> B2B Conector. Todos los derechos reservados.</p>
                </div>
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>/pages/privacy-policy" class="footer-link">
                        <i class="fas fa-shield-alt"></i>
                        <span>Privacidad</span>
                    </a>
                    <a href="<?= BASE_URL ?>/pages/terms" class="footer-link">
                        <i class="fas fa-file-contract"></i>
                        <span>Términos</span>
                    </a>
                    <a href="<?= BASE_URL ?>/pages/help" class="footer-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Ayuda</span>
                    </a>
                </div>
            </div>
            
            <div class="footer-divider"></div>
            
            <div class="footer-info">
                <div>
                    <span>Panel de Administración</span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span> • Usuario: <?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'N/A') ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <span>Versión 1.0 • PHP <?= PHP_VERSION ?></span>
                </div>
            </div>
        </div>
    </div>
</footer>
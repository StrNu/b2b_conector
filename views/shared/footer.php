</main> <!-- Fin del contenido principal -->
        </div> <!-- Fin de .main-container -->
        
        <footer class="main-footer">
            <div class="footer-content">
                <p>&copy; <?= date('Y') ?> B2B Conector. Todos los derechos reservados.</p>
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>/pages/privacy-policy.php">Privacidad</a>
                    <a href="<?= BASE_URL ?>/pages/terms.php">Términos</a>
                    <a href="<?= BASE_URL ?>/pages/help.php">Ayuda</a>
                </div>
            </div>
        </footer>
    </div> <!-- Fin de .app-container -->

    <?php include(VIEW_DIR . '/shared/modals.php'); ?>
    
    <!-- JavaScript para funcionalidades básicas -->
     <!-- Scripts base -->
<script>
    // Define BASE_URL como variable global de JavaScript
    window.BASE_URL = '<?= BASE_PUBLIC_URL ?>';
</script>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/components/autosearch.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= BASE_URL ?>/assets/js/components/tabs.js"></script>
        <!-- JS base siempre cargado -->
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
</body>
</html>
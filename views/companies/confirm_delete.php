<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="container" style="max-width: 480px; margin: 40px auto;">
    <div class="card" style="padding: 2em; text-align: center;">
        <h2>Confirmar eliminación</h2>
        <p style="margin: 1.5em 0; font-size: 1.1em; color: #b71c1c;">
            <?php echo htmlspecialchars($message); ?>
        </p>
        <form method="post" action="<?php echo htmlspecialchars($action); ?><?php if (isset($_GET['id'])) echo '?id=' . urlencode($_GET['id']); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger" style="margin-right: 1em;">Sí, eliminar</button>
            <a href="<?php echo BASE_URL . '/companies'; ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

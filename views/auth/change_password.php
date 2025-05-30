<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="container mx-auto py-8 max-w-md">
    <h1 class="text-2xl font-bold text-center mb-4">Cambiar Contraseña</h1>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <form action="<?= BASE_URL ?>/auth/change_password_event" method="POST" class="bg-white rounded-xl shadow-lg p-6 space-y-4">
        <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $_POST['email'] ?? '') ?>">
        <div>
            <label class="label">Nueva Contraseña *</label>
            <input type="password" name="new_password" class="form-control" required placeholder="********">
        </div>
        <div>
            <label class="label">Repetir Nueva Contraseña *</label>
            <input type="password" name="confirm_password" class="form-control" required placeholder="********">
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary px-8 py-2 text-lg">Guardar Contraseña</button>
        </div>
    </form>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

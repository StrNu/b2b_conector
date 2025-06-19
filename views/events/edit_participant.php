<?php include(VIEW_DIR . '/shared/header.php'); ?>
<div class="content max-w-lg mx-auto mt-8">
    <h1 class="text-2xl font-bold mb-4">Editar Participante</h1>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <form action="<?= BASE_URL ?>/events/editParticipant/<?= $eventId ?>/<?= $assistant->getId() ?>" method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($assistant->getFirstName()) ?>">
            <?php if (isset($_SESSION['validation_errors']['first_name'])): ?>
                <div class="error-message"><?= $_SESSION['validation_errors']['first_name'] ?></div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Apellido</label>
            <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($assistant->getLastName()) ?>">
            <?php if (isset($_SESSION['validation_errors']['last_name'])): ?>
                <div class="error-message"><?= $_SESSION['validation_errors']['last_name'] ?></div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($assistant->getEmail()) ?>">
            <?php if (isset($_SESSION['validation_errors']['email'])): ?>
                <div class="error-message"><?= $_SESSION['validation_errors']['email'] ?></div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="mobile_phone" class="form-control" value="<?= htmlspecialchars($assistant->getMobilePhone() ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Empresa</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($assistant->getCompany() ? $assistant->getCompany()['company_name'] : '') ?>" disabled>
            <input type="hidden" name="company_id" value="<?= htmlspecialchars($assistant->getCompanyId()) ?>">
        </div>
        <div class="flex gap-2 mt-6">
            <a href="<?= BASE_URL ?>/events/participants/<?= $eventId ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
        </div>
    </form>
    <?php unset($_SESSION['validation_errors']); ?>
</div>
<?php include(VIEW_DIR . '/shared/footer.php'); ?>

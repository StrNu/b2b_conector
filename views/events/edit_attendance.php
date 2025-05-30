<div class="content max-w-lg mx-auto mt-8">
    <h1 class="text-2xl font-bold mb-4">Editar Días de Asistencia</h1>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <form action="<?= BASE_URL ?>/events/companyAttendance/<?= (int)$event->getId() ?>/<?= (int)$company->getId() ?>" method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="form-group">
            <label class="block font-semibold mb-2">Selecciona los días de asistencia:</label>
            <div class="grid grid-cols-1 gap-2">
                <?php foreach ($eventDays as $day): ?>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="attendance_days[]" value="<?= htmlspecialchars($day) ?>" class="mr-2" <?= in_array($day, $formattedDays) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($day) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-2 mt-6">
            <a href="<?= BASE_URL ?>/events/view_full_registration/<?= (int)$event->getId() ?>/<?= (int)$company->getId() ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
        </div>
    </form>
</div>

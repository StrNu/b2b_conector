<div class="container mx-auto py-8 max-w-2xl">
    <div class="text-center mb-6">
        <a href="<?= BASE_URL ?>/events/event_list?event_id=<?= (int)$event->getId() ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Regresar al evento</a>
    </div>
    <h1 class="text-3xl font-bold text-center mb-2">Detalles de Registro de Empresa</h1>
    <p class="text-center text-gray-600 mb-6">A continuación se muestra la información completa registrada para esta empresa en el evento.</p>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>
    <div class="space-y-8 bg-white rounded-xl shadow-lg p-6">
        <!-- Información de la Empresa -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-building"></i> Información de la Empresa
                <a href="<?= BASE_URL ?>/events/companies/<?= (int)$event->getId() ?>/edit/<?= (int)$company->getId() ?>" class="ml-2 btn btn-xs btn-warning" title="Editar empresa"><i class="fas fa-edit"></i> Editar</a>
            </legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><strong>Nombre de la Empresa:</strong> <?= htmlspecialchars($company->getCompanyName()) ?></div>
                <div><strong>Sitio Web:</strong> <?= htmlspecialchars($company->getWebsite()) ?></div>
            </div>
            <div class="mb-2"><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($company->getDescription())) ?></div>
            <?php if ($company->getCompanyLogo()): ?>
                <div class="mb-2"><strong>Logo:</strong><br><img src="<?= BASE_URL ?>/uploads/logos/<?= htmlspecialchars($company->getCompanyLogo()) ?>" alt="Logo" class="h-20 mt-2"></div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><strong>Ciudad:</strong> <?= htmlspecialchars($company->getCity()) ?></div>
                <div><strong>País:</strong> <?= htmlspecialchars($company->getCountry()) ?></div>
            </div>
        </fieldset>
        <!-- Datos de Contacto -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-user"></i> Datos de Contacto</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div><strong>Nombre:</strong> <?= htmlspecialchars($company->getContactFirstName()) ?></div>
                <div><strong>Apellido:</strong> <?= htmlspecialchars($company->getContactLastName()) ?></div>
                <div><strong>Teléfono Celular:</strong> <?= htmlspecialchars($company->getPhone()) ?></div>
                <div><strong>Correo Electrónico:</strong> <?= htmlspecialchars($company->getEmail()) ?></div>
                <div><strong>Ciudad:</strong> <?= htmlspecialchars($company->getCity()) ?></div>
                <div><strong>País:</strong> <?= htmlspecialchars($company->getCountry()) ?></div>
            </div>
        </fieldset>
        <!-- Datos de la Cuenta -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-id-card"></i> Datos para Registro de la Cuenta</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <?php if (!empty($eventUserRole)): ?>
                    <div><strong>Rol en el evento:</strong> <?= htmlspecialchars($eventUserRole) ?></div>
                <?php else: ?>
                    <div class="text-gray-500">No hay datos de usuario para este evento.</div>
                <?php endif; ?>
            </div>
        </fieldset>
        <!-- Datos de Acceso -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-id-card"></i> Datos de Acceso</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <?php if (!empty($eventUserEmail)): ?>
                    <div><strong>Email de acceso:</strong> <?= htmlspecialchars($eventUserEmail) ?></div>
                    <form action="<?= BASE_URL ?>/auth/change_password_event" method="POST" class="mt-2 space-y-2 max-w-xs">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($eventUserEmail) ?>">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                        <div>
                            <input type="password" name="new_password" class="form-control w-full" required placeholder="Nueva contraseña">
                        </div>
                        <div>
                            <input type="password" name="confirm_password" class="form-control w-full" required placeholder="Repetir nueva contraseña">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary w-full">Cambiar contraseña</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-gray-500">No hay datos de usuario para este evento.</div>
                <?php endif; ?>
            </div>
        </fieldset>
        <!-- Asistentes -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-users"></i> Asistentes Registrados
                <a href="<?= BASE_URL ?>/events/editParticipant/<?= (int)$event->getId() ?>/<?= !empty($assistants) ? (int)$assistants[0]['assistant_id'] : '' ?>" class="ml-2 btn btn-xs btn-warning" title="Editar asistentes"><i class="fas fa-edit"></i> Editar</a>
            </legend>
            <?php if (!empty($assistants)): ?>
                <?php foreach ($assistants as $i => $asist): ?>
                    <div class="mb-2 border-b pb-2 flex items-center justify-between">
                        <div>
                            <div><strong>Nombre:</strong> <?= htmlspecialchars($asist['first_name']) ?></div>
                            <div><strong>Apellido:</strong> <?= htmlspecialchars($asist['last_name']) ?></div>
                            <div><strong>Teléfono:</strong> <?= htmlspecialchars($asist['mobile_phone']) ?></div>
                            <div><strong>Email:</strong> <?= htmlspecialchars($asist['email']) ?></div>
                        </div>
                        <div>
                            <a href="<?= BASE_URL ?>/events/editParticipant/<?= (int)$event->getId() ?>/<?= (int)$asist['assistant_id'] ?>" class="btn btn-xs btn-warning ml-2" title="Editar participante"><i class="fas fa-edit"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/events/participants/<?= (int)$event->getId() ?>" class="btn btn-primary"><i class="fas fa-user-plus"></i> Registrar asistente</a>
            <?php endif; ?>
        </fieldset>
        <!-- Productos o Servicios de Interés -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-box"></i> Productos o Servicios de Interés
                <a href="<?= BASE_URL ?>/events/editRequirements/<?= (int)$event->getId() ?>/<?= (int)$company->getId() ?>" class="ml-2 btn btn-xs btn-warning" title="Editar requerimientos"><i class="fas fa-edit"></i> Editar</a>
            </legend>
            <?php
            $role = method_exists($company, 'getRole') ? $company->getRole() : ($eventUserRole ?? null);
            if ($role === 'supplier') {
                // Mostrar ofertas del proveedor
                $offers = $company->getOffers($company->getId());
                if (!empty($categories) && !empty($subcategories)) {
                    include(VIEW_DIR . '/events/partials/supplier_offers_readonly.php');
                } else {
                    echo '<div class="text-gray-500">No se especificaron productos o servicios de interés.</div>';
                }
            } else {
                // Mostrar requerimientos del comprador (comportamiento original)
                $reqBySub = [];
                if (!empty($requirements)) {
                    foreach ($requirements as $req) {
                        $reqBySub[$req['event_subcategory_id']] = $req;
                    }
                }
                if (!empty($categories) && !empty($subcategories)) {
                    include(VIEW_DIR . '/events/partials/requirements_readonly.php');
                } else {
                    echo '<div class="text-gray-500">No se especificaron productos o servicios de interés.</div>';
                }
            }
            ?>
        </fieldset>
        <!-- Días de Asistencia -->
        <fieldset class="card mb-4">
            <legend class="font-semibold flex items-center gap-2 mb-2"><i class="fas fa-calendar-alt"></i> Días de Asistencia
                <a href="<?= BASE_URL ?>/events/edit_days_attendance/<?= (int)$event->getId() ?>/<?= (int)$company->getId() ?>" class="ml-2 btn btn-xs btn-warning" title="Editar días de asistencia"><i class="fas fa-edit"></i> Editar</a>
            </legend>
            <?php if (!empty($attendanceDays)): ?>
                <ul class="list-disc pl-6">
                    <?php foreach ($attendanceDays as $day): ?>
                        <li><?= dateFromDatabase(is_array($day) && isset($day['date']) ? $day['date'] : $day) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-gray-500">No se especificaron días de asistencia.</div>
            <?php endif; ?>
        </fieldset>
    </div>
</div>
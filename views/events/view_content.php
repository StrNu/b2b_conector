<!-- Contenido limpio de events/view sin layout -->
<div class="content">
    <div class="content-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($event->getEventName()) ?></h1>
                <p class="text-gray-500 text-sm">Detalles y gestión del evento.</p>
            </div>
            <div class="actions flex gap-2">
                <a href="<?= BASE_URL ?>/events" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                <a href="<?= BASE_URL ?>/events/edit/<?= $event->getId() ?>\" class="btn btn-primary"><i class="fas fa-edit"></i> Editar</a>
                <a href="<?= BASE_URL ?>/events/report/<?= $event->getId() ?>" class="btn btn-info"><i class="fas fa-chart-bar"></i> Reporte</a>
            </div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['admin_credentials'])): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-lg"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-yellow-800 font-semibold mb-2">Credenciales del Administrador del Evento</h3>
                <p class="text-yellow-700 text-sm mb-3">El correo electrónico no se pudo enviar automáticamente. Por favor, proporcione manualmente las siguientes credenciales al administrador del evento:</p>
                <div class="bg-white border border-yellow-300 rounded p-3 font-mono text-sm">
                    <div class="grid grid-cols-1 gap-2">
                        <div><strong>Evento:</strong> <?= htmlspecialchars($_SESSION['admin_credentials']['event_name']) ?></div>
                        <div><strong>Email/Usuario:</strong> <?= htmlspecialchars($_SESSION['admin_credentials']['email']) ?></div>
                        <div><strong>Contraseña:</strong> <span class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($_SESSION['admin_credentials']['password']) ?></span></div>
                    </div>
                </div>
                <button onclick="this.parentElement.parentElement.parentElement.style.display='none'" class="mt-2 text-yellow-600 hover:text-yellow-800 text-sm underline">
                    <i class="fas fa-times"></i> Cerrar este mensaje
                </button>
            </div>
        </div>
    </div>
    <?php 
        unset($_SESSION['admin_credentials']); 
    ?>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Información del evento -->
        <div class="md:col-span-2 flex flex-col gap-6">
            <div class="event-view-card p-6 flex flex-col gap-2 bg-white rounded shadow">
                <h2 class="event-view-title flex items-center gap-2"><i class="fas fa-info-circle"></i> Información del Evento</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 event-view-section">
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-map-marker-alt text-blue-500"></i><span class="font-semibold">Sede:</span><span><?= htmlspecialchars($event->getVenue()) ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="far fa-calendar-alt text-blue-500"></i><span class="font-semibold">Fechas:</span><span><?= dateFromDatabase($event->getStartDate()) ?> - <?= dateFromDatabase($event->getEndDate()) ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-clock text-blue-500"></i><span class="font-semibold">Horario:</span><span><?= substr($event->getStartTime(), 0, 5) ?> - <?= substr($event->getEndTime(), 0, 5) ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-table text-blue-500"></i><span class="font-semibold">Mesas:</span><span><?= $event->getAvailableTables() ?></span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-stopwatch text-blue-500"></i><span class="font-semibold">Duración reunión:</span><span><?= $event->getMeetingDuration() ?> min</span></div>
                    <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-building text-blue-500"></i><span class="font-semibold">Empresa Organizadora:</span><span><?= !empty($event->getCompanyName()) ? htmlspecialchars($event->getCompanyName()) : '<em>No especificada</em>' ?></span></div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="event-view-card p-6 flex flex-col gap-2 bg-white rounded shadow">
            <h2 class="event-view-title mb-4 flex items-center gap-2"><i class="fas fa-chart-bar"></i> Estadísticas</h2>
            <div class="flex flex-col gap-2 event-view-section">
                <div class="flex items-center gap-2"><i class="fas fa-users text-blue-500"></i><span class="font-semibold">Participantes:</span><span><?= $participantsCount ?? 0 ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-building text-green-500"></i><span class="font-semibold">Compradores:</span><span><?= $buyerCompaniesCount ?? 0 ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-store text-red-500"></i><span class="font-semibold">Proveedores:</span><span><?= $supplierCompaniesCount ?? 0 ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-handshake text-blue-500"></i><span class="font-semibold">Matches:</span><span><?= $matchCount ?? 0 ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-calendar-check text-blue-500"></i><span class="font-semibold">Citas:</span><span><?= $scheduleCount ?? 0 ?></span></div>
            </div>
        </div>
    </div>
</div>
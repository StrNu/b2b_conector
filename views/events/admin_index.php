<!-- Vista de eventos para administradores normales -->
<div class="content">
    <div class="content-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1">Gestión de Eventos</h1>
                <p class="text-gray-500 text-sm">Administrar todos los eventos del sistema.</p>
            </div>
            <div class="actions flex gap-2">
                <a href="<?= BASE_URL ?>/events/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Evento
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de eventos -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <?php if (!empty($events)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fechas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sede</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estadísticas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-calendar-alt text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($event['event_name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: <?= $event['event_id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex flex-col">
                                        <span><?= formatDate($event['start_date']) ?></span>
                                        <span class="text-gray-500">a <?= formatDate($event['end_date']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($event['venue']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($event['is_active']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-col space-y-1">
                                        <span><i class="fas fa-building text-blue-500 w-4"></i> <?= $event['companies_count'] ?? 0 ?> empresas</span>
                                        <span><i class="fas fa-handshake text-green-500 w-4"></i> <?= $event['matches_count'] ?? 0 ?> matches</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="<?= BASE_URL ?>/events/view/<?= $event['event_id'] ?>" 
                                           class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/events/edit/<?= $event['event_id'] ?>" 
                                           class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/events/companies/<?= $event['event_id'] ?>" 
                                           class="text-green-600 hover:text-green-900" title="Empresas">
                                            <i class="fas fa-building"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/events/matches/<?= $event['event_id'] ?>" 
                                           class="text-purple-600 hover:text-purple-900" title="Matches">
                                            <i class="fas fa-handshake"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 mx-auto mb-4 text-gray-400">
                        <i class="fas fa-calendar-alt text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay eventos</h3>
                    <p class="text-gray-500 mb-6">Comienza creando tu primer evento.</p>
                    <a href="<?= BASE_URL ?>/events/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Primer Evento
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
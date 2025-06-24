<?php 
// Configurar variables para el header
$pageTitle = 'Agenda de Citas - ' . (isset($event) ? $event->getEventName() : 'Evento');
$moduleCSS = 'events';
$moduleJS = 'agenda';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Evento', 'url' => BASE_URL . '/events/view/' . $eventId],
    ['title' => 'Agenda']
];

includeAppropriateHeader([
    'pageTitle' => $pageTitle,
    'moduleCSS' => $moduleCSS,
    'moduleJS' => $moduleJS,
    'breadcrumbs' => $breadcrumbs
]); 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
<style>
.tabs-pure {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 1rem;
}
.tabs-pure a {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    color: #4b5563;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    font-weight: 500;
    transition: color 0.2s, border-color 0.2s;
}
.tabs-pure a:focus {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
.tabs-pure a.active {
    color: #1d4ed8;
    border-bottom: 2px solid #1d4ed8;
    font-weight: bold;
}
</style>
<div class="content">
    <div class="flex items-center justify-between mb-4">
        <a href="<?= BASE_URL ?>/events/view/<?= (int)$event->getId() ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Evento
        </a>
        <div>
            <button class="btn btn-primary" id="sendAllAgendasBtn">
                <i class="fas fa-paper-plane"></i> Enviar todas las agendas
            </button>
        </div>
    </div>
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Agendas de Citas por Empresa</h1>
    </div>
    <?php
    $tab = $_GET['tab'] ?? 'all';
    $all = $agendas['all'] ?? [];
    $buyers = $agendas['buyers'] ?? [];
    $suppliers = $agendas['suppliers'] ?? [];
    ?>
    <nav class="tabs-pure" aria-label="Agendas tabs">
        <a href="?tab=all" class="<?= $tab === 'all' ? 'active' : '' ?>">Todas</a>
        <a href="?tab=buyers" class="<?= $tab === 'buyers' ? 'active' : '' ?>">Compradores</a>
        <a href="?tab=suppliers" class="<?= $tab === 'suppliers' ? 'active' : '' ?>">Proveedores</a>
    </nav>
    <div class="tab-content">
        <?php if ($tab === 'all'): ?>
            <div id="tab-all">
                <?php
                $agendas = $all;
                include(VIEW_DIR . '/events/_all_schedules.php');
                ?>
            </div>
        <?php elseif ($tab === 'buyers'): ?>
            <div id="tab-buyers">
                <?php
                $agendas = $buyers;
                include(VIEW_DIR . '/events/_buyers_schedules.php');
                ?>
            </div>
        <?php elseif ($tab === 'suppliers'): ?>
            <div id="tab-suppliers">
                <?php
                $agendas = $suppliers;
                include(VIEW_DIR . '/events/_suppliers_schedules.php');
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php includeAppropriateFooter(); ?>

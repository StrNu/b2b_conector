<?php
session_start();
if (isset($_SESSION['event_id'])) {
    $eventId = $_SESSION['event_id'];
}
include(VIEW_DIR . '/shared/header.php'); ?>
<?php
// Debug temporal para ver el contenido de $customMatches
/*if (isset($customMatches)) {
    echo '<pre style="background:#fffbe6;border:1px solid #e6c200;padding:10px;">';
    echo '<b>DEBUG $customMatches:</b> ';
    var_dump($customMatches);
    echo '</pre>';
}
*/
//Si se necesita hacer debug de $unmatchedCompanies, descomentar este bloque

?>
<!-- Incluir CSS de tabs -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components/tabs.css">
<div class="content">
    <div class="content-header flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Matches del Evento</h1>
        <div class="flex items-center">
            <a href="<?= BASE_URL ?>/events/view/<?= isset($eventId) && is_numeric($eventId) && $eventId > 0 ? $eventId : (isset($_GET['event_id']) ? (int)$_GET['event_id'] : '') ?>" class="ml-2 bg-gray-200 text-gray-700 text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-gray-300 transition flex items-center gap-2">Regresar al evento</a>
        </div>
    </div>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>

    <!-- Tabs navigation -->
    <div class="tabs-component mb-6">
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="direct-matches" aria-selected="true" type="button">Matches encontrados</button>
            <button class="tab-btn" data-tab="potential-matches" aria-selected="false" type="button">Matches potenciales</button>
            <button class="tab-btn" data-tab="no-match-companies" aria-selected="false" type="button">Empresas por optimizar</button>
        </div>
        <div class="tab-panel active" id="direct-matches">
            <div class="bg-white p-4 rounded-xl shadow mb-6">
                <div class="flex items-center mb-4">
                    <input type="text" id="search-matches" class="form-input w-full max-w-xs mr-2" placeholder="Buscar por empresa..." data-autosearch="direct-matches-table">
                    <form method="post" action="<?= BASE_URL ?>/appointments/scheduleAll" id="schedule-all-form" class="ml-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                        <button type="submit" class="bg-green-600 text-white text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-green-700 transition flex items-center gap-2">
                            <span>Programar todo</span>
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>
                    </form>
                </div>
                <div class="table-responsive" id="direct-matches-table-container">
                <!-- La tabla se cargará aquí vía AJAX -->
                </div>
                <div id="direct-matches-pagination" class="mt-4"></div>
            </div>
        </div>
        <div class="tab-panel" id="potential-matches">
            <div class="flex items-center mb-4">
                <input type="text" id="search-potential-matches" class="form-input w-full max-w-xs mr-2" placeholder="Buscar en matches potenciales..." data-autosearch="potential-matches-table">
                <button id="btn-save-all-potential" class="bg-green-700 text-white px-4 py-2 rounded ml-2">Guardar todos los matches</button>
            </div>
            <div class="filters" id="potential-matches-filters"></div>
            <div id="potential-matches-table-container"></div>
            <div id="potential-matches-pagination" class="mt-4"></div>
        </div>
        <div class="tab-panel" id="no-match-companies">
            <div class="flex items-center mb-4">
                <input type="text" id="search-no-match-companies" class="form-input w-full max-w-xs mr-2" placeholder="Buscar empresa..." data-autosearch="no-match-companies-table">
            </div>
            <div id="no-match-companies-table-container"></div>
            <div id="no-match-companies-pagination" class="mt-4"></div>
        </div>
    </div>


<script>
// Variables PHP disponibles en JS
const eventId = '<?= isset($eventId) ? htmlspecialchars($eventId) : '' ?>';
const csrfToken = '<?= isset($csrfToken) ? htmlspecialchars($csrfToken) : '' ?>';

// Función para cargar matches confirmados
function loadDirectMatches() {
    const container = document.getElementById('direct-matches-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando matches directos...</div>';
    fetch('<?= BASE_URL ?>/matches/getConfirmedMatchesAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(eventId) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<div class="text-center text-red-500 py-4">Error al cargar matches: ' + (data.message || '') + '</div>';
            document.getElementById('direct-matches-pagination').innerHTML = '';
            return;
        }
        const matches = data.matches || [];
        let html = '<table class="table" id="direct-matches-table"><thead><tr>' +
            '<th>ID</th><th>Comprador</th><th>Proveedor</th><th>Score</th>' +
            '</tr></thead><tbody>';
        if (matches.length === 0) {
            html += '<tr><td colspan="4" class="text-center text-gray-400">No hay matches directos.</td></tr>';
        } else {
            matches.forEach(m => {
                html += `<tr>` +
                    `<td>${m.match_id ?? '-'}</td>` +
                    `<td>${m.buyer_name ?? '-'}</td>` +
                    `<td>${m.supplier_name ?? '-'}</td>` +
                    `<td>${m.match_strength ?? '-'}</td>` +
                    `</tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
        // Invocar paginación si hay filas
        if (matches.length > 0) {
            if (typeof pagination === 'function') {
                pagination('direct-matches-pagination', 'direct-matches-table', 10);
            }
        } else {
            document.getElementById('direct-matches-pagination').innerHTML = '';
        }
    })
    .catch(err => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar matches.</div>';
        document.getElementById('direct-matches-pagination').innerHTML = '';
    });
}

// Función para cargar matches potenciales
function loadPotentialMatches() {
    const container = document.getElementById('potential-matches-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando matches potenciales...';
    fetch('<?= BASE_URL ?>/matches/getPotentialMatchesAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(eventId) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<div class="text-center text-red-500 py-4">Error al cargar matches potenciales: ' + (data.message || '') + '</div>';
            document.getElementById('potential-matches-pagination').innerHTML = '';
            return;
        }
        const matches = data.matches || [];
        let html = '<table class="table" id="potential-matches-table"><thead><tr>' +
            '<th>Comprador</th><th>Proveedor</th><th>Score</th><th>Acciones</th>' +
            '</tr></thead><tbody>';
        if (matches.length === 0) {
            html += '<tr><td colspan="4" class="text-center text-gray-400">No hay matches potenciales.</td></tr>';
        } else {
            matches.forEach(m => {
                html += `<tr>` +
                    `<td>${m.buyer_name ?? '-'}</td>` +
                    `<td>${m.supplier_name ?? '-'}</td>` +
                    `<td>${m.match_strength ?? '-'}</td>` +
                    `<td><button class="btn btn-primary btn-xs btn-edit-potential-match" data-buyer-id="${m.buyer_id}" data-supplier-id="${m.supplier_id}" data-event-id="${eventId}">Editar match</button></td>` +
                    `</tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
        // Invocar paginación si hay filas
        if (matches.length > 0) {
            if (typeof pagination === 'function') {
                pagination('potential-matches-pagination', 'potential-matches-table', 10);
            }
        } else {
            document.getElementById('potential-matches-pagination').innerHTML = '';
        }
        // Inicializar eventos para los botones de editar
        document.querySelectorAll('.btn-edit-potential-match').forEach(btn => {
            btn.addEventListener('click', function() {
                const buyerId = this.getAttribute('data-buyer-id');
                const supplierId = this.getAttribute('data-supplier-id');
                const eventId = this.getAttribute('data-event-id');
                // Aquí puedes llamar a la función para abrir el modal y cargar los datos
                if (typeof openEditPotentialMatchModal === 'function') {
                    openEditPotentialMatchModal({buyerId, supplierId, eventId});
                }
            });
        });
    })
    .catch(err => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar matches potenciales.</div>';
        document.getElementById('potential-matches-pagination').innerHTML = '';
    });
}

// Función para cargar empresas sin match
function loadUnmatchedCompanies() {
    const container = document.getElementById('no-match-companies-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando empresas sin match...';
    fetch('<?= BASE_URL ?>/matches/getUnmatchedCompaniesAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(eventId) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<div class="text-center text-red-500 py-4">Error al cargar empresas: ' + (data.message || '') + '</div>';
            document.getElementById('no-match-companies-pagination').innerHTML = '';
            return;
        }
        const companies = data.companies || [];
        let html = '<table class="table" id="no-match-companies-table"><thead><tr>' +
            '<th>ID</th><th>Nombre</th><th>Tipo</th>' +
            '</tr></thead><tbody>';
        if (companies.length === 0) {
            html += '<tr><td colspan="3" class="text-center text-gray-400">No hay empresas por optimizar.</td></tr>';
        } else {
            companies.forEach(c => {
                html += `<tr>` +
                    `<td>${c.id ?? '-'}</td>` +
                    `<td>${c.name ?? '-'}</td>` +
                    `<td>${c.type ?? '-'}</td>` +
                    `</tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
        // Invocar paginación si hay filas
        if (companies.length > 0) {
            if (typeof pagination === 'function') {
                pagination('no-match-companies-pagination', 'no-match-companies-table', 10);
            }
        } else {
            document.getElementById('no-match-companies-pagination').innerHTML = '';
        }
    })
    .catch(err => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar empresas.</div>';
        document.getElementById('no-match-companies-pagination').innerHTML = '';
    });
}

// Detectar cambio de pestaña y cargar datos solo cuando se selecciona
window.addEventListener('DOMContentLoaded', function() {
    loadDirectMatches(); // Carga inicial
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = btn.getAttribute('data-tab');
            if (tab === 'direct-matches') loadDirectMatches();
            if (tab === 'potential-matches') loadPotentialMatches();
            if (tab === 'no-match-companies') loadUnmatchedCompanies();
        });
    });
});
</script>
<!-- Incluir el JS de paginación al final -->
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
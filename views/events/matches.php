<?php
session_start();
if (isset($_SESSION['event_id'])) {
    $eventId = $_SESSION['event_id'];
}
include(VIEW_DIR . '/shared/header.php'); ?>
<?php
// DEBUG: Mostrar POST y GET en la vista para matches confirmados
if (isset($_POST) && count($_POST)) {
    echo '<pre style="background:#ffeeba;color:#856404;padding:8px;font-size:12px;">DEBUG $_POST:<br>';
    var_dump($_POST);
    echo '</pre>';
}
if (isset($_GET) && count($_GET)) {
    echo '<pre style="background:#ffeeba;color:#856404;padding:8px;font-size:12px;">DEBUG $_GET:<br>';
    var_dump($_GET);
    echo '</pre>';
}
?>
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
    <script>
    document.getElementById('generate-matches-form').addEventListener('submit', function() {
        document.getElementById('generate-matches-btn-text').textContent = 'Generando...';
        document.getElementById('generate-matches-spinner').classList.remove('hidden');
        document.getElementById('generate-matches-btn').setAttribute('disabled', 'disabled');
    });
    </script>
    <?php include(VIEW_DIR . '/shared/notifications.php'); ?>

    <!-- Tabs navigation -->
    <div class="tabs-component mb-6">
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="direct-matches" aria-selected="true" type="button">Matches encontrados</button>
            <button class="tab-btn" data-tab="potential-matches" aria-selected="false" type="button">Matches potenciales</button>
            <button class="tab-btn" data-tab="no-match-companies" aria-selected="false" type="button">Empresas por optimizar</button>
        </div>
        <div class="tab-panel active" id="direct-matches">
            <div class="flex items-center justify-end mb-4 gap-2">
                <form method="post" action="<?= BASE_URL ?>/matches/generateAll" id="generate-matches-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                    <button type="submit" class="bg-indigo-600 text-white text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-indigo-700 transition flex items-center gap-2" id="generate-matches-btn">
                        <span id="generate-matches-btn-text">Generar matches</span>
                        <svg id="generate-matches-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                    </button>
                </form>
                <form method="post" action="<?= BASE_URL ?>/appointments/scheduleAll" id="schedule-all-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                    <button type="submit" class="bg-green-600 text-white text-sm px-5 py-2 rounded-full font-semibold shadow hover:bg-green-700 transition flex items-center gap-2">
                        <span>Programar todo</span>
                        <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                </form>
            </div>
            <div class="bg-white p-4 rounded-xl shadow mb-6">
                <div class="flex items-center mb-4">
                    <input type="text" id="search-matches" class="form-input w-full max-w-xs mr-2" placeholder="Buscar por empresa...">
                    <button id="filter-direct-matches-btn" type="button" class="bg-blue-600 text-white text-sm px-4 py-2 rounded ml-2">Buscar matches confirmados</button>
                </div>
                <div class="table-responsive" id="direct-matches-table-container">
                <!-- La tabla se cargará aquí vía AJAX -->
                </div>
            </div>
        </div>
        <div class="tab-panel" id="potential-matches">
            <!-- Aquí se cargará la tabla de matches potenciales vía AJAX -->
            <div class="filters" id="potential-matches-filters"></div>
            <div id="potential-matches-table-container"></div>
        </div>
        <div class="tab-panel" id="no-match-companies">
            <!-- Aquí se cargará la tabla de empresas sin match vía AJAX -->
            <div id="no-match-companies-table-container"></div>
        </div>
    </div>
    <script>
// Make eventId and csrfToken available globally
window.eventId = '<?= htmlspecialchars($eventId) ?>';
window.csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
</script>
<!-- Incluir JS de tabs -->
<script src="<?= BASE_URL ?>/assets/js/components/tabs.js"></script>
<script src="<?= BASE_URL ?>/assets/js/autosearch.js"></script>
<script src="<?= BASE_URL ?>/views/events/matches_actions.js"></script>
<script>
// Eliminar lógica antigua de tabs y usar el callback de tabs.js para cargar AJAX
function loadDirectMatches() {
    const container = document.getElementById('direct-matches-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando matches directos...</div>';
    fetch('<?= BASE_URL ?>/matches/getConfirmedMatchesAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(window.eventId) + '&csrf_token=' + encodeURIComponent(window.csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<div class="text-center text-red-500 py-4">Error al cargar matches directos</div>';
            return;
        }
        const matches = data.matches || [];
        let searchValue = document.getElementById('search-matches').value.toLowerCase();
        let filtered = matches.filter(m =>
            m.buyer_name.toLowerCase().includes(searchValue) ||
            m.supplier_name.toLowerCase().includes(searchValue)
        );
        let html = '<table class="table"><thead><tr>' +
            '<th>match_id</th>' +
            '<th>buyer_id</th>' +
            '<th>supplier_id</th>' +
            '<th>event_id</th>' +
            '<th>match_strength</th>' +
            '<th>created_at</th>' +
            '<th>status</th>' +
            '<th>matched_categories</th>' +
            '<th>programed</th>' +
            '<th>match_level</th>' +
            '<th>buyer_subcategories</th>' +
            '<th>supplier_subcategories</th>' +
            '<th>buyer_dates</th>' +
            '<th>supplier_dates</th>' +
            '<th>buyer_keywords</th>' +
            '<th>supplier_keywords</th>' +
            '<th>buyer_description</th>' +
            '<th>supplier_description</th>' +
            '<th>reason</th>' +
            '<th>keywords_match</th>' +
            '<th>coincidence_of_dates</th>' +
            '<th>Acciones</th>' +
            '</tr></thead><tbody>';
        if (filtered.length === 0) {
            html += '<tr><td colspan="22" class="text-center text-gray-400">No hay matches directos.</td></tr>';
        } else {
            filtered.forEach(m => {
                function formatField(val) {
                    if (val === null || val === undefined || val === '') return '-';
                    if (Array.isArray(val)) return val.length ? val.join('<br>') : '-';
                    try {
                        let parsed = JSON.parse(val);
                        if (Array.isArray(parsed)) return parsed.length ? parsed.join('<br>') : '-';
                        if (typeof parsed === 'object') return Object.values(parsed).join('<br>');
                    } catch (e) {}
                    return (typeof val === 'string') ? val.replace(/\n/g, '<br>') : val;
                }
                html += `<tr>` +
                    `<td>${m.match_id ?? '-'}</td>` +
                    `<td>${m.buyer_id ?? '-'}</td>` +
                    `<td>${m.supplier_id ?? '-'}</td>` +
                    `<td>${m.event_id ?? '-'}</td>` +
                    `<td>${formatField(m.match_strength)}</td>` +
                    `<td>${m.created_at ?? '-'}</td>` +
                    `<td>${m.status ?? '-'}</td>` +
                    `<td>${formatField(m.matched_categories)}</td>` +
                    `<td>${m.programed ?? '-'}</td>` +
                    `<td>${m.match_level ?? '-'}</td>` +
                    `<td>${formatField(m.buyer_subcategories)}</td>` +
                    `<td>${formatField(m.supplier_subcategories)}</td>` +
                    `<td>${formatField(m.buyer_dates)}</td>` +
                    `<td>${formatField(m.supplier_dates)}</td>` +
                    `<td>${formatField(m.buyer_keywords)}</td>` +
                    `<td>${formatField(m.supplier_keywords)}</td>` +
                    `<td>${formatField(m.buyer_description)}</td>` +
                    `<td>${formatField(m.supplier_description)}</td>` +
                    `<td>${formatField(m.reason)}</td>` +
                    `<td>${formatField(m.keywords_match)}</td>` +
                    `<td>${formatField(m.coincidence_of_dates)}</td>` +
                    `<td><button class='btn-schedule-match text-green-600 underline mr-2' data-match-id='${m.match_id}'>Programar</button><button class='btn-delete-match text-red-600 underline' data-match-id='${m.match_id}'>Eliminar</button></td>` +
                    `</tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
    })
    .catch(() => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar matches directos</div>';
    });
}

function loadPotentialMatches() {
    const container = document.getElementById('potential-matches-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando matches potenciales...</div>';
    fetch('<?= BASE_URL ?>/matches/getPotentialMatchesAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(window.eventId) + '&csrf_token=' + encodeURIComponent(window.csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<div class="text-center text-red-500 py-4">Error al cargar matches potenciales</div>';
            return;
        }
        const matches = data.matches || [];
        let html = '<table class="table"><thead><tr>' +
            '<th>Comprador</th><th>Proveedor</th><th>Razón</th><th>Fechas comprador</th><th>Fechas proveedor</th><th>Acciones</th></tr></thead><tbody>';
        if (matches.length === 0) {
            html += '<tr><td colspan="6" class="text-center text-gray-400">No hay matches potenciales.</td></tr>';
        } else {
            matches.forEach(m => {
                html += `<tr><td>${m.buyer_name || '-'}</td><td>${m.supplier_name || '-'}</td><td>${m.reason || '-'}</td><td>${m.buyer_dates || '-'}</td><td>${m.supplier_dates || '-'}</td><td><!-- acciones --></td></tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
    })
    .catch(() => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar matches potenciales</div>';
    });
}

function loadNoMatchCompanies() {
    const container = document.getElementById('no-match-companies-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando empresas sin match...</div>';
    fetch('<?= BASE_URL ?>/matches/getUnmatchedCompaniesAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(window.eventId) + '&csrf_token=' + encodeURIComponent(window.csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<div class="text-center text-red-500 py-4">Error al cargar empresas sin match</div>';
            return;
        }
        const companies = data.companies || [];
        let html = '<table class="table"><thead><tr>' +
            '<th>Empresa</th><th>Rol</th><th>Categorías</th><th>Subcategorías</th><th>Palabras clave</th><th>Descripción</th><th>Contacto</th><th>Email</th><th>Teléfono</th></tr></thead><tbody>';
        if (companies.length === 0) {
            html += '<tr><td colspan="9" class="text-center text-gray-400">No hay empresas sin match.</td></tr>';
        } else {
            companies.forEach(c => {
                html += `<tr>
                    <td>${c.company_name}</td>
                    <td>${c.role === 'buyer' ? 'Comprador' : 'Proveedor'}</td>
                    <td>${c.categories || '-'}</td>
                    <td>${c.subcategories || '-'}</td>
                    <td>${c.keywords || '-'}</td>
                    <td>${c.description || '-'}</td>
                    <td>${c.contact_name || '-'}</td>
                    <td>${c.contact_email || '-'}</td>
                    <td>${c.contact_phone || '-'}</td>
                </tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
    })
    .catch(() => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar empresas sin match</div>';
    });
}

// Integrate with tabs.js: load the correct table on tab switch
window.addEventListener('DOMContentLoaded', function() {
    // Initial load for the first tab
    loadDirectMatches();
    // Listen for tab changes
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            if (tab === 'direct-matches') loadDirectMatches();
            else if (tab === 'potential-matches') loadPotentialMatches();
            else if (tab === 'no-match-companies') loadNoMatchCompanies();
        });
    });
    const searchInput = document.getElementById('search-matches');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            loadDirectMatches();
        });
        if (window.autosearch) {
            window.autosearch(searchInput, {
                minLength: 2,
                source: function(term, suggest) {
                    // Opcional: implementar sugerencias AJAX si se desea
                    suggest([]);
                }
            });
        }
    }
    document.getElementById('filter-direct-matches-btn').addEventListener('click', function() {
        document.getElementById('search-matches').value = '';
        loadDirectMatches();
    });
});
</script>
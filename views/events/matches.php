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
            <div class="flex items-center mb-4 justify-between">
                <input type="text" id="search-potential-matches" class="form-input w-full max-w-xs mr-2" placeholder="Buscar en matches potenciales..." data-autosearch="potential-matches-table">
                <div class="flex items-center gap-2">
                    <select id="filter-match-reason" class="form-input max-w-xs">
                        <option value="">Filtrar por Match reason</option>
                    </select>
                    <button id="btn-save-all-potential" class="btn btn-primary ml-2">Guardar todos los matches</button>
                </div>
            </div>
            <div class="filters" id="potential-matches-filters"></div>
            <div id="potential-matches-table-container"></div>
            <div id="potential-matches-pagination" class="mt-4"></div>
        </div>
        <div class="tab-panel" id="no-match-companies">
            <div class="flex items-center mb-4">
                <input type="text" id="search-no-match-companies" class="form-input w-full max-w-xs mr-2" placeholder="Buscar empresa..." data-autosearch="no-match-companies-table">
                <details class="ml-4">
                    <summary class="cursor-pointer bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                        💡 Sugerencias de optimización
                    </summary>
                    <div class="mt-4 bg-white border border-gray-200 rounded-lg shadow-lg p-6 min-w-[800px]">
                        <div id="optimization-suggestions-content" class="grid grid-cols-2 gap-6">
                            <div class="text-center text-gray-400 py-4 col-span-2">
                                <button id="load-optimization-suggestions" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                    Cargar sugerencias de optimización
                                </button>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
            <div id="no-match-companies-table-container"></div>
            <div id="no-match-companies-pagination" class="mt-4"></div>
        </div>
    </div>


<script>
// Variables PHP disponibles en JS
const eventId = '<?= isset($eventId) ? htmlspecialchars($eventId) : '' ?>';
const csrfToken = '<?= isset($csrfToken) ? htmlspecialchars($csrfToken) : '' ?>';

// Variables globales para el modal
window.csrfToken = csrfToken;
window.BASE_URL = '<?= BASE_URL ?>';
window.pmSupplierId = null;
window.pmEventId = null;
window.pmBuyerId = null;

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
            '<th>ID</th><th>Comprador</th><th>Proveedor</th><th>Score</th><th>Acciones</th>' +
            '</tr></thead><tbody>';
        if (matches.length === 0) {
            html += '<tr><td colspan="5" class="text-center text-gray-400">No hay matches directos.</td></tr>';
        } else {
            matches.forEach(m => {
                html += `<tr>` +
                    `<td>${m.match_id ?? '-'}</td>` +
                    `<td>${m.buyer_name ?? '-'}</td>` +
                    `<td>${m.supplier_name ?? '-'}</td>` +
                    `<td>${m.match_strength ?? '-'}</td>` +
                    `<td>
                        <button class="btn btn-success btn-xs btn-schedule-appointment" 
                                data-match-id="${m.match_id}" 
                                data-buyer-id="${m.buyer_id}" 
                                data-supplier-id="${m.supplier_id}" 
                                data-event-id="${eventId}"
                                data-buyer-name="${m.buyer_name ?? ''}"
                                data-supplier-name="${m.supplier_name ?? ''}"
                                data-coincidence-dates="${m.coincidence_of_dates ?? ''}"
                                title="Programar cita para este match">
                            <i class="fas fa-calendar-plus"></i> Programar cita
                        </button>
                    </td>` +
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
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando matches potenciales...</div>';
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
            '<th>Comprador</th><th>Proveedor</th><th>Score</th><th>Strength Match</th><th>Match reason</th><th>Acciones</th>' +
            '</tr></thead><tbody>';
        if (matches.length === 0) {
            html += '<tr><td colspan="6" class="text-center text-gray-400">No hay matches potenciales.</td></tr>';
        } else {
            matches.forEach(m => {
                // Score
                const scoreVal = m.match_strength !== undefined && m.match_strength !== null ? parseFloat(m.match_strength) : null;
                const score = scoreVal !== null ? scoreVal.toFixed(2) : '-';
                let scoreBar = '-';
                if (scoreVal !== null) {
                    scoreBar = `<div style=\"min-width:90px;\">`
                        + `<div class=\"w-full bg-gray-200 rounded h-4\">`
                        + `<div class=\"bg-green-500 h-4 rounded\" style=\"width:${scoreVal}%\"></div>`
                        + `</div>`
                        + `<div class=\"text-xs text-gray-700 mt-1 text-center\">${score}%</div>`
                        + `</div>`;
                }
                // Strength Match
                const strengthVal = m.strength_match !== undefined && m.strength_match !== null ? parseFloat(m.strength_match) : null;
                const strength = strengthVal !== null ? strengthVal.toFixed(2) : '-';
                let strengthBar = '-';
                if (strengthVal !== null) {
                    strengthBar = `<div style=\"min-width:90px;\">`
                        + `<div class=\"w-full bg-gray-200 rounded h-4\">`
                        + `<div class=\"bg-blue-500 h-4 rounded\" style=\"width:${strengthVal}%\"></div>`
                        + `</div>`
                        + `<div class=\"text-xs text-gray-700 mt-1 text-center\">${strength}%</div>`
                        + `</div>`;
                }
                const reason = m.reason ?? '-';
                html += `<tr>` +
                    `<td>${m.buyer_name ?? '-'}</td>` +
                    `<td>${m.supplier_name ?? '-'}</td>` +
                    `<td>${scoreBar}</td>` +
                    `<td>${strengthBar}</td>` +
                    `<td>${reason}</td>` +
                    `<td><button class=\"btn btn-primary btn-xs btn-edit-potential-match\" data-buyer-id=\"${m.buyer_id}\" data-supplier-id=\"${m.supplier_id}\" data-event-id=\"${eventId}\">Editar match</button></td>` +
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
                
                console.log('Botón editar clicked:', {buyerId, supplierId, eventId});
                
                // Llamar a la función del modal con los datos correctos
                openEditPotentialMatchModal({buyerId, supplierId, eventId});
            });
        });
        // Poblar filtro de Match reason
        const reasonSet = new Set();
        matches.forEach(m => {
            if (m.reason) reasonSet.add(m.reason);
        });
        const filterSelect = document.getElementById('filter-match-reason');
        if (filterSelect) {
            filterSelect.innerHTML = '<option value="">Filtrar por Match reason</option>';
            Array.from(reasonSet).forEach(reason => {
                filterSelect.innerHTML += `<option value="${reason}">${reason}</option>`;
            });
            filterSelect.onchange = function() {
                const val = this.value;
                const rows = document.querySelectorAll('#potential-matches-table tbody tr');
                rows.forEach(row => {
                    if (!val || row.children[4].textContent === val) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            };
        }
    })
    .catch(err => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar matches potenciales.</div>';
        document.getElementById('potential-matches-pagination').innerHTML = '';
    });
}

// Función para cargar empresas sin match
function loadUnmatchedCompanies() {
    console.log('loadUnmatchedCompanies() ejecutándose...');
    const container = document.getElementById('no-match-companies-table-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-4">Cargando empresas sin match...</div>';
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
            '<th>ID</th><th>Nombre</th><th>Tipo</th><th>Acciones</th>' +
            '</tr></thead><tbody>';
        if (companies.length === 0) {
            html += '<tr><td colspan="4" class="text-center text-gray-400">No hay empresas por optimizar.</td></tr>';
        } else {
            companies.forEach(c => {
                html += `<tr>` +
                    `<td>${c.id ?? '-'}</td>` +
                    `<td>${c.name ?? '-'}</td>` +
                    `<td>${c.type ?? '-'}</td>` +
                    `<td><button class="btn btn-success btn-xs btn-optimize-company" data-company-id="${c.id}" data-company-type="${c.type}">Optimizar</button></td>` +
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
        
        // Agregar event listeners para botones "Optimizar"
        const optimizeButtons = document.querySelectorAll('.btn-optimize-company');
        console.log('Botones Optimizar encontrados:', optimizeButtons.length);
        
        optimizeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const companyId = this.getAttribute('data-company-id');
                const companyType = this.getAttribute('data-company-type');
                
                console.log('Event listener individual - Optimizar clicked:', {companyId, companyType});
                // Abrir modal de optimización
                openOptimizeCompanyModal(companyId, companyType);
            });
        });
    })
    .catch(err => {
        container.innerHTML = '<div class="text-center text-red-500 py-4">Error de red al cargar empresas.</div>';
        document.getElementById('no-match-companies-pagination').innerHTML = '';
    });
}

// Mostrar y cerrar el modal de edición de match potencial
function openEditPotentialMatchModal({buyerId, supplierId, eventId}) {
    console.log('openEditPotentialMatchModal llamada con:', {buyerId, supplierId, eventId});
    
    // Validar parámetros
    if (!buyerId || !supplierId || !eventId) {
        console.error('Faltan parámetros requeridos:', {buyerId, supplierId, eventId});
        alert('Error: Faltan datos para abrir el modal de edición.');
        return;
    }
    
    // Guardar supplierId y eventId en variables globales para el submit
    window.pmSupplierId = supplierId;
    window.pmEventId = eventId;
    window.pmBuyerId = buyerId;
    
    const modal = document.getElementById('potentialMatchModal');
    if (!modal) {
        console.error('Modal potentialMatchModal no encontrado');
        alert('Error: Modal no encontrado en la página.');
        return;
    }
    
    // Limpiar campos
    const cleanField = (id, isInput = false) => {
        const el = document.getElementById(id);
        if (el) {
            if (isInput) el.value = '';
            else el.textContent = '';
        }
    };
    
    cleanField('pm-buyer-company');
    cleanField('pm-buyer-requirements');
    cleanField('pm-buyer-description');
    cleanField('pm-buyer-keywords');
    cleanField('pm-buyer-attendance');
    cleanField('pm-supplier-company');
    cleanField('pm-supplier-offers');
    cleanField('pm-supplier-description', true);
    cleanField('pm-supplier-keywords', true);
    
    const attendanceList = document.getElementById('pm-supplier-attendance-list');
    if (attendanceList) attendanceList.innerHTML = '';
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    
    console.log('Modal mostrado, cargando datos...');
    // Cargar datos del comprador
    fetch(`${window.BASE_URL}/matches/getCompanyPotentialMatchesDetailAjax`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `company_id=${encodeURIComponent(buyerId)}&event_id=${encodeURIComponent(eventId)}`
    })
    .then(res => res.json())
    .then(data => {
        console.log('[DEBUG] Buyer data received:', data);
        if (data.success && data.data && data.data.company) {
            const c = data.data.company;
            document.getElementById('pm-buyer-company').textContent = c.company_name || '-';
            
            // Mostrar requirements para buyers
            const requirementsEl = document.getElementById('pm-buyer-requirements');
            console.log('[DEBUG] Requirements data:', data.data.requirements);
            if (Array.isArray(data.data.requirements) && data.data.requirements.length > 0) {
                const reqList = data.data.requirements.map(req => {
                    console.log('[DEBUG] Processing requirement:', req);
                    return `${req.event_subcategory_name} → ${req.event_category_name}`;
                }).join('<br>');
                requirementsEl.innerHTML = reqList;
                console.log('[DEBUG] Requirements HTML set:', reqList);
            } else {
                requirementsEl.textContent = '-';
                console.log('[DEBUG] No requirements found or empty array');
            }
            
            document.getElementById('pm-buyer-description').textContent = c.description || '-';
            if (Array.isArray(c.keywords)) {
                document.getElementById('pm-buyer-keywords').textContent = c.keywords.join(', ');
            } else {
                document.getElementById('pm-buyer-keywords').textContent = c.keywords || '-';
            }
            document.getElementById('pm-buyer-attendance').textContent = (data.data.attendance_days && data.data.attendance_days.length) ? data.data.attendance_days.join(', ') : '-';
        } else {
            document.getElementById('pm-buyer-company').textContent = 'No encontrado';
        }
    });
    // Cargar datos del proveedor
    fetch(`${window.BASE_URL}/matches/getCompanyPotentialMatchesDetailAjax`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `company_id=${encodeURIComponent(supplierId)}&event_id=${encodeURIComponent(eventId)}`
    })
    .then(res => res.json())
    .then(data => {
        console.log('[DEBUG] Supplier data received:', data);
        if (data.success && data.data && data.data.company) {
            const c = data.data.company;
            document.getElementById('pm-supplier-company').textContent = c.company_name || '-';
            
            // Mostrar supplier_offers para suppliers
            const offersEl = document.getElementById('pm-supplier-offers');
            console.log('[DEBUG] Supplier offers data:', data.data.supplier_offers);
            if (Array.isArray(data.data.supplier_offers) && data.data.supplier_offers.length > 0) {
                const offersList = data.data.supplier_offers.map(offer => {
                    console.log('[DEBUG] Processing offer:', offer);
                    return `${offer.event_subcategory_name} → ${offer.event_category_name}`;
                }).join('<br>');
                offersEl.innerHTML = offersList;
                console.log('[DEBUG] Supplier offers HTML set:', offersList);
            } else {
                offersEl.textContent = '-';
                console.log('[DEBUG] No supplier offers found or empty array');
            }
            
            document.getElementById('pm-supplier-description').value = c.description || '';
            if (Array.isArray(c.keywords)) {
                document.getElementById('pm-supplier-keywords').value = c.keywords.join(', ');
            } else {
                document.getElementById('pm-supplier-keywords').value = c.keywords || '';
            }
            // Attendance Days como inputs type=date editables
            const attList = document.getElementById('pm-supplier-attendance-list');
            attList.innerHTML = '';
            if (data.data.attendance_days && data.data.attendance_days.length) {
                data.data.attendance_days.forEach((date, idx) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex items-center gap-2 mb-1';
                    const input = document.createElement('input');
                    input.type = 'date';
                    input.className = 'form-control datepicker';
                    input.value = date;
                    input.name = `supplier_attendance_dates[]`;
                    // Botón eliminar
                    const btnDel = document.createElement('button');
                    btnDel.type = 'button';
                    btnDel.className = 'text-red-500 text-xs px-2';
                    btnDel.innerHTML = 'Eliminar';
                    btnDel.onclick = function() { wrapper.remove(); };
                    wrapper.appendChild(input);
                    wrapper.appendChild(btnDel);
                    attList.appendChild(wrapper);
                });
            } else {
                attList.innerHTML = '<span class="text-gray-400">-</span>';
            }
            // Inicializar datepicker si aplica
            if (typeof addDatePicker === 'function') {
                addDatePicker();
            }
        } else {
            document.getElementById('pm-supplier-company').textContent = 'No encontrado';
        }
    });
}

function closePotentialMatchModal() {
    const modal = document.getElementById('potentialMatchModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    // Limpiar variables globales
    window.pmSupplierId = null;
    window.pmEventId = null;
    window.pmBuyerId = null;
    
    console.log('Modal cerrado');
}

// Función para cargar sugerencias de optimización
function loadOptimizationSuggestions() {
    const content = document.getElementById('optimization-suggestions-content');
    content.innerHTML = '<div class="text-center text-gray-400 py-4 col-span-2">Cargando sugerencias...</div>';
    
    fetch('<?= BASE_URL ?>/matches/getOptimizationSuggestionsAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(eventId) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            content.innerHTML = '<div class="text-center text-red-500 py-4 col-span-2">Error: ' + (data.message || '') + '</div>';
            return;
        }
        
        const suggestions = data.suggestions;
        let html = '';
        
        // Columna 1: Buyers
        html += '<div class="space-y-4">';
        html += '<h3 class="text-lg font-semibold text-gray-800 mb-3">📈 Tendencias de Compradores</h3>';
        
        // Keywords más populares de buyers
        if (Object.keys(suggestions.buyer_keywords).length > 0) {
            html += '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">';
            html += '<h4 class="font-medium text-blue-800 mb-2"><i class="fas fa-tags"></i> Keywords más populares</h4>';
            html += '<div class="flex flex-wrap gap-2">';
            Object.entries(suggestions.buyer_keywords).forEach(([keyword, count]) => {
                html += `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">${keyword} (${count})</span>`;
            });
            html += '</div></div>';
        }
        
        // Palabras en descripciones de buyers
        if (Object.keys(suggestions.buyer_description_words).length > 0) {
            html += '<div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">';
            html += '<h4 class="font-medium text-indigo-800 mb-2"><i class="fas fa-comment-alt"></i> Palabras frecuentes en descripciones</h4>';
            html += '<div class="flex flex-wrap gap-2">';
            Object.entries(suggestions.buyer_description_words).slice(0, 8).forEach(([word, count]) => {
                html += `<span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">${word} (${count})</span>`;
            });
            html += '</div></div>';
        }
        
        // Requirements más populares
        if (suggestions.popular_requirements.length > 0) {
            html += '<div class="bg-green-50 border border-green-200 rounded-lg p-4">';
            html += '<h4 class="font-medium text-green-800 mb-2"><i class="fas fa-list-check"></i> Requirements más demandados</h4>';
            html += '<div class="space-y-1">';
            suggestions.popular_requirements.slice(0, 5).forEach(req => {
                html += `<div class="text-sm text-green-700">${req.subcategory} → ${req.category} <span class="bg-green-200 text-green-800 text-xs px-2 py-0.5 rounded-full ml-2">${req.count}</span></div>`;
            });
            html += '</div></div>';
        }
        
        html += '</div>';
        
        // Columna 2: Suppliers
        html += '<div class="space-y-4">';
        html += '<h3 class="text-lg font-semibold text-gray-800 mb-3">🏭 Tendencias de Proveedores</h3>';
        
        // Keywords más populares de suppliers
        if (Object.keys(suggestions.supplier_keywords).length > 0) {
            html += '<div class="bg-orange-50 border border-orange-200 rounded-lg p-4">';
            html += '<h4 class="font-medium text-orange-800 mb-2"><i class="fas fa-tags"></i> Keywords más populares</h4>';
            html += '<div class="flex flex-wrap gap-2">';
            Object.entries(suggestions.supplier_keywords).forEach(([keyword, count]) => {
                html += `<span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">${keyword} (${count})</span>`;
            });
            html += '</div></div>';
        }
        
        // Palabras en descripciones de suppliers
        if (Object.keys(suggestions.supplier_description_words).length > 0) {
            html += '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
            html += '<h4 class="font-medium text-red-800 mb-2"><i class="fas fa-comment-alt"></i> Palabras frecuentes en descripciones</h4>';
            html += '<div class="flex flex-wrap gap-2">';
            Object.entries(suggestions.supplier_description_words).slice(0, 8).forEach(([word, count]) => {
                html += `<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">${word} (${count})</span>`;
            });
            html += '</div></div>';
        }
        
        // Supplier offers más populares
        if (suggestions.popular_supplier_offers.length > 0) {
            html += '<div class="bg-purple-50 border border-purple-200 rounded-lg p-4">';
            html += '<h4 class="font-medium text-purple-800 mb-2"><i class="fas fa-briefcase"></i> Ofertas más comunes</h4>';
            html += '<div class="space-y-1">';
            suggestions.popular_supplier_offers.slice(0, 5).forEach(offer => {
                html += `<div class="text-sm text-purple-700">${offer.subcategory} → ${offer.category} <span class="bg-purple-200 text-purple-800 text-xs px-2 py-0.5 rounded-full ml-2">${offer.count}</span></div>`;
            });
            html += '</div></div>';
        }
        
        html += '</div>';
        
        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-red-500 py-4 col-span-2">Error de red al cargar sugerencias.</div>';
    });
}

// Función para abrir modal de optimización de empresa
function openOptimizeCompanyModal(companyId, companyType) {
    console.log('openOptimizeCompanyModal llamada con:', {companyId, companyType});
    
    const modal = document.getElementById('optimizeCompanyModal');
    if (!modal) {
        console.error('Modal optimizeCompanyModal no encontrado');
        console.log('Modales disponibles:', document.querySelectorAll('.modal'));
        return;
    }
    
    console.log('Modal encontrado:', modal);
    
    // Establecer datos básicos
    document.getElementById('optimize-company-id').value = companyId;
    document.getElementById('optimize-event-id').value = eventId;
    document.getElementById('optimize-company-role').value = companyType;
    
    // Limpiar campos
    document.getElementById('optimize-description').value = '';
    document.getElementById('optimize-keywords').value = '';
    document.getElementById('optimize-company-name').textContent = 'Cargando...';
    document.getElementById('optimize-company-type').textContent = companyType === 'buyer' ? 'Comprador' : 'Proveedor';
    
    // Configurar botón de editar requerimientos/ofertas
    const editBtn = document.getElementById('edit-requirements-offers-btn');
    const editBtnText = document.getElementById('edit-btn-text');
    
    if (companyType === 'buyer') {
        editBtnText.textContent = 'Editar requerimientos';
        editBtn.querySelector('i').className = 'fas fa-shopping-cart';
    } else {
        editBtnText.textContent = 'Editar ofertas';
        editBtn.querySelector('i').className = 'fas fa-boxes';
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    
    // Cargar datos de la empresa
    loadCompanyDataForOptimization(companyId);
    
    // Cargar sugerencias de optimización
    loadOptimizationSuggestionsForModal();
}

// Función para cerrar modal de optimización
function closeOptimizeCompanyModal() {
    const modal = document.getElementById('optimizeCompanyModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

// Función para cargar datos de la empresa
function loadCompanyDataForOptimization(companyId) {
    fetch(`${window.BASE_URL}/matches/getCompanyDetailsAjax`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `company_id=${encodeURIComponent(companyId)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.company) {
            const company = data.company;
            
            // Actualizar información de la empresa
            document.getElementById('optimize-company-name').textContent = company.company_name || 'Empresa';
            document.getElementById('optimize-description').value = company.description || '';
            
            // Procesar keywords
            let keywords = '';
            if (company.keywords) {
                if (Array.isArray(company.keywords)) {
                    keywords = company.keywords.join(', ');
                } else if (typeof company.keywords === 'string') {
                    try {
                        const parsed = JSON.parse(company.keywords);
                        if (Array.isArray(parsed)) {
                            keywords = parsed.join(', ');
                        } else {
                            keywords = company.keywords;
                        }
                    } catch (e) {
                        keywords = company.keywords;
                    }
                }
            }
            document.getElementById('optimize-keywords').value = keywords;
            
        } else {
            console.error('Error loading company data:', data.message);
            document.getElementById('optimize-company-name').textContent = 'Error al cargar empresa';
        }
    })
    .catch(err => {
        console.error('Error fetching company data:', err);
        document.getElementById('optimize-company-name').textContent = 'Error de conexión';
    });
}

// Función para cargar sugerencias de optimización en el modal
function loadOptimizationSuggestionsForModal() {
    const keywordContainer = document.getElementById('suggested-keywords');
    const wordsContainer = document.getElementById('suggested-words');
    
    // Intentar obtener datos de las sugerencias ya cargadas
    const suggestionContent = document.getElementById('optimization-suggestions-content');
    if (suggestionContent && suggestionContent.innerHTML.includes('Keywords más populares')) {
        // Las sugerencias ya están cargadas, extraer datos
        extractSuggestionsFromDOM(keywordContainer, wordsContainer);
    } else {
        // Cargar sugerencias desde el servidor
        keywordContainer.innerHTML = '<span class="text-gray-400 text-xs">Cargando...</span>';
        wordsContainer.innerHTML = '<span class="text-gray-400 text-xs">Cargando...</span>';
        
        fetch('<?= BASE_URL ?>/matches/getOptimizationSuggestionsAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'event_id=' + encodeURIComponent(eventId) + '&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.suggestions) {
                populateSuggestions(keywordContainer, wordsContainer, data.suggestions);
            } else {
                keywordContainer.innerHTML = '<span class="text-gray-400 text-xs">No disponible</span>';
                wordsContainer.innerHTML = '<span class="text-gray-400 text-xs">No disponible</span>';
            }
        })
        .catch(err => {
            keywordContainer.innerHTML = '<span class="text-red-400 text-xs">Error</span>';
            wordsContainer.innerHTML = '<span class="text-red-400 text-xs">Error</span>';
        });
    }
}

// Función para extraer sugerencias del DOM existente
function extractSuggestionsFromDOM(keywordContainer, wordsContainer) {
    // Esta función extraería las sugerencias del DOM si ya están cargadas
    // Por simplicidad, mostramos un mensaje
    keywordContainer.innerHTML = '<span class="text-gray-400 text-xs">Cargar sugerencias primero</span>';
    wordsContainer.innerHTML = '<span class="text-gray-400 text-xs">Cargar sugerencias primero</span>';
}

// Función para poblar sugerencias en el modal
function populateSuggestions(keywordContainer, wordsContainer, suggestions) {
    // Keywords populares
    const companyRole = document.getElementById('optimize-company-role').value;
    const keywords = companyRole === 'buyer' ? suggestions.buyer_keywords : suggestions.supplier_keywords;
    
    keywordContainer.innerHTML = '';
    if (keywords && Object.keys(keywords).length > 0) {
        Object.entries(keywords).slice(0, 8).forEach(([keyword, count]) => {
            const tag = document.createElement('span');
            tag.className = 'bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-blue-200 transition';
            tag.textContent = `${keyword} (${count})`;
            tag.onclick = () => addKeywordToInput(keyword);
            keywordContainer.appendChild(tag);
        });
    } else {
        keywordContainer.innerHTML = '<span class="text-gray-400 text-xs">Sin datos</span>';
    }
    
    // Palabras en descripciones
    const descWords = companyRole === 'buyer' ? suggestions.buyer_description_words : suggestions.supplier_description_words;
    
    wordsContainer.innerHTML = '';
    if (descWords && Object.keys(descWords).length > 0) {
        Object.entries(descWords).slice(0, 8).forEach(([word, count]) => {
            const tag = document.createElement('span');
            tag.className = 'bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200 transition';
            tag.textContent = `${word} (${count})`;
            tag.onclick = () => addWordToDescription(word);
            wordsContainer.appendChild(tag);
        });
    } else {
        wordsContainer.innerHTML = '<span class="text-gray-400 text-xs">Sin datos</span>';
    }
}

// Función para agregar keyword al input
function addKeywordToInput(keyword) {
    const keywordInput = document.getElementById('optimize-keywords');
    const currentValue = keywordInput.value.trim();
    
    if (currentValue === '') {
        keywordInput.value = keyword;
    } else {
        const keywords = currentValue.split(',').map(k => k.trim());
        if (!keywords.includes(keyword)) {
            keywordInput.value = currentValue + ', ' + keyword;
        }
    }
}

// Función para agregar palabra a la descripción
function addWordToDescription(word) {
    const descInput = document.getElementById('optimize-description');
    const currentValue = descInput.value.trim();
    
    if (currentValue === '') {
        descInput.value = word;
    } else {
        if (!currentValue.toLowerCase().includes(word.toLowerCase())) {
            descInput.value = currentValue + ' ' + word;
        }
    }
}

// Detectar cambio de pestaña y cargar datos solo cuando se selecciona
window.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, verificando modal...');
    const modal = document.getElementById('optimizeCompanyModal');
    console.log('Modal optimizeCompanyModal:', modal);
    
    loadDirectMatches(); // Carga inicial
    
    // Event listeners para cambios de tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = btn.getAttribute('data-tab');
            if (tab === 'direct-matches') loadDirectMatches();
            if (tab === 'potential-matches') loadPotentialMatches();
            if (tab === 'no-match-companies') loadUnmatchedCompanies();
        });
    });
    
    // Event listener para cerrar modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('.close-modal-btn')) {
            closePotentialMatchModal();
        }
    });
    
    // Event listener para cargar sugerencias de optimización
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'load-optimization-suggestions') {
            loadOptimizationSuggestions();
        }
    });
    
    // Event listeners para modal de optimización
    document.addEventListener('click', function(e) {
        // Botón Optimizar (event delegation)
        if (e.target.closest('.btn-optimize-company')) {
            const btn = e.target.closest('.btn-optimize-company');
            const companyId = btn.getAttribute('data-company-id');
            const companyType = btn.getAttribute('data-company-type');
            
            console.log('Botón Optimizar clicked:', {companyId, companyType});
            openOptimizeCompanyModal(companyId, companyType);
        }
        
        // Cerrar modal
        if (e.target.closest('.close-modal-btn') && e.target.closest('#optimizeCompanyModal')) {
            closeOptimizeCompanyModal();
        }
        
        // Botón editar requerimientos/ofertas
        if (e.target.closest('#edit-requirements-offers-btn')) {
            const companyId = document.getElementById('optimize-company-id').value;
            const eventId = document.getElementById('optimize-event-id').value;
            
            if (companyId && eventId) {
                // Redirigir a view_full_registration
                window.location.href = `${window.BASE_URL}/events/view_full_registration/${eventId}?company_id=${companyId}`;
            }
        }
    });
    
    // Event listener para agregar fecha en el modal
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'add-supplier-attendance-date') {
            const attList = document.getElementById('pm-supplier-attendance-list');
            if (attList) {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center gap-2 mb-1';
                
                const input = document.createElement('input');
                input.type = 'date';
                input.className = 'form-control datepicker';
                input.name = 'supplier_attendance_dates[]';
                
                const btnDel = document.createElement('button');
                btnDel.type = 'button';
                btnDel.className = 'text-red-500 text-xs px-2';
                btnDel.innerHTML = 'Eliminar';
                btnDel.onclick = function() { wrapper.remove(); };
                
                wrapper.appendChild(input);
                wrapper.appendChild(btnDel);
                attList.appendChild(wrapper);
            }
        }
    });
    // Guardar datos del proveedor al hacer submit en el modal
    const potentialMatchForm = document.getElementById('potentialMatchForm');
    if (potentialMatchForm) {
        potentialMatchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Obtener supplierId y eventId del modal (guardar en variables globales al abrir el modal)
            const supplierId = window.pmSupplierId;
            const eventId = window.pmEventId;
            if (!supplierId || !eventId) {
                alert('Faltan datos de proveedor o evento.');
                return;
            }
            const description = document.getElementById('pm-supplier-description').value;
            const keywords = document.getElementById('pm-supplier-keywords').value;
            // Obtener todas las fechas
            const attendanceInputs = document.querySelectorAll('#pm-supplier-attendance-list input[type="date"]');
            const attendanceDays = Array.from(attendanceInputs).map(input => input.value).filter(Boolean);
            // CSRF token
            const csrfToken = window.csrfToken || '';
            // Construir datos para enviar
            const formData = new URLSearchParams();
            formData.append('supplier_id', supplierId);
            formData.append('event_id', eventId);
            formData.append('description', description);
            formData.append('keywords', keywords);
            attendanceDays.forEach(date => formData.append('attendance_days[]', date));
            formData.append('csrf_token', csrfToken);
            // Enviar AJAX
            fetch(`${window.BASE_URL}/matches/updateSupplierPotentialMatchAjax`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Datos del proveedor actualizados correctamente.');
                    closePotentialMatchModal();
                    // Opcional: recargar matches potenciales
                    if (typeof loadPotentialMatches === 'function') loadPotentialMatches();
                } else {
                    alert(data.message || 'Error al guardar los datos.');
                }
            })
            .catch(() => {
                alert('Error de red al guardar los datos.');
            });
        });
    }
    
    // Event listener para el form de optimización
    const optimizeForm = document.getElementById('optimizeCompanyForm');
    if (optimizeForm) {
        optimizeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            // Enviar datos
            fetch(`${window.BASE_URL}/matches/optimizeCompanyAjax`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Empresa optimizada correctamente');
                    closeOptimizeCompanyModal();
                    // Recargar empresas sin match si estamos en esa pestaña
                    if (document.querySelector('.tab-btn[data-tab="no-match-companies"]').classList.contains('active')) {
                        loadUnmatchedCompanies();
                    }
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la optimización'));
                }
            })
            .catch(err => {
                console.error('Error saving optimization:', err);
                alert('Error de conexión al guardar los datos');
            })
            .finally(() => {
                // Rehabilitar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
    
    // Event listener para botones "Programar cita"
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-schedule-appointment')) {
            e.preventDefault();
            
            const btn = e.target.closest('.btn-schedule-appointment');
            const matchId = btn.dataset.matchId;
            const buyerId = btn.dataset.buyerId;
            const supplierId = btn.dataset.supplierId;
            const eventId = btn.dataset.eventId;
            const buyerName = btn.dataset.buyerName;
            const supplierName = btn.dataset.supplierName;
            const coincidenceDates = btn.dataset.coincidenceDates;
            
            // Validar datos requeridos
            if (!matchId || !buyerId || !supplierId || !eventId) {
                alert('Error: Faltan datos necesarios para programar la cita');
                return;
            }
            
            // Confirmar acción
            const confirmMessage = `¿Desea programar una cita entre:\n${buyerName} (Comprador)\n${supplierName} (Proveedor)?\n\nSe asignará automáticamente el primer horario disponible.`;
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Deshabilitar botón temporalmente
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Programando...';
            
            // Enviar solicitud AJAX
            fetch(`${window.BASE_URL}/agendas/scheduleAppointment`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `match_id=${encodeURIComponent(matchId)}&buyer_id=${encodeURIComponent(buyerId)}&supplier_id=${encodeURIComponent(supplierId)}&event_id=${encodeURIComponent(eventId)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`¡Cita programada exitosamente!\n\nFecha: ${data.appointment.date}\nHora: ${data.appointment.time}\nMesa: ${data.appointment.table}`);
                    
                    // Cambiar estado del botón
                    btn.innerHTML = '<i class="fas fa-check"></i> Programada';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-secondary');
                    btn.disabled = true;
                } else {
                    alert('Error al programar la cita: ' + (data.message || 'Error desconocido'));
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(err => {
                console.error('Error scheduling appointment:', err);
                alert('Error de conexión al programar la cita');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    });
});
</script>


</script>
<!-- Incluir el JS de paginación al final -->
<?php include(VIEW_DIR . '/shared/footer.php'); ?>
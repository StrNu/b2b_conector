<?php 
// Vista de matches con Material Design 3
if (file_exists(CONFIG_DIR . '/material-config.php')) {
    require_once CONFIG_DIR . '/material-config.php';
}

// Fallback functions if Material Design helpers are not available
if (!function_exists('materialButton')) {
    function materialButton($text, $variant = 'filled', $icon = '', $attributes = '', $size = '') {
        $class = 'btn btn-primary';
        if ($variant === 'outlined') $class = 'btn btn-secondary';
        if ($variant === 'tonal') $class = 'btn btn-info';
        if ($size === 'small') $class .= ' btn-sm';
        return '<button class="' . $class . '" ' . $attributes . '>' . $text . '</button>';
    }
}

if (!function_exists('materialCard')) {
    function materialCard($title, $content, $variant = 'elevated', $actions = '') {
        return '<div class="card">
                    <div class="card-header"><h5>' . $title . '</h5></div>
                    <div class="card-body">' . $content . '</div>
                    ' . ($actions ? '<div class="card-footer">' . $actions . '</div>' : '') . '
                </div>';
    }
}

if (!function_exists('displayFlashMessages')) {
    function displayFlashMessages() {
        include(VIEW_DIR . '/shared/notifications.php');
    }
}

$pageTitle = 'Matches del Evento';
$moduleCSS = 'matches';
$moduleJS = 'matches';
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Matches del Evento</h1>
            <p class="page-subtitle">Gestiona las conexiones entre compradores y proveedores</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . (isset($eventId) && is_numeric($eventId) && $eventId > 0 ? $eventId : (isset($_GET['event_id']) ? (int)$_GET['event_id'] : '')) . '\'"'
            ) ?>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Tabs Section -->
    <div class="tabs-section">
        <?php 
        ob_start();
        ?>
            <!-- Material Design 3 Tabs -->
            <div class="tabs-material">
                <div class="tabs-material__nav">
                    <button class="tabs-material__tab tabs-material__tab--active" data-tab="direct-matches" type="button">
                        <span class="tabs-material__tab-indicator"></span>
                        <span class="tabs-material__tab-content">
                            <i class="fas fa-check-circle"></i>
                            Matches Confirmados
                        </span>
                    </button>
                    <button class="tabs-material__tab" data-tab="potential-matches" type="button">
                        <span class="tabs-material__tab-indicator"></span>
                        <span class="tabs-material__tab-content">
                            <i class="fas fa-search"></i>
                            Matches Potenciales
                        </span>
                    </button>
                    <button class="tabs-material__tab" data-tab="no-match-companies" type="button">
                        <span class="tabs-material__tab-indicator"></span>
                        <span class="tabs-material__tab-content">
                            <i class="fas fa-exclamation-triangle"></i>
                            Sin Matches
                        </span>
                    </button>
                </div>
                
                <!-- Tab Content: Direct Matches -->
                <div class="tabs-material__panel tabs-material__panel--active" id="direct-matches">
                    <div class="matches-controls">
                        <div class="search-controls">
                            <div class="textfield-material">
                                <input type="text" id="search-matches" class="textfield-material__input" 
                                       placeholder=" " data-autosearch="direct-matches-table">
                                <label class="textfield-material__label">Buscar por empresa...</label>
                            </div>
                        </div>
                        <div class="action-controls">
                            <form method="post" action="<?= BASE_URL ?>/appointments/scheduleAll" id="schedule-all-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId ?? '') ?>">
                                <?= materialButton(
                                    '<i class="fas fa-calendar-plus"></i> Programar Todo',
                                    'filled',
                                    '',
                                    'type="submit"'
                                ) ?>
                            </form>
                        </div>
                    </div>
                    <div class="table-container" id="direct-matches-table-container">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            Cargando matches confirmados...
                        </div>
                    </div>
                    <div id="direct-matches-pagination" class="pagination-container"></div>
                </div>
                
                <!-- Tab Content: Potential Matches -->
                <div class="tabs-material__panel" id="potential-matches">
                    <div class="matches-controls matches-controls--extended">
                        <div class="search-controls">
                            <div class="textfield-material">
                                <input type="text" id="search-potential-matches" class="textfield-material__input" 
                                       placeholder=" " data-autosearch="potential-matches-table">
                                <label class="textfield-material__label">Buscar en matches potenciales...</label>
                            </div>
                        </div>
                        <div class="filter-controls">
                            <div class="textfield-material">
                                <select id="filter-match-reason" class="textfield-material__input">
                                    <option value="">Todos los tipos</option>
                                </select>
                                <label class="textfield-material__label">Filtrar por razón</label>
                            </div>
                        </div>
                        <div class="action-controls">
                            <?= materialButton(
                                '<i class="fas fa-save"></i> Guardar Todos',
                                'tonal',
                                '',
                                'id="btn-save-all-potential"'
                            ) ?>
                        </div>
                    </div>
                    <div class="table-container" id="potential-matches-table-container">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            Cargando matches potenciales...
                        </div>
                    </div>
                    <div id="potential-matches-pagination" class="pagination-container"></div>
                </div>
                
                <!-- Tab Content: No Match Companies -->
                <div class="tabs-material__panel" id="no-match-companies">
                    <div class="matches-controls">
                        <div class="search-controls">
                            <div class="textfield-material">
                                <input type="text" id="search-no-match-companies" class="textfield-material__input" 
                                       placeholder=" " data-autosearch="no-match-companies-table">
                                <label class="textfield-material__label">Buscar empresa...</label>
                            </div>
                        </div>
                        <div class="optimization-controls">
                            <details class="optimization-details">
                                <summary class="optimization-summary">
                                    <i class="fas fa-lightbulb"></i>
                                    Sugerencias de optimización
                                    <i class="fas fa-chevron-down"></i>
                                </summary>
                                <div class="optimization-content">
                                    <div id="optimization-suggestions-content" class="suggestions-grid">
                                        <div class="suggestions-placeholder">
                                            <?= materialButton(
                                                '<i class="fas fa-sync-alt"></i> Cargar Sugerencias',
                                                'outlined',
                                                '',
                                                'id="load-optimization-suggestions"'
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                    <div class="table-container" id="no-match-companies-table-container">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            Cargando empresas sin matches...
                        </div>
                    </div>
                    <div id="no-match-companies-pagination" class="pagination-container"></div>
                </div>
            </div>
        <?php 
        $matchesContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-handshake"></i> Gestión de Matches',
            $matchesContent,
            'elevated'
        );
        ?>
    </div>
</div>


<script>
// Variables PHP disponibles en JS
const eventId = '<?= isset($eventId) ? htmlspecialchars($eventId) : '' ?>';
const csrfToken = '<?= isset($csrfToken) ? htmlspecialchars($csrfToken) : '' ?>';

// Variables globales para el modal
window.csrfToken = csrfToken;
window.BASE_URL = '<?= BASE_URL ?>';
window.eventId = eventId;
window.pmSupplierId = null;
window.pmEventId = null;
window.pmBuyerId = null;

// Debug: verificar que las variables están disponibles
console.log('Matches page initialized with:', {
    eventId: eventId,
    csrfToken: csrfToken ? 'presente' : 'ausente',
    BASE_URL: window.BASE_URL
});

// Función para cargar matches confirmados
window.loadDirectMatches = function loadDirectMatches() {
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
                        ${(m.programed == 1 || m.programed === true) ? 
                            `<button class="btn btn-secondary btn-xs" disabled title="Ya programada">
                                <i class="fas fa-check"></i> Programada
                            </button>` :
                            `<button class="btn btn-success btn-xs btn-schedule-appointment" 
                                    data-match-id="${m.match_id}" 
                                    data-buyer-id="${m.buyer_id}" 
                                    data-supplier-id="${m.supplier_id}" 
                                    data-event-id="${eventId}"
                                    data-buyer-name="${m.buyer_name ?? ''}"
                                    data-supplier-name="${m.supplier_name ?? ''}"
                                    data-coincidence-dates="${m.coincidence_of_dates ?? ''}"
                                    title="Programar cita para este match">
                                <i class="fas fa-calendar-plus"></i> Programar cita
                            </button>`
                        }
                    </td>` +
                    `</tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
        // Invocar paginación si hay filas
        if (matches.length > 0) {
            // Esperar un poco para que el DOM se actualice completamente
            setTimeout(() => {
                if (typeof pagination === 'function') {
                    pagination('direct-matches-pagination', 'direct-matches-table', 10);
                    console.log('Paginación inicializada para direct-matches-table');
                }
            }, 100);
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
window.loadPotentialMatches = function loadPotentialMatches() {
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
            '<th>Comprador</th><th>Proveedor</th><th>Score</th><th>Strength Match</th><th>Match reason</th><th>Fechas Coincidentes</th><th>Acciones</th>' +
            '</tr></thead><tbody>';
        if (matches.length === 0) {
            html += '<tr><td colspan="7" class="text-center text-gray-400">No hay matches potenciales.</td></tr>';
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
                
                // Fechas coincidentes y botón "Guardar match"
                const dateMatch = m.date_match || 0;
                const coincidenceDates = m.coincidence_of_dates || '';
                const coincidentDatesDisplay = coincidenceDates ? coincidenceDates.replace(/,/g, ', ') : '-';
                
                // Botones de acción
                let actionButtons = `<button class=\"btn btn-primary btn-xs btn-edit-potential-match\" data-buyer-id=\"${m.buyer_id}\" data-supplier-id=\"${m.supplier_id}\" data-event-id=\"${eventId}\">Editar match</button>`;
                
                if (dateMatch > 0) {
                    actionButtons += ` <button class=\"btn btn-success btn-xs btn-save-match\" data-buyer-id=\"${m.buyer_id}\" data-supplier-id=\"${m.supplier_id}\" data-event-id=\"${eventId}\" data-date-match=\"${dateMatch}\">
                        <i class=\"fas fa-handshake\"></i> Guardar match
                    </button>`;
                } else {
                    actionButtons += ` <button class=\"btn btn-secondary btn-xs\" disabled title=\"Sin fechas coincidentes\">
                        <i class=\"fas fa-times\"></i> Sin fechas
                    </button>`;
                }
                
                html += `<tr>` +
                    `<td>${m.buyer_name ?? '-'}</td>` +
                    `<td>${m.supplier_name ?? '-'}</td>` +
                    `<td>${scoreBar}</td>` +
                    `<td>${strengthBar}</td>` +
                    `<td>${reason}</td>` +
                    `<td style=\"color: #059669; font-weight: 500;\">${coincidentDatesDisplay}</td>` +
                    `<td>${actionButtons}</td>` +
                    `</tr>`;
            });
        }
        html += '</tbody></table>';
        container.innerHTML = html;
        // Invocar paginación si hay filas
        if (matches.length > 0) {
            // Esperar un poco para que el DOM se actualice completamente
            setTimeout(() => {
                if (typeof pagination === 'function') {
                    pagination('potential-matches-pagination', 'potential-matches-table', 10);
                    console.log('Paginación inicializada para potential-matches-table');
                }
            }, 100);
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
        
        // Inicializar eventos para los botones "Guardar match"
        document.querySelectorAll('.btn-save-match').forEach(btn => {
            btn.addEventListener('click', function() {
                const buyerId = this.getAttribute('data-buyer-id');
                const supplierId = this.getAttribute('data-supplier-id');
                const eventId = this.getAttribute('data-event-id');
                const dateMatch = this.getAttribute('data-date-match');
                
                console.log('Botón guardar match clicked:', {buyerId, supplierId, eventId, dateMatch});
                
                // Confirmar acción
                if (!confirm('¿Desea crear un match confirmado entre estas empresas?')) {
                    return;
                }
                
                const originalContent = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
                
                // Crear match confirmado
                fetch(`${window.BASE_URL}/matches/createConfirmedMatchAjax`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `buyer_id=${buyerId}&supplier_id=${supplierId}&event_id=${eventId}&csrf_token=${window.csrfToken}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('¡Match creado exitosamente!');
                        // Recargar matches potenciales y directos
                        loadPotentialMatches();
                        loadDirectMatches();
                    } else {
                        alert('Error al crear el match: ' + (data.message || 'Error desconocido'));
                        this.disabled = false;
                        this.innerHTML = originalContent;
                    }
                })
                .catch(err => {
                    console.error('Error creating match:', err);
                    alert('Error de conexión al crear el match');
                    this.disabled = false;
                    this.innerHTML = originalContent;
                });
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
window.loadUnmatchedCompanies = function loadUnmatchedCompanies() {
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
            // Esperar un poco para que el DOM se actualice completamente
            setTimeout(() => {
                if (typeof pagination === 'function') {
                    pagination('no-match-companies-pagination', 'no-match-companies-table', 10);
                    console.log('Paginación inicializada para no-match-companies-table');
                }
            }, 100);
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
    
    let modal = document.getElementById('potentialMatchModal');
    if (!modal) {
        console.error('Modal potentialMatchModal no encontrado');
        alert('Error: Modal no encontrado en la página.');
        return;
    }
    
    // Mover el modal directamente al body para evitar problemas de overflow/clip
    if (modal.parentElement !== document.body) {
        console.log('Moviendo modal al body...');
        document.body.appendChild(modal);
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
    cleanField('pm-coincident-dates');
    
    const attendanceList = document.getElementById('pm-supplier-attendance-list');
    if (attendanceList) attendanceList.innerHTML = '';
    
    // Detectar cambios en el modal
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                console.log('ALERTA: Alguien está cambiando el estilo del modal!');
                console.log('Nuevo estilo:', mutation.target.style.cssText);
                console.trace('Stack trace del cambio');
            }
        });
    });
    
    observer.observe(modal, {
        attributes: true,
        attributeFilter: ['style']
    });
    
    // Obtener modal content
    const modalContent = modal.querySelector('.modal-content');
    
    // SOLUCIÓN: Sobrescribir CSS problemático de Material Design
    console.log('Aplicando override de CSS para Material Design...');
    
    // Primero remover cualquier CSS override existente
    const existingOverride = document.getElementById('modal-override-styles');
    if (existingOverride) {
        existingOverride.remove();
    }
    
    // Crear estilo específico para anular Material Design
    const overrideStyle = document.createElement('style');
    overrideStyle.id = 'modal-override-styles';
    overrideStyle.innerHTML = `
        #potentialMatchModal {
            display: flex !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.8) !important;
            z-index: 2147483647 !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            padding: 0 !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
            clip: auto !important;
            clip-path: none !important;
        }
        
        #potentialMatchModal .modal-content {
            position: relative !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            padding: 0 !important;
            border-radius: 12px !important;
            max-width: 95vw !important;
            width: 900px !important;
            max-height: 90vh !important;
            overflow: hidden !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
            margin: 0 !important;
            transform: none !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Header del modal */
        #potentialMatchModal .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%) !important;
            color: white !important;
            padding: 20px 24px !important;
            margin: 0 !important;
            border-radius: 12px 12px 0 0 !important;
            border-bottom: none !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        
        #potentialMatchModal .modal-header h3 {
            margin: 0 !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            color: white !important;
        }
        
        #potentialMatchModal .close-modal-btn {
            background: rgba(255,255,255,0.2) !important;
            border: none !important;
            color: white !important;
            width: 32px !important;
            height: 32px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            font-size: 18px !important;
            line-height: 1 !important;
            transition: background-color 0.2s !important;
        }
        
        #potentialMatchModal .close-modal-btn:hover {
            background: rgba(255,255,255,0.3) !important;
        }
        
        /* Grid layout */
        #potentialMatchModal .modal-body {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0 !important;
            padding: 0 !important;
            max-height: calc(90vh - 80px) !important;
            overflow-y: auto !important;
        }
        
        /* Columnas del grid */
        #potentialMatchModal .buyer-column {
            background: #f8fafc !important;
            border-right: 1px solid #e5e7eb !important;
            padding: 24px !important;
        }
        
        #potentialMatchModal .supplier-column {
            background: #ffffff !important;
            padding: 24px !important;
        }
        
        /* Headers de columnas */
        #potentialMatchModal .column-header {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin-bottom: 20px !important;
            padding-bottom: 12px !important;
            border-bottom: 2px solid !important;
            font-weight: 600 !important;
            font-size: 16px !important;
        }
        
        #potentialMatchModal .buyer-column .column-header {
            color: #1e40af !important;
            border-color: #3b82f6 !important;
        }
        
        #potentialMatchModal .supplier-column .column-header {
            color: #059669 !important;
            border-color: #10b981 !important;
        }
        
        /* Fields */
        #potentialMatchModal .field-group {
            margin-bottom: 16px !important;
        }
        
        #potentialMatchModal .field-label {
            display: block !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #374151 !important;
            margin-bottom: 6px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        #potentialMatchModal .field-value {
            background: white !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            padding: 10px 12px !important;
            font-size: 14px !important;
            color: #111827 !important;
            min-height: 20px !important;
            line-height: 1.4 !important;
        }
        
        #potentialMatchModal .field-value.readonly {
            background: #f9fafb !important;
            color: #6b7280 !important;
            border-color: #e5e7eb !important;
        }
        
        #potentialMatchModal .field-value.editable {
            border-color: #3b82f6 !important;
            background: white !important;
        }
        
        #potentialMatchModal .field-value.editable:focus {
            outline: none !important;
            border-color: #1e40af !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Footer */
        #potentialMatchModal .modal-footer {
            background: #f8fafc !important;
            padding: 20px 24px !important;
            border-top: 1px solid #e5e7eb !important;
            display: flex !important;
            justify-content: flex-end !important;
            gap: 12px !important;
            border-radius: 0 0 12px 12px !important;
        }
        
        #potentialMatchModal .btn-modal {
            padding: 12px 20px !important;
            border-radius: 6px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            width: 140px !important;
            height: 44px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            text-align: center !important;
            line-height: 1.4 !important;
            white-space: nowrap !important;
            box-sizing: border-box !important;
        }
        
        #potentialMatchModal .btn-cancel {
            background: #f3f4f6 !important;
            color: #374151 !important;
            border: 1px solid #d1d5db !important;
        }
        
        #potentialMatchModal .btn-cancel:hover {
            background: #e5e7eb !important;
            color: #1f2937 !important;
        }
        
        #potentialMatchModal .btn-save {
            background: #3b82f6 !important;
            color: white !important;
            border: 1px solid #3b82f6 !important;
        }
        
        #potentialMatchModal .btn-save:hover {
            background: #2563eb !important;
            border-color: #2563eb !important;
        }
        
        /* Botón de agregar fecha más pequeño */
        #potentialMatchModal #add-supplier-attendance-date {
            padding: 8px 12px !important;
            font-size: 12px !important;
            min-width: auto !important;
            background: #f0f9ff !important;
            color: #0369a1 !important;
            border: 1px solid #bae6fd !important;
        }
        
        #potentialMatchModal #add-supplier-attendance-date:hover {
            background: #e0f2fe !important;
            border-color: #7dd3fc !important;
        }
        
        /* Forzar visibilidad de todos los elementos dentro del modal */
        #potentialMatchModal * {
            visibility: visible !important;
            opacity: 1 !important;
        }
    `;
    
    document.head.appendChild(overrideStyle);
    
    // Mostrar modal
    modal.style.display = 'flex';
    
    console.log('Modal mostrado:', modal);
    console.log('Modal offsetWidth:', modal.offsetWidth);
    console.log('Modal offsetHeight:', modal.offsetHeight);
    console.log('Modal getClientRects:', modal.getClientRects());
    
    // Investigar el DOM y contenedores padre
    console.log('Modal parent element:', modal.parentElement);
    console.log('Body overflow:', getComputedStyle(document.body).overflow);
    console.log('HTML overflow:', getComputedStyle(document.documentElement).overflow);
    
    // Verificar si hay elementos que puedan estar ocultando el modal
    let parent = modal.parentElement;
    while (parent) {
        const styles = getComputedStyle(parent);
        if (styles.overflow !== 'visible' || styles.position === 'relative' || styles.zIndex !== 'auto') {
            console.log('Parent with special styles:', parent, {
                overflow: styles.overflow,
                position: styles.position,
                zIndex: styles.zIndex,
                clip: styles.clip,
                clipPath: styles.clipPath
            });
        }
        parent = parent.parentElement;
    }
    
    // Verificar cada 100ms si alguien está cambiando el display
    const checkInterval = setInterval(() => {
        if (modal.style.display === 'none') {
            console.log('DETECTADO: Modal fue ocultado! Restaurando...');
            modal.style.cssText = 'display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.8) !important; z-index: 999999999 !important; align-items: center !important; justify-content: center !important;';
        }
    }, 100);
    
    // Limpiar el intervalo después de 10 segundos
    setTimeout(() => {
        clearInterval(checkInterval);
        observer.disconnect();
    }, 10000);
    
    // Log del modal content ya configurado
    console.log('Modal content:', modalContent);
    
    // Verificar si hay otros elementos con z-index alto que puedan estar tapando
    const highZElements = Array.from(document.querySelectorAll('*')).filter(el => {
        const zIndex = parseInt(getComputedStyle(el).zIndex);
        return zIndex > 1000 && el !== modal && !modal.contains(el);
    });
    
    console.log('Elementos con z-index alto:', highZElements);
    
    // Temporalmente reducir z-index de elementos que puedan estar tapando
    const originalZIndexes = [];
    highZElements.forEach(el => {
        const originalZ = el.style.zIndex;
        originalZIndexes.push({element: el, zIndex: originalZ});
        el.style.zIndex = '1';
        console.log('Reduciendo z-index de:', el);
    });
    
    // Restaurar z-indexes después de 5 segundos
    setTimeout(() => {
        originalZIndexes.forEach(({element, zIndex}) => {
            element.style.zIndex = zIndex;
        });
        console.log('Z-indexes restaurados');
    }, 5000);
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
                    const isInactive = req.subcategory_active == 0 || req.category_active == 0;
                    const inactiveStyle = isInactive ? ' style="color: #999; text-decoration: line-through;" title="Categoría inactiva"' : '';
                    return `<span${inactiveStyle}>${req.event_subcategory_name} → ${req.event_category_name}</span>`;
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
            const buyerAttendanceDays = data.data.attendance_days || [];
            document.getElementById('pm-buyer-attendance').textContent = buyerAttendanceDays.length ? buyerAttendanceDays.join(', ') : '-';
            
            // Guardar fechas del buyer en variable global para comparar después
            window.buyerAttendanceDays = buyerAttendanceDays;
        } else {
            document.getElementById('pm-buyer-company').textContent = 'No encontrado';
        }
        
        // Log después de cargar buyer data
        console.log('BUYER FETCH COMPLETE - Modal display:', modal.style.display);
        console.log('BUYER FETCH COMPLETE - Modal visible:', modal.offsetWidth > 0 && modal.offsetHeight > 0);
    })
    .catch(err => {
        console.error('Error loading buyer data:', err);
        console.log('BUYER FETCH ERROR - Modal display:', modal.style.display);
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
                    const isInactive = offer.subcategory_active == 0 || offer.category_active == 0;
                    const inactiveStyle = isInactive ? ' style="color: #999; text-decoration: line-through;" title="Categoría inactiva"' : '';
                    return `<span${inactiveStyle}>${offer.event_subcategory_name} → ${offer.event_category_name}</span>`;
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
            const supplierAttendanceDays = data.data.attendance_days || [];
            
            if (supplierAttendanceDays.length) {
                supplierAttendanceDays.forEach((date, idx) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex items-center gap-2 mb-1';
                    const input = document.createElement('input');
                    input.type = 'date';
                    input.className = 'form-control datepicker attendance-date-input';
                    input.value = date;
                    input.name = `supplier_attendance_dates[]`;
                    
                    // Event listener para recalcular fechas coincidentes cuando cambie
                    input.addEventListener('change', calculateCoincidentDates);
                    
                    // Botón eliminar
                    const btnDel = document.createElement('button');
                    btnDel.type = 'button';
                    btnDel.className = 'text-red-500 text-xs px-2';
                    btnDel.innerHTML = 'Eliminar';
                    btnDel.onclick = function() { 
                        wrapper.remove(); 
                        calculateCoincidentDates(); // Recalcular después de eliminar
                    };
                    wrapper.appendChild(input);
                    wrapper.appendChild(btnDel);
                    attList.appendChild(wrapper);
                });
            } else {
                attList.innerHTML = '<span class="text-gray-400">-</span>';
            }
            
            // Calcular fechas coincidentes iniciales
            calculateCoincidentDates();
            
            // Inicializar datepicker si aplica
            if (typeof addDatePicker === 'function') {
                addDatePicker();
            }
        } else {
            document.getElementById('pm-supplier-company').textContent = 'No encontrado';
        }
        
        // Log final del estado del modal después de cargar supplier
        console.log('SUPPLIER FETCH COMPLETE - Modal display:', modal.style.display);
        console.log('SUPPLIER FETCH COMPLETE - Modal visible:', modal.offsetWidth > 0 && modal.offsetHeight > 0);
    })
    .catch(err => {
        console.error('Error loading supplier data:', err);
        console.log('SUPPLIER FETCH ERROR - Modal display:', modal.style.display);
    });
}

function closePotentialMatchModal() {
    const modal = document.getElementById('potentialMatchModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    
    // Remover el CSS override que está forzando la visibilidad
    const overrideStyle = document.getElementById('modal-override-styles');
    if (overrideStyle) {
        overrideStyle.remove();
        console.log('CSS override removido');
    }
    
    // Limpiar variables globales
    window.pmSupplierId = null;
    window.pmEventId = null;
    window.pmBuyerId = null;
    
    console.log('Modal cerrado');
}

// Función para calcular fechas coincidentes y mostrarlas en el modal
function calculateCoincidentDates() {
    const buyerDates = window.buyerAttendanceDays || [];
    
    // Obtener fechas actuales del supplier desde los inputs
    const supplierInputs = document.querySelectorAll('.attendance-date-input');
    const supplierDates = Array.from(supplierInputs)
        .map(input => input.value)
        .filter(date => date && date.trim() !== '');
    
    // Calcular fechas coincidentes
    const coincidentDates = buyerDates.filter(date => supplierDates.includes(date));
    
    // Actualizar display de fechas coincidentes
    const coincidentDatesEl = document.getElementById('pm-coincident-dates');
    
    if (coincidentDates.length > 0) {
        coincidentDatesEl.textContent = coincidentDates.join(', ');
        coincidentDatesEl.style.color = '#059669';
    } else {
        coincidentDatesEl.textContent = 'Sin fechas coincidentes';
        coincidentDatesEl.style.color = '#dc2626';
    }
    
    console.log('Fechas coincidentes calculadas:', {
        buyerDates,
        supplierDates, 
        coincidentDates
    });
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
    
    // Verificar que los elementos existen antes de usarlos
    const elements = {
        companyId: document.getElementById('optimize-company-id'),
        eventId: document.getElementById('optimize-event-id'),
        companyRole: document.getElementById('optimize-company-role'),
        description: document.getElementById('optimize-description'),
        keywords: document.getElementById('optimize-keywords'),
        companyName: document.getElementById('optimize-company-name'),
        companyType: document.getElementById('optimize-company-type'),
        editBtn: document.getElementById('edit-requirements-offers-btn'),
        editBtnText: document.getElementById('edit-btn-text')
    };
    
    console.log('Elementos encontrados:', Object.keys(elements).reduce((acc, key) => {
        acc[key] = elements[key] ? 'OK' : 'MISSING';
        return acc;
    }, {}));
    
    // Establecer datos básicos (con verificación)
    if (elements.companyId) elements.companyId.value = companyId;
    if (elements.eventId) elements.eventId.value = eventId;
    if (elements.companyRole) elements.companyRole.value = companyType;
    
    // Limpiar campos (con verificación)
    if (elements.description) elements.description.value = '';
    if (elements.keywords) elements.keywords.value = '';
    if (elements.companyName) elements.companyName.textContent = 'Cargando...';
    if (elements.companyType) elements.companyType.textContent = companyType === 'buyer' ? 'Comprador' : 'Proveedor';
    
    // Configurar botón de editar requerimientos/ofertas (con verificación)
    if (elements.editBtn && elements.editBtnText) {
        if (companyType === 'buyer') {
            elements.editBtnText.textContent = 'Editar requerimientos';
            const icon = elements.editBtn.querySelector('i');
            if (icon) icon.className = 'fas fa-shopping-cart';
        } else {
            elements.editBtnText.textContent = 'Editar ofertas';
            const icon = elements.editBtn.querySelector('i');
            if (icon) icon.className = 'fas fa-boxes';
        }
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    
    console.log('Modal mostrado con estilos:', {
        display: modal.style.display,
        visibility: modal.style.visibility,
        opacity: modal.style.opacity,
        classList: Array.from(modal.classList)
    });
    
    // Forzar visibilidad después de un momento
    setTimeout(() => {
        console.log('Verificando visibilidad del modal después de timeout...');
        const rect = modal.getBoundingClientRect();
        console.log('Modal rect:', rect);
        console.log('Modal visible en viewport:', rect.width > 0 && rect.height > 0);
        
        if (rect.width === 0 || rect.height === 0) {
            console.log('Modal no es visible, aplicando estilos de fuerza...');
            modal.style.cssText = 'position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0,0,0,0.5) !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; visibility: visible !important; opacity: 1 !important;';
        }
    }, 100);
    
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
    
    // Verificar que las variables necesarias estén disponibles
    if (!eventId) {
        console.error('eventId no está disponible - la funcionalidad puede no funcionar correctamente');
        return;
    }
    
    if (!csrfToken) {
        console.warn('csrfToken no está disponible - las funciones AJAX pueden fallar');
    }
    
    console.log('Iniciando carga de matches directos...');
    loadDirectMatches(); // Carga inicial
    
    // Configurar variables globales para el módulo de matches
    window.eventId = eventId;
    window.csrfToken = csrfToken;
    
    // Event listeners para cambios de tab Material Design 3
    document.querySelectorAll('.tabs-material__tab').forEach(btn => {
        btn.addEventListener('click', function() {
            // Actualizar estado activo de tabs
            document.querySelectorAll('.tabs-material__tab').forEach(t => t.classList.remove('tabs-material__tab--active'));
            document.querySelectorAll('.tabs-material__panel').forEach(p => p.classList.remove('tabs-material__panel--active'));
            
            this.classList.add('tabs-material__tab--active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('tabs-material__panel--active');
            
            // Cargar contenido según la pestaña seleccionada
            console.log('Cambiando a pestaña:', tabId);
            if (tabId === 'direct-matches') {
                loadDirectMatches();
            } else if (tabId === 'potential-matches') {
                loadPotentialMatches();
            } else if (tabId === 'no-match-companies') {
                loadUnmatchedCompanies();
            }
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
                input.className = 'form-control datepicker attendance-date-input';
                input.name = 'supplier_attendance_dates[]';
                
                // Event listener para recalcular fechas coincidentes
                input.addEventListener('change', calculateCoincidentDates);
                
                const btnDel = document.createElement('button');
                btnDel.type = 'button';
                btnDel.className = 'text-red-500 text-xs px-2';
                btnDel.innerHTML = 'Eliminar';
                btnDel.onclick = function() { 
                    wrapper.remove(); 
                    calculateCoincidentDates(); // Recalcular después de eliminar
                };
                
                wrapper.appendChild(input);
                wrapper.appendChild(btnDel);
                attList.appendChild(wrapper);
                
                // Recalcular fechas coincidentes después de agregar
                calculateCoincidentDates();
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
            // El botón está fuera del form, lo buscamos por ID o atributo form
            const submitBtn = document.querySelector('button[form="optimizeCompanyForm"][type="submit"]') || 
                             document.querySelector('#optimizeCompanyModal button[type="submit"]');
            
            if (!submitBtn) {
                console.error('No se encontró el botón de submit en el modal de optimización');
                console.log('Botones disponibles:', document.querySelectorAll('#optimizeCompanyModal button'));
                return;
            }
            
            const originalText = submitBtn.innerHTML;
            
            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            // Enviar datos
            console.log('Enviando datos a:', `${window.BASE_URL}/matches?action=optimizeCompanyAjax`);
            console.log('FormData:', Object.fromEntries(formData.entries()));
            
            fetch(`${window.BASE_URL}/matches?action=optimizeCompanyAjax`, {
                method: 'POST',
                body: formData
            })
            .then(res => {
                console.log('Response status:', res.status);
                console.log('Response headers:', Array.from(res.headers.entries()));
                
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                
                return res.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Invalid JSON response: ${text}`);
                    }
                });
            })
            .then(data => {
                console.log('Parsed response:', data);
                if (data.success) {
                    alert('Empresa optimizada correctamente');
                    closeOptimizeCompanyModal();
                    // Recargar empresas sin match si estamos en esa pestaña
                    if (document.querySelector('.tabs-material__tab[data-tab="no-match-companies"]').classList.contains('tabs-material__tab--active')) {
                        loadUnmatchedCompanies();
                    }
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la optimización'));
                }
            })
            .catch(err => {
                console.error('Error saving optimization:', err);
                console.error('Error details:', err.message);
                alert('Error de conexión al guardar los datos: ' + err.message);
            })
            .finally(() => {
                // Rehabilitar botón
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
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
                    
                    // Cambiar estado del botón inmediatamente
                    btn.innerHTML = '<i class="fas fa-check"></i> Programada';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-secondary');
                    btn.disabled = true;
                    
                    // Recargar la tabla de matches para reflejar el cambio
                    console.log('Recargando tabla de matches después de programar cita...');
                    setTimeout(() => {
                        if (typeof window.loadDirectMatches === 'function') {
                            window.loadDirectMatches();
                        }
                    }, 1000);
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
    
    // Event listener para el formulario "Programar Todo"
    const scheduleAllForm = document.getElementById('schedule-all-form');
    if (scheduleAllForm) {
        scheduleAllForm.addEventListener('submit', function(e) {
            const confirmMessage = '¿Está seguro de que desea programar automáticamente TODAS las citas disponibles?\n\nEsta acción:\n- Programará citas para todos los matches confirmados\n- Asignará automáticamente horarios y mesas disponibles\n- Marcará los matches como programados\n\n¿Continuar?';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Mostrar indicador de carga
            const submitBtn = scheduleAllForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Programando...';
                
                // Restaurar botón si hay error (timeout de seguridad)
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                }, 30000); // 30 segundos timeout
            }
        });
    }
});
</script>

<style>
/* Matches Material Design 3 Styles */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.page-header__content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.page-subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    font-size: 1rem;
}

.page-header__actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.tabs-section {
    margin-bottom: 2rem;
}

/* Material Design 3 Tabs */
.tabs-material {
    width: 100%;
}

.tabs-material__nav {
    display: flex;
    border-bottom: 1px solid var(--md-outline-variant);
    margin-bottom: 2rem;
    overflow-x: auto;
}

.tabs-material__tab {
    position: relative;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--md-on-surface-variant);
    transition: color 200ms cubic-bezier(0.2, 0.0, 0, 1.0);
    min-width: 0;
    flex: 1;
}

.tabs-material__tab--active {
    color: var(--md-primary-40);
}

.tabs-material__tab-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
}

.tabs-material__tab-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--md-primary-40);
    border-radius: 3px 3px 0 0;
    transform: scaleX(0);
    transition: transform 200ms cubic-bezier(0.2, 0.0, 0, 1.0);
}

.tabs-material__tab--active .tabs-material__tab-indicator {
    transform: scaleX(1);
}

.tabs-material__panel {
    display: none;
    animation: fadeIn 300ms ease-in-out;
}

.tabs-material__panel--active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Matches Controls */
.matches-controls {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    align-items: end;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
}

.matches-controls--extended {
    grid-template-columns: 2fr 1fr 1fr;
}

.search-controls,
.filter-controls,
.action-controls {
    min-width: 0;
}

.optimization-controls {
    grid-column: 1 / -1;
    margin-top: 1rem;
}

/* Optimization Details */
.optimization-details {
    border: 1px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-medium);
    overflow: hidden;
}

.optimization-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: var(--md-surface-container);
    cursor: pointer;
    font-weight: 500;
    color: var(--md-on-surface);
    list-style: none;
    transition: background-color 200ms ease;
}

.optimization-summary:hover {
    background: var(--md-surface-container-high);
}

.optimization-summary::-webkit-details-marker {
    display: none;
}

.optimization-content {
    padding: 1.5rem;
    background: var(--md-surface);
}

.suggestions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.suggestions-placeholder {
    text-align: center;
    padding: 2rem;
    color: var(--md-on-surface-variant);
}

/* Table Container */
.table-container {
    background: var(--md-surface);
    border-radius: var(--md-shape-corner-medium);
    overflow: hidden;
    box-shadow: var(--md-elevation-1);
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 3rem;
    color: var(--md-on-surface-variant);
    font-size: 0.875rem;
}

.loading-state i {
    font-size: 1.25rem;
    color: var(--md-primary-40);
}

/* Pagination Container */
.pagination-container {
    margin-top: 1.5rem;
    display: flex;
    justify-content: center;
}

/* Pagination Styles */
.pagination-nav {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.pagination-btn {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--md-outline-variant);
    background: var(--md-surface);
    color: var(--md-on-surface);
    border-radius: var(--md-shape-corner-small);
    cursor: pointer;
    transition: all 200ms ease;
    font-size: 0.875rem;
    min-width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination-btn:hover {
    background: var(--md-surface-container-high);
    border-color: var(--md-primary-40);
}

.pagination-btn.active {
    background: var(--md-primary-40);
    color: var(--md-on-primary);
    border-color: var(--md-primary-40);
}

/* Search Counter */
.search-counter {
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
    margin-top: 0.5rem;
    text-align: center;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .tabs-material__nav {
        flex-direction: column;
    }
    
    .tabs-material__tab {
        flex: none;
    }
    
    .matches-controls {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .matches-controls--extended {
        grid-template-columns: 1fr;
    }
    
    .optimization-summary {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .tabs-material__tab {
        color: var(--md-on-surface-variant, #a4a4a4);
    }
    
    .tabs-material__tab--active {
        color: var(--md-primary-80, #d0bcff);
    }
}

/* Estilos específicos para el modal de optimización */
#optimizeCompanyModal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.5) !important;
    z-index: 10000 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#optimizeCompanyModal.hidden {
    display: none !important;
}

#optimizeCompanyModal .modal-content {
    background: white !important;
    border-radius: 8px !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
    max-width: 800px !important;
    width: 90vw !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    position: relative !important;
}
</style>

<?php 
// Incluir modales necesarios para la funcionalidad de matches
include VIEW_DIR . '/shared/modals.php'; 
?>
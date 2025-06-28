<?php
/**
 * Controlador de Dashboard
 * 
 * Este controlador maneja la página principal del panel de administración,
 * mostrando estadísticas, resúmenes y elementos visuales relevantes para
 * los diferentes tipos de usuarios según su rol.
 * 
 * @package B2B Conector
 * @version 1.0
 */

require_once 'BaseController.php';

class DashboardController extends BaseController {
    private $userModel;
    private $eventModel;
    private $companyModel;
    private $matchModel;
    private $appointmentModel;
    private $categoryModel;
    
    /**
     * Constructor
     * 
     * Inicializa los modelos necesarios y otras dependencias
     */
    public function __construct() {
        parent::__construct();
        
        Logger::info('Iniciando DashboardController');
        Logger::debug('Estado de sesión: ' . (session_status() === PHP_SESSION_ACTIVE ? 'activa' : 'inactiva'));
        Logger::debug('ID de sesión: ' . session_id());

         // Verificar contenido de la sesión
    if (isset($_SESSION)) {
        Logger::debug('Datos de sesión: ' . json_encode($_SESSION));
    } else {
        Logger::warning('No hay datos de sesión disponibles');
    }
        
        // La conexión ya se inicializa en BaseController
        // $this->db ya está disponible
        
        // Inicializar modelos
        $this->userModel = new User($this->db);
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->matchModel = new MatchModel($this->db);
        $this->appointmentModel = new Appointment($this->db);
        $this->categoryModel = new Category($this->db);

        Logger::debug('Verificando autenticación. isAuthenticated(): ' . (isAuthenticated() ? 'true' : 'false'));

        
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            Logger::warning('Usuario no autenticado, redirigiendo a login');
            setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        Logger::info('Usuario autenticado, continuando...');
    }
    
    /**
     * Mostrar el dashboard principal
     * 
     * Muestra widgets y estadísticas relevantes según el rol del usuario
     * 
     * @return void
     */
    public function index() {
        // Obtener el rol del usuario actual
        $userRole = $_SESSION['role'] ?? '';
        
        // Inicializar variables para la vista
        $totalCompanies = 0;
        $totalEvents = 0;
        $totalMatches = 0;
        $recentEvents = [];
        
        try {
            // Obtener estadísticas básicas
            $totalCompanies = $this->companyModel->count();
            $totalEvents = $this->eventModel->count();
            $totalMatches = $this->matchModel->count();
            
            Logger::debug('Dashboard stats loaded', [
                'companies' => $totalCompanies,
                'events' => $totalEvents,
                'matches' => $totalMatches
            ]);
            
            // Obtener eventos recientes
            $upcomingEvents = $this->eventModel->getCurrentEvents(['offset' => 0, 'limit' => 5]);
            
            // Formatear eventos para la vista
            $recentEvents = [];
            foreach ($upcomingEvents as $event) {
                $recentEvents[] = [
                    'id' => $event['event_id'],
                    'name' => $event['event_name'],
                    'date' => date('d/m/Y', strtotime($event['start_date'])) . ' - ' . 
                             date('d/m/Y', strtotime($event['end_date'])),
                    'is_active' => $event['is_active'] == 1
                ];
            }
            
            Logger::debug('Recent events loaded', ['count' => count($recentEvents)]);
            
            // Obtener estadísticas adicionales según el rol del usuario
            $stats = [
                'events' => [
                    'total' => $totalEvents,
                    'active' => $this->eventModel->count(['is_active' => 1]),
                    'upcoming' => $this->eventModel->countUpcomingEvents()
                ],
                'companies' => [
                    'total' => $totalCompanies,
                    'buyers' => $this->companyModel->count(['role' => 'buyer']),
                    'suppliers' => $this->companyModel->count(['role' => 'supplier'])
                ],
                'matches' => [
                    'total' => $totalMatches,
                    'pending' => $this->matchModel->count(['status' => 'pending']),
                    'accepted' => $this->matchModel->count(['status' => 'accepted']),
                    'rejected' => $this->matchModel->count(['status' => 'rejected'])
                ],
                'appointments' => [
                    'total' => $this->appointmentModel->count(),
                    'scheduled' => $this->appointmentModel->count(['status' => 'scheduled']),
                    'completed' => $this->appointmentModel->count(['status' => 'completed']),
                    'cancelled' => $this->appointmentModel->count(['status' => 'cancelled'])
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('Error cargando dashboard: ' . $e->getMessage());
            setFlashMessage('Error al cargar el dashboard. Inténtelo de nuevo más tarde.', 'danger');
        }
        
        // Generar acciones rápidas según el rol
        $quickActions = $this->generateQuickActions($userRole);

        $data = [
            'pageTitle' => 'Panel de Control',
            'moduleCSS' => 'dashboard',
            'moduleJS' => 'dashboard',
            'userRole' => $userRole,
            'stats' => $stats,
            'recentEvents' => $recentEvents,
            'quickActions' => $quickActions,
            'totalCompanies' => $totalCompanies,
            'totalEvents' => $totalEvents,
            'totalMatches' => $totalMatches
        ];

        $this->render('dashboard/index', $data, 'admin');
    }
        
    /**
     * Proporcionar datos estadísticos generales del sistema
     * 
     * Devuelve estadísticas generales, posiblemente en formato JSON para gráficos
     * 
     * @return void
     */
    public function stats() {
        // Verificar si la solicitud es AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Preparar estadísticas generales
        $stats = [
            'users' => [
                'total' => $this->userModel->count(),
                'by_role' => [
                    'admin' => $this->userModel->count(['role' => ROLE_ADMIN]),
                    'organizer' => $this->userModel->count(['role' => ROLE_ORGANIZER]),
                    'buyer' => $this->userModel->count(['role' => ROLE_BUYER]),
                    'supplier' => $this->userModel->count(['role' => ROLE_SUPPLIER]),
                    'user' => $this->userModel->count(['role' => 'user'])
                ]
            ],
            'events' => [
                'total' => $this->eventModel->count(),
                'active' => $this->eventModel->count(['is_active' => 1]),
                'inactive' => $this->eventModel->count(['is_active' => 0]),
                'upcoming' => $this->countUpcomingEvents()
            ],
            'companies' => [
                'total' => $this->companyModel->count(),
                'buyers' => $this->companyModel->count(['role' => 'buyer']),
                'suppliers' => $this->companyModel->count(['role' => 'supplier']),
                'active' => $this->companyModel->count(['is_active' => 1])
            ],
            'matches' => [
                'total' => $this->matchModel->count(),
                'pending' => $this->matchModel->count(['status' => 'pending']),
                'accepted' => $this->matchModel->count(['status' => 'accepted']),
                'rejected' => $this->matchModel->count(['status' => 'rejected']),
                'success_rate' => $this->calculateMatchSuccessRate()
            ],
            'appointments' => [
                'total' => $this->appointmentModel->count(),
                'scheduled' => $this->appointmentModel->count(['status' => Appointment::STATUS_SCHEDULED]),
                'completed' => $this->appointmentModel->count(['status' => Appointment::STATUS_COMPLETED]),
                'cancelled' => $this->appointmentModel->count(['status' => Appointment::STATUS_CANCELLED]),
                'attendance_rate' => $this->calculateAppointmentAttendanceRate()
            ],
            'top_events' => $this->getTopEvents(5)
        ];
        
        // Responder según el tipo de solicitud
        if ($isAjax) {
            // Para solicitud AJAX, responder con JSON
            header('Content-Type: application/json');
            echo json_encode($stats);
            exit;
        } else {
            // Para solicitud normal, mostrar página de estadísticas
            $data = [
                'pageTitle' => 'Estadísticas del Sistema',
                'moduleCSS' => 'dashboard',
                'moduleJS' => 'dashboard',
                'stats' => $stats
            ];
            
            $this->render('dashboard/stats', $data, 'admin');
        }
    }
    
    /**
     * Mostrar estadísticas específicas de un evento
     * 
     * @param int $id ID del evento
     * @return void
     */
    public function eventStats($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Verificar si la solicitud es AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Preparar estadísticas del evento
        $stats = [
            'event_info' => [
                'id' => $id,
                'name' => $this->eventModel->getEventName(),
                'start_date' => $this->eventModel->getStartDate(),
                'end_date' => $this->eventModel->getEndDate(),
                'is_active' => $this->eventModel->isActive(),
                'venue' => $this->eventModel->getVenue(),
                'available_tables' => $this->eventModel->getAvailableTables(),
                'meeting_duration' => $this->eventModel->getMeetingDuration()
            ],
            'companies' => [
                'total' => count($this->eventModel->getParticipants($id)),
                'buyers' => count($this->eventModel->getBuyers($id)),
                'suppliers' => count($this->eventModel->getSuppliers($id))
            ],
            'matches' => [
                'total' => $this->matchModel->count(['event_id' => $id]),
                'pending' => $this->matchModel->count(['event_id' => $id, 'status' => 'pending']),
                'accepted' => $this->matchModel->count(['event_id' => $id, 'status' => 'accepted']),
                'rejected' => $this->matchModel->count(['event_id' => $id, 'status' => 'rejected']),
                'success_rate' => $this->calculateEventMatchSuccessRate($id)
            ],
            'appointments' => [
                'total' => $this->appointmentModel->count(['event_id' => $id]),
                'scheduled' => $this->appointmentModel->count(['event_id' => $id, 'status' => Appointment::STATUS_SCHEDULED]),
                'completed' => $this->appointmentModel->count(['event_id' => $id, 'status' => Appointment::STATUS_COMPLETED]),
                'cancelled' => $this->appointmentModel->count(['event_id' => $id, 'status' => Appointment::STATUS_CANCELLED]),
                'attendance_rate' => $this->calculateEventAppointmentAttendanceRate($id)
            ],
            'categories' => $this->getEventCategoriesStats($id),
            'time_slots' => $this->getEventTimeSlotsStats($id),
            'attendance_days' => $this->getEventAttendanceDaysStats($id)
        ];
        
        // Responder según el tipo de solicitud
        if ($isAjax) {
            // Para solicitud AJAX, responder con JSON
            header('Content-Type: application/json');
            echo json_encode($stats);
            exit;
        } else {
            // Para solicitud normal, mostrar página de estadísticas del evento
            $data = [
                'pageTitle' => 'Estadísticas del Evento',
                'moduleCSS' => 'dashboard',
                'moduleJS' => 'dashboard',
                'event' => $event,
                'eventStats' => $eventStats
            ];
            
            $this->render('dashboard/event_stats', $data, 'admin');
        }
    }
    
    /**
     * Mostrar actividad reciente del usuario actual o todos los usuarios
     * 
     * @return void
     */
    public function userActivity() {
        // Verificar permisos para ver actividad de todos los usuarios
        $viewAllUsers = hasRole([ROLE_ADMIN, ROLE_ORGANIZER]);
        
        // Obtener ID del usuario actual
        $userId = $_SESSION['user_id'];
        
        // Preparar información de actividad
        $activity = [
            'user_info' => $this->getUserInfo($userId),
            'recent_logins' => [], // Se implementaría con una tabla de registro de accesos
            'appointments' => [],
            'matches' => []
        ];
        
        // Si es administrador u organizador, puede ver actividad de todos
        if ($viewAllUsers) {
            // Obtener actividad reciente de todos los usuarios
            $activity['recent_users'] = $this->userModel->getAll([], ['offset' => 0, 'limit' => 10]);
            $activity['recent_appointments'] = $this->appointmentModel->getAll([], ['offset' => 0, 'limit' => 10]);
            $activity['recent_matches'] = $this->matchModel->getAll([], ['offset' => 0, 'limit' => 10]);
        } else {
            // Obtener ID de la empresa asociada al usuario
            $companyId = $this->getCompanyIdByUser($userId);
            
            if ($companyId) {
                // Obtener citas recientes de la empresa
                $activity['appointments'] = $this->appointmentModel->getByCompany($companyId, null, null, ['offset' => 0, 'limit' => 10]);
                
                // Obtener matches recientes de la empresa según su rol
                $userRole = $_SESSION['role'] ?? '';
                if ($userRole === ROLE_BUYER) {
                    $activity['matches'] = $this->matchModel->getByBuyer($companyId, null, null, ['offset' => 0, 'limit' => 10]);
                } elseif ($userRole === ROLE_SUPPLIER) {
                    $activity['matches'] = $this->matchModel->getBySupplier($companyId, null, null, ['offset' => 0, 'limit' => 10]);
                }
            }
        }
        
        $data = [
            'pageTitle' => 'Actividad de Usuario',
            'moduleCSS' => 'dashboard',
            'moduleJS' => 'dashboard',
            'activity' => $activity
        ];
        
        $this->render('dashboard/user_activity', $data, 'admin');
    }
    
    /**
     * Mostrar eventos próximos
     * 
     * @return void
     */
    public function upcomingEvents() {
        // Obtener el rol del usuario actual
        $userRole = $_SESSION['role'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // Preparar lista de eventos próximos
        $upcomingEvents = [];
        $eventsWithSchedules = [];
        
        if (in_array($userRole, [ROLE_ADMIN, ROLE_ORGANIZER])) {
            // Administradores y organizadores ven todos los eventos próximos
            $upcomingEvents = $this->eventModel->getCurrentEvents();
            
            // Obtener citas programadas para cada evento
            foreach ($upcomingEvents as &$event) {
                $event['schedules_count'] = $this->appointmentModel->count(['event_id' => $event['event_id'], 'status' => Appointment::STATUS_SCHEDULED]);
                $event['pending_matches'] = $this->matchModel->count(['event_id' => $event['event_id'], 'status' => 'pending']);
            }
        } else {
            // Compradores y proveedores ven eventos en los que participan
            $companyId = $this->getCompanyIdByUser($userId);
            
            if ($companyId) {
                // Obtener eventos donde participa la empresa
                $participatingEvents = $this->companyModel->getEvents($companyId);
                
                // Filtrar solo eventos futuros o actuales
                $today = date('Y-m-d');
                $upcomingEvents = array_filter($participatingEvents, function($event) use ($today) {
                    return $event['end_date'] >= $today;
                });
                
                // Obtener citas programadas para cada evento
                foreach ($upcomingEvents as &$event) {
                    $schedules = $this->appointmentModel->getByCompany($companyId, $event['event_id'], Appointment::STATUS_SCHEDULED);
                    $event['schedules'] = $schedules;
                    $event['schedules_count'] = count($schedules);
                    
                    // Agregar a eventos con citas si tiene al menos una
                    if ($event['schedules_count'] > 0) {
                        $eventsWithSchedules[] = $event;
                    }
                }
            }
        }
        
        $data = [
            'pageTitle' => 'Eventos Próximos',
            'moduleCSS' => 'dashboard',
            'moduleJS' => 'dashboard',
            'upcomingEvents' => $upcomingEvents,
            'eventsWithSchedules' => $eventsWithSchedules
        ];
        
        $this->render('dashboard/upcoming_events', $data, 'admin');
    }
    
    /**
     * Mostrar acciones rápidas disponibles para el usuario según su rol
     * 
     * @return void
     */
    public function quickActions() {
        // Obtener el rol del usuario actual
        $userRole = $_SESSION['role'] ?? '';
        
        // Generar acciones rápidas según el rol
        $quickActions = $this->generateQuickActions($userRole);
        
        // Verificar si la solicitud es AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            // Para solicitud AJAX, responder con JSON
            header('Content-Type: application/json');
            echo json_encode(['quickActions' => $quickActions]);
            exit;
        } else {
            // Para solicitud normal, incluir la vista parcial
            $data = [
                'pageTitle' => 'Acciones Rápidas',
                'moduleCSS' => 'dashboard',
                'moduleJS' => 'dashboard',
                'quickActions' => $quickActions
            ];
            
            $this->render('dashboard/partials/quick_actions', $data, 'admin');
        }
    }
    
    /* Métodos privados auxiliares */
    
    /**
     * Cargar estadísticas para administradores
     * 
     * @param array &$stats Referencia al array de estadísticas a completar
     * @return void
     */
    private function loadAdminStats(&$stats) {
        // Estadísticas de usuarios
        $stats['users'] = [
            'total' => $this->userModel->count(),
            'admins' => $this->userModel->count(['role' => ROLE_ADMIN]),
            'organizers' => $this->userModel->count(['role' => ROLE_ORGANIZER]),
            'buyers' => $this->userModel->count(['role' => ROLE_BUYER]),
            'suppliers' => $this->userModel->count(['role' => ROLE_SUPPLIER])
        ];
        
        // Estadísticas de eventos
        $stats['events']['total'] = $this->eventModel->count();
        $stats['events']['active'] = $this->eventModel->count(['is_active' => 1]);
        $stats['events']['inactive'] = $this->eventModel->count(['is_active' => 0]);
        $stats['events']['upcoming'] = $this->eventModel->countUpcomingEvents();
        
        // Estadísticas de empresas
        $stats['companies']['total'] = $this->companyModel->count();
        $stats['companies']['buyers'] = $this->companyModel->count(['role' => 'buyer']);
        $stats['companies']['suppliers'] = $this->companyModel->count(['role' => 'supplier']);
        $stats['companies']['active'] = $this->companyModel->count(['is_active' => 1]);
        $stats['companies']['inactive'] = $this->companyModel->count(['is_active' => 0]);
        
        // Estadísticas de matches
        $stats['matches']['total'] = $this->matchModel->count();
        $stats['matches']['pending'] = $this->matchModel->count(['status' => 'pending']);
        $stats['matches']['accepted'] = $this->matchModel->count(['status' => 'accepted']);
        $stats['matches']['rejected'] = $this->matchModel->count(['status' => 'rejected']);
        
        // Estadísticas de citas
        $stats['appointments']['total'] = $this->appointmentModel->count();
        $stats['appointments']['scheduled'] = $this->appointmentModel->count(['status' => Appointment::STATUS_SCHEDULED]);
        $stats['appointments']['completed'] = $this->appointmentModel->count(['status' => Appointment::STATUS_COMPLETED]);
        $stats['appointments']['cancelled'] = $this->appointmentModel->count(['status' => Appointment::STATUS_CANCELLED]);
        
        // Calcular tasas de éxito
        $stats['matches']['success_rate'] = $this->matchModel->calculateSuccessRate();
        $stats['appointments']['attendance_rate'] = $this->appointmentModel->calculateAttendanceRate();
        
        // Obtener eventos más activos
        $stats['top_events'] = $this->eventModel->getTopEvents(5);
        
        // Alertas y problemas
        $stats['alerts'] = [
            'matches_without_schedule' => $this->matchModel->countMatchesWithoutSchedule(),
            'events_without_participants' => $this->eventModel->countWithoutParticipants(),
            'matches_without_participants' => $this->matchModel->countWithoutParticipants(),
            'events_without_categories' => $this->countEventsWithoutCategories()
        ];
    }

    /**
     * Cargar estadísticas para organizadores
     * 
     * @param array &$stats Referencia al array de estadísticas a completar
     * @return void
     */
    private function loadOrganizerStats(&$stats) {
        // Obtener IDs de eventos gestionados por este organizador
        // Nota: Esto sería una implementación real que relaciona usuarios con eventos
        // como es un ejemplo, mostramos todos los eventos
        $eventIds = $this->getOrganizerEvents($_SESSION['user_id']);
        
        if (empty($eventIds)) {
            // Si no hay eventos específicos, usar todos los eventos
            $stats['events']['total'] = $this->eventModel->count();
            $stats['events']['active'] = $this->eventModel->count(['is_active' => 1]);
            $stats['events']['upcoming'] = $this->countUpcomingEvents();
            
            // Contar participantes en todos los eventos
            $events = $this->eventModel->getAll();
            $buyerCount = 0;
            $supplierCount = 0;
            
            foreach ($events as $event) {
                $buyerCount += count($this->eventModel->getBuyers($event['event_id']));
                $supplierCount += count($this->eventModel->getSuppliers($event['event_id']));
            }
            
            $stats['companies']['buyers'] = $buyerCount;
            $stats['companies']['suppliers'] = $supplierCount;
            $stats['companies']['total'] = $buyerCount + $supplierCount;
            
            // Contar matches y citas en todos los eventos
            $stats['matches']['total'] = $this->matchModel->count();
            $stats['matches']['pending'] = $this->matchModel->count(['status' => 'pending']);
            $stats['matches']['accepted'] = $this->matchModel->count(['status' => 'accepted']);
            
            $stats['appointments']['total'] = $this->appointmentModel->count();
            $stats['appointments']['scheduled'] = $this->appointmentModel->count(['status' => Appointment::STATUS_SCHEDULED]);
            $stats['appointments']['completed'] = $this->appointmentModel->count(['status' => Appointment::STATUS_COMPLETED]);
        } else {
            // Contar estadísticas sólo para los eventos del organizador
            $conditions = ['event_id' => $eventIds];
            
            $stats['events']['total'] = count($eventIds);
            $stats['events']['active'] = $this->countActiveEvents($eventIds);
            $stats['events']['upcoming'] = $this->countUpcomingEvents($eventIds);
            
            // Contar participantes en los eventos del organizador
            $buyerCount = 0;
            $supplierCount = 0;
            
            foreach ($eventIds as $eventId) {
                $buyerCount += count($this->eventModel->getBuyers($eventId));
                $supplierCount += count($this->eventModel->getSuppliers($eventId));
            }
            
            $stats['companies']['buyers'] = $buyerCount;
            $stats['companies']['suppliers'] = $supplierCount;
            $stats['companies']['total'] = $buyerCount + $supplierCount;
            
            // Contar matches en los eventos del organizador
            $stats['matches']['total'] = $this->countMatchesForEvents($eventIds);
            $stats['matches']['pending'] = $this->countMatchesForEvents($eventIds, 'pending');
            $stats['matches']['accepted'] = $this->countMatchesForEvents($eventIds, 'accepted');
            
            // Contar citas en los eventos del organizador
            $stats['appointments']['total'] = $this->countAppointmentsForEvents($eventIds);
            $stats['appointments']['scheduled'] = $this->countAppointmentsForEvents($eventIds, Appointment::STATUS_SCHEDULED);
            $stats['appointments']['completed'] = $this->countAppointmentsForEvents($eventIds, Appointment::STATUS_COMPLETED);
        }
        
        // Alertas y pendientes
        $stats['pending_tasks'] = [
            'pending_matches' => $stats['matches']['pending'],
            'upcoming_appointments' => $stats['appointments']['scheduled']
        ];
    }
    
    /**
     * Cargar estadísticas para compradores y proveedores
     * 
     * @param array &$stats Referencia al array de estadísticas a completar
     * @param string $role Rol del usuario (buyer o supplier)
     * @return void
     */
    private function loadParticipantStats(&$stats, $role) {
        // Obtener ID de la empresa asociada al usuario
        $companyId = $this->getCompanyIdByUser($_SESSION['user_id']);
        
        if (!$companyId) {
            // Si no tiene empresa asociada, no hay estadísticas específicas
            return;
        }
        
        // Obtener eventos donde participa la empresa
        $participatingEvents = $this->companyModel->getEvents($companyId);
        $eventIds = array_column($participatingEvents, 'event_id');
        
        $stats['events']['total'] = count($eventIds);
        $stats['events']['active'] = $this->countActiveEvents($eventIds);
        $stats['events']['upcoming'] = $this->countUpcomingEvents($eventIds);
        
        // Estadísticas de matches según rol
        if ($role === ROLE_BUYER) {
            $stats['matches']['total'] = $this->matchModel->count(['buyer_id' => $companyId]);
            $stats['matches']['pending'] = $this->matchModel->count(['buyer_id' => $companyId, 'status' => 'pending']);
            $stats['matches']['accepted'] = $this->matchModel->count(['buyer_id' => $companyId, 'status' => 'accepted']);
            $stats['matches']['rejected'] = $this->matchModel->count(['buyer_id' => $companyId, 'status' => 'rejected']);
        } else {
            $stats['matches']['total'] = $this->matchModel->count(['supplier_id' => $companyId]);
            $stats['matches']['pending'] = $this->matchModel->count(['supplier_id' => $companyId, 'status' => 'pending']);
            $stats['matches']['accepted'] = $this->matchModel->count(['supplier_id' => $companyId, 'status' => 'accepted']);
            $stats['matches']['rejected'] = $this->matchModel->count(['supplier_id' => $companyId, 'status' => 'rejected']);
        }
        
        // Estadísticas de citas
        $stats['appointments']['total'] = count($this->appointmentModel->getByCompany($companyId));
        $stats['appointments']['scheduled'] = count($this->appointmentModel->getByCompany($companyId, null, Appointment::STATUS_SCHEDULED));
        $stats['appointments']['completed'] = count($this->appointmentModel->getByCompany($companyId, null, Appointment::STATUS_COMPLETED));
        $stats['appointments']['cancelled'] = count($this->appointmentModel->getByCompany($companyId, null, Appointment::STATUS_CANCELLED));
        
        // Próximos eventos y citas
        $stats['next_events'] = $this->getNextEventsForCompany($companyId, 3);
        $stats['next_appointments'] = $this->getNextAppointmentsForCompany($companyId, 3);
    }
    
    /**
     * Generar acciones rápidas según el rol del usuario
     * 
     * @param string $role Rol del usuario actual
     * @return array Lista de acciones rápidas disponibles
     */
    private function generateQuickActions($role) {
        $actions = [];
        
        // Acciones comunes para todos los usuarios autenticados
        $actions[] = [
            'title' => 'Mi Perfil',
            'url' => BASE_URL . '/users/profile',
            'icon' => 'user',
            'description' => 'Ver y editar mi perfil'
        ];
        
        $actions[] = [
            'title' => 'Cambiar Contraseña',
            'url' => BASE_URL . '/auth/change-password',
            'icon' => 'key',
            'description' => 'Actualizar mi contraseña'
        ];
        
        // Acciones específicas según el rol
        switch ($role) {
            case ROLE_ADMIN:
                $actions[] = [
                    'title' => 'Gestionar Usuarios',
                    'url' => BASE_URL . '/auth/admin/users',
                    'icon' => 'users',
                    'description' => 'Administrar usuarios del sistema'
                ];
                
                $actions[] = [
                    'title' => 'Crear Evento',
                    'url' => BASE_URL . '/events/create',
                    'icon' => 'calendar-plus',
                    'description' => 'Crear un nuevo evento'
                ];
                
                $actions[] = [
                    'title' => 'Gestionar Categorías',
                    'url' => BASE_URL . '/categories',
                    'icon' => 'tag',
                    'description' => 'Administrar categorías y subcategorías'
                ];
                
            
                $actions[] = [
                    'title' => 'Estadísticas Globales',
                    'url' => BASE_URL . '/dashboard/stats',
                    'icon' => 'chart-bar',
                    'description' => 'Ver estadísticas completas del sistema'
                ];
                break;
                
            case ROLE_ORGANIZER:
                $actions[] = [
                    'title' => 'Crear Evento',
                    'url' => BASE_URL . '/events/create',
                    'icon' => 'calendar-plus',
                    'description' => 'Crear un nuevo evento'
                ];
                
                $actions[] = [
                    'title' => 'Gestionar Eventos',
                    'url' => BASE_URL . '/events',
                    'icon' => 'calendar',
                    'description' => 'Administrar mis eventos'
                ];
                
                $actions[] = [
                    'title' => 'Generar Matches',
                    'url' => BASE_URL . '/events',
                    'icon' => 'handshake',
                    'description' => 'Generar matches para mis eventos'
                ];
                
                $actions[] = [
                    'title' => 'Programar Citas',
                    'url' => BASE_URL . '/appointments',
                    'icon' => 'clock',
                    'description' => 'Administrar citas de eventos'
                ];
                break;
                
            case ROLE_BUYER:
                // Obtener ID de la empresa asociada al usuario
                $companyId = $this->getCompanyIdByUser($_SESSION['user_id']);
                
                if ($companyId) {
                    $actions[] = [
                        'title' => 'Mis Citas',
                        'url' => BASE_URL . '/appointments/company/' . $companyId,
                        'icon' => 'calendar-check',
                        'description' => 'Ver mis citas programadas'
                    ];
                    
                    $actions[] = [
                        'title' => 'Mis Matches',
                        'url' => BASE_URL . '/matches/buyer/' . $companyId,
                        'icon' => 'handshake',
                        'description' => 'Ver mis matches con proveedores'
                    ];
                    
                    $actions[] = [
                        'title' => 'Administrar Requerimientos',
                        'url' => BASE_URL . '/requirements/company/' . $companyId,
                        'icon' => 'list-check',
                        'description' => 'Gestionar mis necesidades de compra'
                    ];
                }
                break;
                
            case ROLE_SUPPLIER:
                // Obtener ID de la empresa asociada al usuario
                $companyId = $this->getCompanyIdByUser($_SESSION['user_id']);
                
                if ($companyId) {
                    $actions[] = [
                        'title' => 'Mis Citas',
                        'url' => BASE_URL . '/appointments/company/' . $companyId,
                        'icon' => 'calendar-check',
                        'description' => 'Ver mis citas programadas'
                    ];
                    
                    $actions[] = [
                        'title' => 'Mis Matches',
                        'url' => BASE_URL . '/matches/supplier/' . $companyId,
                        'icon' => 'handshake',
                        'description' => 'Ver mis matches con compradores'
                    ];
                    
                    $actions[] = [
                        'title' => 'Administrar Ofertas',
                        'url' => BASE_URL . '/offers/company/' . $companyId,
                        'icon' => 'tag',
                        'description' => 'Gestionar mis productos y servicios'
                    ];
                }
                break;
        }
        
        return $actions;
    }
    
    /**
     * Contar eventos próximos
     * 
     * @param array|null $eventIds Lista específica de IDs de eventos a considerar
     * @return int Número de eventos próximos
     */
    private function countUpcomingEvents($eventIds = null) {
        return $this->eventModel->countUpcomingEvents($eventIds);
    }
    
    /**
     * Calcular tasa de éxito de los matches (proporción de matches aceptados)
     * 
     * @return float Porcentaje de éxito (0-100)
     */
    private function calculateMatchSuccessRate() {
        $totalMatches = $this->matchModel->count(['status' => ['pending', 'accepted', 'rejected']]);
        $acceptedMatches = $this->matchModel->count(['status' => 'accepted']);
        
        if ($totalMatches == 0) {
            return 0;
        }
        
        return round(($acceptedMatches / $totalMatches) * 100, 2);
    }
    
    /**
     * Calcular tasa de asistencia a citas (citas completadas vs programadas)
     * 
     * @return float Porcentaje de asistencia (0-100)
     */
    private function calculateAppointmentAttendanceRate() {
        $scheduledAppointments = $this->appointmentModel->count(['status' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED]]);
        $completedAppointments = $this->appointmentModel->count(['status' => Appointment::STATUS_COMPLETED]);
        
        if ($scheduledAppointments == 0) {
            return 0;
        }
        
        return round(($completedAppointments / $scheduledAppointments) * 100, 2);
    }
    
    /**
     * Obtener los eventos más activos según número de citas o matches
     * 
     * @param int $limit Número máximo de eventos a retornar
     * @return array Lista de eventos con sus estadísticas
     */
    private function getTopEvents($limit = 5) {
        $events = $this->eventModel->getAll(['is_active' => 1]);
        $topEvents = [];
        
        foreach ($events as $event) {
            $eventId = $event['event_id'];
            
            $matchesCount = $this->matchModel->count(['event_id' => $eventId]);
            $appointmentsCount = $this->appointmentModel->count(['event_id' => $eventId]);
            $participantsCount = count($this->eventModel->getParticipants($eventId));
            
            $topEvents[] = [
                'id' => $eventId,
                'name' => $event['event_name'],
                'start_date' => $event['start_date'],
                'end_date' => $event['end_date'],
                'matches' => $matchesCount,
                'appointments' => $appointmentsCount,
                'participants' => $participantsCount,
                'activity_score' => $matchesCount + ($appointmentsCount * 2) + $participantsCount
            ];
        }
        
        // Ordenar por puntuación de actividad en orden descendente
        usort($topEvents, function($a, $b) {
            return $b['activity_score'] - $a['activity_score'];
        });
        
        // Limitar número de resultados
        return array_slice($topEvents, 0, $limit);
    }
    
    /**
     * Contar matches que no tienen cita programada
     * 
     * @return int Número de matches sin cita
     */
    private function countWithoutSchedule() {
        $matchesWithoutSchedule = 0;
        $acceptedMatches = $this->matchModel->getAll(['status' => 'accepted']);
        
        foreach ($acceptedMatches as $match) {
            if (!$this->appointmentModel->existsForMatch($match['match_id'])) {
                $matchesWithoutSchedule++;
            }
        }
        
        return $matchesWithoutSchedule;
    }
    
    /**
     * Contar eventos sin participantes
     * 
     * @return int Número de eventos sin participantes
     */
    private function countEventsWithoutParticipants() {
        $count = 0;
        $events = $this->eventModel->getAll(['is_active' => 1]);
        
        foreach ($events as $event) {
            $participants = $this->eventModel->getParticipants($event['event_id']);
            
            if (empty($participants)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Contar eventos sin categorías asociadas
     * 
     * @return int Número de eventos sin categorías
     */
    private function countEventsWithoutCategories() {
        // En este sistema, las categorías son globales y no están asociadas directamente a eventos
        // Por lo tanto, este método siempre devuelve 0
        return 0;
    }
    
    /**
     * Obtener eventos activos de una lista de IDs
     * 
     * @param array $eventIds Lista de IDs de eventos
     * @return int Número de eventos activos
     */
    private function countActiveEvents($eventIds) {
        $count = 0;
        
        foreach ($eventIds as $eventId) {
            if ($this->eventModel->findById($eventId) && $this->eventModel->isActive()) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Contar matches para una lista de eventos
     * 
     * @param array $eventIds Lista de IDs de eventos
     * @param string|null $status Estado específico de los matches (opcional)
     * @return int Número total de matches
     */
    private function countMatchesForEvents($eventIds, $status = null) {
        $count = 0;
        
        foreach ($eventIds as $eventId) {
            if ($status) {
                $count += $this->matchModel->count(['event_id' => $eventId, 'status' => $status]);
            } else {
                $count += $this->matchModel->count(['event_id' => $eventId]);
            }
        }
        
        return $count;
    }
    
    /**
     * Contar citas para una lista de eventos
     * 
     * @param array $eventIds Lista de IDs de eventos
     * @param string|null $status Estado específico de las citas (opcional)
     * @return int Número total de citas
     */
    private function countAppointmentsForEvents($eventIds, $status = null) {
        $count = 0;
        
        foreach ($eventIds as $eventId) {
            if ($status) {
                $count += $this->appointmentModel->count(['event_id' => $eventId, 'status' => $status]);
            } else {
                $count += $this->appointmentModel->count(['event_id' => $eventId]);
            }
        }
        
        return $count;
    }
    
    /**
     * Calcular tasa de éxito de matches para un evento específico
     * 
     * @param int $eventId ID del evento
     * @return float Porcentaje de éxito (0-100)
     */
    private function calculateEventMatchSuccessRate($eventId) {
        $totalMatches = $this->matchModel->count(['event_id' => $eventId, 'status' => ['pending', 'accepted', 'rejected']]);
        $acceptedMatches = $this->matchModel->count(['event_id' => $eventId, 'status' => 'accepted']);
        
        if ($totalMatches == 0) {
            return 0;
        }
        
        return round(($acceptedMatches / $totalMatches) * 100, 2);
    }
    
    /**
     * Calcular tasa de asistencia a citas para un evento específico
     * 
     * @param int $eventId ID del evento
     * @return float Porcentaje de asistencia (0-100)
     */
    private function calculateEventAppointmentAttendanceRate($eventId) {
        $scheduledAppointments = $this->appointmentModel->count([
            'event_id' => $eventId, 
            'status' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED]
        ]);
        
        $completedAppointments = $this->appointmentModel->count([
            'event_id' => $eventId, 
            'status' => Appointment::STATUS_COMPLETED
        ]);
        
        if ($scheduledAppointments == 0) {
            return 0;
        }
        
        return round(($completedAppointments / $scheduledAppointments) * 100, 2);
    }
    

    /**
 * Obtener estadísticas de categorías para un evento
 * 
 * @param int $eventId ID del evento
 * @return array Estadísticas de categorías
 */
private function getEventCategoriesStats($eventId) {
    return $this->categoryModel->getEventStats($eventId);
}
    
    /**
     * Obtener estadísticas de slots de tiempo para un evento
     * 
     * @param int $eventId ID del evento
     * @return array Estadísticas de slots de tiempo
     */
    private function getEventTimeSlotsStats($eventId) {
        $stats = [];
        
        // Obtener todas las citas programadas para este evento
        $appointments = $this->appointmentModel->getByEvent($eventId);
        
        if (empty($appointments)) {
            return $stats;
        }
        
        // Agrupar por hora del día
        $hourlyDistribution = [];
        $dailyDistribution = [];
        
        foreach ($appointments as $appointment) {
            $dateTime = new DateTime($appointment['start_datetime']);
            $hour = (int) $dateTime->format('H');
            $date = $dateTime->format('Y-m-d');
            
            // Distribución por hora
            if (!isset($hourlyDistribution[$hour])) {
                $hourlyDistribution[$hour] = 0;
            }
            $hourlyDistribution[$hour]++;
            
            // Distribución por día
            if (!isset($dailyDistribution[$date])) {
                $dailyDistribution[$date] = 0;
            }
            $dailyDistribution[$date]++;
        }
        
        // Ordenar distribuciones
        ksort($hourlyDistribution);
        ksort($dailyDistribution);
        
        // Formatear para la salida
        $hourlyStats = [];
        foreach ($hourlyDistribution as $hour => $count) {
            $hourlyStats[] = [
                'hour' => sprintf('%02d:00', $hour),
                'count' => $count
            ];
        }
        
        $dailyStats = [];
        foreach ($dailyDistribution as $date => $count) {
            $dailyStats[] = [
                'date' => dateFromDatabase($date),
                'count' => $count
            ];
        }
        
        $stats['hourly'] = $hourlyStats;
        $stats['daily'] = $dailyStats;
        
        return $stats;
    }
    
    /**
 * Obtener estadísticas de días de asistencia para un evento
 * 
 * @param int $eventId ID del evento
 * @return array Estadísticas de días de asistencia
 */
private function getEventAttendanceDaysStats($eventId) {
    return $this->eventModel->getAttendanceDaysStats($eventId);
}
    
    /**
     * Obtener información de usuario
     * 
     * @param int $userId ID del usuario
     * @return array Información del usuario
     */
    private function getUserInfo($userId) {
        if (!$this->userModel->findById($userId)) {
            return [];
        }
        
        return [
            'id' => $this->userModel->getId(),
            'username' => $this->userModel->getUsername(),
            'name' => $this->userModel->getName(),
            'email' => $this->userModel->getEmail(),
            'role' => $this->userModel->getRole(),
            'is_active' => $this->userModel->isActive(),
            'registration_date' => $this->userModel->getRegistrationDate()
        ];
    }
    
    /**
     * Obtener eventos del organizador
     * 
     * @param int $userId ID del usuario organizador
     * @return array Lista de IDs de eventos
     */
    private function getOrganizerEvents($userId) {
        // Esta función obtendría los eventos asociados a un organizador
        // Como no hay una relación directa en la base de datos, en un sistema real
        // habría una tabla que relacione usuarios con eventos
        
        // Por ahora, simplemente devolvemos todos los eventos
        $events = $this->eventModel->getAll();
        return array_column($events, 'event_id');
    }
    
    /**
     * Obtener ID de empresa asociada a un usuario
     * 
     * @param int $userId ID del usuario
     * @return int|null ID de la empresa o null si no existe
     */
    private function getCompanyIdByUser($userId) {
        // En un sistema real, habría una relación entre usuarios y empresas
        // Por ejemplo, una tabla user_companies o un campo user_id en la tabla company
        
        // Como es un ejemplo, asumimos que el primer registro de la compañía está asociado al usuario
        $companies = $this->companyModel->getAll([], ['offset' => 0, 'limit' => 1]);
        
        if (!empty($companies)) {
            return $companies[0]['company_id'];
        }
        
        return null;
    }
    
   /**
 * Obtener próximos eventos para una empresa
 * 
 * @param int $companyId ID de la empresa
 * @param int $limit Límite de eventos a retornar
 * @return array Lista de próximos eventos
 */
private function getNextEventsForCompany($companyId, $limit = 3) {
    return $this->companyModel->getUpcomingEvents($companyId, $limit);
}
    
    /**
     * Obtener próximas citas para una empresa
     * 
     * @param int $companyId ID de la empresa
     * @param int $limit Límite de citas a retornar
     * @return array Lista de próximas citas
     */
    private function getNextAppointmentsForCompany($companyId, $limit = 3) {
        $now = date('Y-m-d H:i:s');
        
        $appointments = $this->appointmentModel->getByCompany(
            $companyId, 
            null, 
            Appointment::STATUS_SCHEDULED
        );
        
        // Filtrar solo citas futuras
        $futureAppointments = array_filter($appointments, function($appointment) use ($now) {
            return $appointment['start_datetime'] >= $now;
        });
        
        // Ordenar por fecha/hora de inicio
        usort($futureAppointments, function($a, $b) {
            return strcmp($a['start_datetime'], $b['start_datetime']);
        });
        
        // Limitar número de resultados
        return array_slice($futureAppointments, 0, $limit);
    }
}
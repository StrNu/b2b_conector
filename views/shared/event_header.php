<header class="modern-header" style="background-color: #2563eb;">
  <div class="header-container">
    <!-- Logo y marca -->
    <div style="display: flex; align-items: center; gap: 1rem;">
      <a href="<?= BASE_URL ?>/event-dashboard" class="header-logo">
        <svg class="nav__logo" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="16" cy="16" r="14" fill="currentColor" opacity="0.1"/>
          <path d="M8 12h6v8H8z" fill="currentColor"/>
          <path d="M18 12h6v8h-6z" fill="currentColor"/>
          <path d="M14 14h4v4h-4z" fill="currentColor" opacity="0.8"/>
          <circle cx="10" cy="8" r="2" fill="currentColor"/>
          <circle cx="22" cy="8" r="2" fill="currentColor"/>
          <path d="M10 10v2M22 10v2" stroke="currentColor" stroke-width="1"/>
        </svg>
        <span>Panel de Eventos</span>
      </a>
      
      <?php if (isset($event)): ?>
      <div style="display: flex; align-items: center; gap: 0.5rem; padding-left: 1rem; border-left: 1px solid rgba(255,255,255,0.3);">
        <i class="fas fa-calendar-alt" style="color: #bfdbfe;"></i>
        <span style="font-size: 0.875rem; font-weight: 500; color: #dbeafe;"><?= htmlspecialchars($event->getEventName()) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Menu desktop -->
    <nav class="header-nav">
      <a href="<?= BASE_URL ?>/event-dashboard" class="nav-link">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </a>

      <!-- Dropdown de usuario -->
      <div class="dropdown">
        <button class="dropdown-trigger">
          <i class="fas fa-user-circle"></i>
          <span style="display: none;"><?= htmlspecialchars(getEventUserEmail()) ?></span>
          <span class="user-badge <?= isEventAdmin() ? 'user-badge-admin' : 'user-badge-assistant' ?>">
            <?= isEventAdmin() ? 'Admin' : 'Asistente' ?>
          </span>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="dropdown-menu">
          <?php if (isEventAdmin() && isset($event)): ?>
          <a href="<?= BASE_URL ?>/events/view/<?= $event->getId() ?>" class="dropdown-item">
            <i class="fas fa-cog"></i>
            Administrar Evento
          </a>
          <div class="dropdown-divider"></div>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/event-dashboard/help" class="dropdown-item">
            <i class="fas fa-question-circle"></i>
            Ayuda
          </a>
          <div class="dropdown-divider"></div>
          <a href="<?= BASE_URL ?>/event-dashboard/logout" class="dropdown-item" style="color: #dc2626;">
            <i class="fas fa-sign-out-alt"></i>
            Cerrar Sesión
          </a>
        </div>
      </div>
    </nav>

    <!-- Botón mobile menu -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
      <i class="fas fa-bars"></i>
      <span class="sr-only">Menú</span>
    </button>
  </div>
</header>
<header class="modern-header">
  <div class="header-container">
    <!-- Logo y marca -->
    <a href="<?= BASE_URL ?>/dashboard" class="header-logo">
      <i class="fas fa-chart-line"></i>
      <span>Panel de Administración</span>
    </a>

    <!-- Menu desktop -->
    <nav class="header-nav">
      <a href="<?= BASE_URL ?>/dashboard" class="nav-link">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </a>
      
      <a href="<?= BASE_URL ?>/events" class="nav-link">
        <i class="fas fa-calendar-alt"></i>
        <span>Eventos</span>
      </a>
      
      <a href="<?= BASE_URL ?>/companies" class="nav-link">
        <i class="fas fa-building"></i>
        <span>Empresas</span>
      </a>

      <!-- Dropdown de usuario -->
      <div class="dropdown">
        <button class="dropdown-trigger">
          <i class="fas fa-user-circle"></i>
          <span><?= htmlspecialchars($_SESSION['name'] ?? 'Usuario') ?></span>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="dropdown-menu">
          <a href="<?= BASE_URL ?>/auth/change-password" class="dropdown-item">
            <i class="fas fa-key"></i>
            Cambiar Contraseña
          </a>
          <div class="dropdown-divider"></div>
          <a href="<?= BASE_URL ?>/auth/logout" class="dropdown-item" style="color: #dc2626;">
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
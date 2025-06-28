<!-- CSS principales -->
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/normalize.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modern-layout.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/flash-messages.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="shortcut icon" href="<?= BASE_PUBLIC_URL ?>/assets/images/favicon.ico" type="image/x-icon">

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- CSS de componentes comunes -->
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/forms.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/tables.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/buttons.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/notifications.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/tabs.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/pagination.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/auth.css">

<!-- CSS específicos de módulo -->
<?php if (isset($moduleCSS)): ?>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/<?= $moduleCSS ?>.css">
<?php endif; ?>

<!-- CSS adicionales específicos -->
<?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
    <?php foreach ($additionalCSS as $css): ?>
        <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/<?= $css ?>">
    <?php endforeach; ?>
<?php endif; ?>

<!-- CSS vanilla personalizado -->
<style>
    /* Reset y configuración base */
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding-top: 64px; /* Espacio para navbar fijo */
    }
    
    /* Componentes personalizados */
    .navbar-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
    }
    
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 200px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        border-radius: 0.5rem;
        z-index: 1;
        margin-top: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    
    .dropdown:hover .dropdown-content {
        display: block;
    }
    
    .dropdown-item {
        color: #374151;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        transition: background-color 0.2s;
    }
    
    .dropdown-item:hover {
        background-color: #f3f4f6;
    }
    
    .dropdown-divider {
        border-top: 1px solid #e5e7eb;
        margin: 4px 0;
    }
    
    /* Botón mobile menu */
    .mobile-menu-btn {
        display: none;
    }
    
    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: block;
        }
        
        .desktop-menu {
            display: none;
        }
        
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: inherit;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .mobile-menu.show {
            display: block;
        }
    }
    
    @media (min-width: 769px) {
        .desktop-menu {
            display: flex;
        }
        
        .mobile-menu {
            display: none !important;
        }
    }
</style>

<!-- JavaScript para funcionalidad -->
<script>
    // Toggle mobile menu
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        if (menu) {
            menu.classList.toggle('show');
        }
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('mobile-menu');
        const button = document.querySelector('.mobile-menu-btn');
        
        if (menu && !menu.contains(event.target) && button && !button.contains(event.target)) {
            menu.classList.remove('show');
        }
    });
</script>
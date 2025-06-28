<?php
/**
 * Optimized Asset Loading for B2B Conector
 * Refactored for performance and maintainability with Material Design 3
 * 
 * @version 3.0.0 - Material Design 3
 * @author Refactored CSS Architecture
 */

// Auto-include Material Design CSS if enabled
if (!isset($additionalCSS)) {
    $additionalCSS = [];
}
if (function_exists('getMaterialCSS') && useMaterialDesign()) {
    $additionalCSS = array_merge($additionalCSS, getMaterialCSS());
}

// Define CSS load order for optimal rendering
$coreStyles = [
    'core.css',        // Base styles, variables, reset
    'components.css',  // UI components
    'material-theme.css', // Material Design 3 components
    'layouts.css',     // Layout-specific styles
];

// Define external dependencies
$externalStyles = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Performance optimization: preload critical CSS
foreach ($coreStyles as $style) {
    echo '<link rel="preload" href="' . BASE_PUBLIC_URL . '/assets/css/' . $style . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
}
?>

<!-- Critical CSS (inline for performance) -->
<style>
/* Critical above-the-fold styles with Material Design 3 */
:root {
  --header-height: 4rem;
  --color-white: #ffffff;
  --color-gray-900: #212121;
  
  /* Material Design 3 Core Colors */
  --md-primary-40: #6750a4;
  --md-primary-80: #d0bcff;
  --md-surface-bright: #fef7ff;
  --md-surface-container: #f1ecf4;
  --color-primary-500: #9c27b0;
  --color-secondary-500: #673ab7;
  
  /* Transitions */
  --transition-base: 200ms ease;
  --transition-material: 200ms cubic-bezier(0.2, 0.0, 0, 1.0);
  
  /* Material Elevation */
  --md-elevation-1: 0px 1px 2px 0px rgba(0, 0, 0, 0.3), 0px 1px 3px 1px rgba(0, 0, 0, 0.15);
  --md-elevation-2: 0px 1px 2px 0px rgba(0, 0, 0, 0.3), 0px 2px 6px 2px rgba(0, 0, 0, 0.15);
}

body {
  font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  margin: 0;
  padding: 0;
  background: var(--md-surface-bright);
  color: var(--color-gray-900);
}

.app-layout {
  display: grid;
  grid-template-rows: var(--header-height) 1fr auto;
  min-height: 100vh;
}

.app-header {
  position: fixed;
  top: 0;
  width: 100%;
  height: var(--header-height);
  background: linear-gradient(135deg, var(--color-primary-500) 0%, var(--color-secondary-500) 100%);
  box-shadow: var(--md-elevation-2);
  backdrop-filter: blur(8px);
  z-index: 1000;
}

.app-main {
  padding-top: var(--header-height);
  background: var(--md-surface-bright);
  min-height: calc(100vh - var(--header-height));
}

/* Hide flash messages initially to prevent FOUC */
.flash-messages {
  opacity: 0;
  transition: opacity var(--transition-base);
}

.flash-messages.loaded {
  opacity: 1;
}
</style>

<!-- Core CSS Files -->
<?php foreach ($coreStyles as $style): ?>
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/<?= $style ?>">
<?php endforeach; ?>

<!-- External CSS -->
<?php foreach ($externalStyles as $style): ?>
<link rel="stylesheet" href="<?= $style ?>" crossorigin="anonymous">
<?php endforeach; ?>

<!-- Module-specific CSS (conditional loading) -->
<?php if (isset($moduleCSS)): ?>
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/<?= $moduleCSS ?>.css">
<?php endif; ?>

<!-- Additional CSS (conditional loading) -->
<?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
<?php foreach ($additionalCSS as $css): ?>
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/<?= $css ?>">
<?php endforeach; ?>
<?php endif; ?>

<!-- Favicon -->
<link rel="shortcut icon" href="<?= BASE_PUBLIC_URL ?>/assets/images/favicon.ico" type="image/x-icon">

<!-- Viewport and performance meta tags -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#2563eb">

<!-- JavaScript for enhanced functionality -->
<script>
/**
 * Modern JavaScript utilities for B2B Conector
 * Using vanilla JS for better performance and compatibility
 */

// Utility: DOM ready
function ready(fn) {
  if (document.readyState !== 'loading') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
}

// Utility: Query selector helpers
const $ = (selector, context = document) => context.querySelector(selector);
const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

// Enhanced dropdown functionality
ready(function() {
  const dropdowns = $$('.dropdown');
  
  dropdowns.forEach(dropdown => {
    const trigger = $('.dropdown__trigger', dropdown);
    const menu = $('.dropdown__menu', dropdown);
    
    if (!trigger || !menu) return;
    
    // Click to toggle (mobile-friendly)
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      // Close other dropdowns
      dropdowns.forEach(other => {
        if (other !== dropdown) {
          $('.dropdown__menu', other)?.classList.remove('dropdown__menu--open');
        }
      });
      
      menu.classList.toggle('dropdown__menu--open');
    });
    
    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target)) {
        menu.classList.remove('dropdown__menu--open');
      }
    });
    
    // Close on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        menu.classList.remove('dropdown__menu--open');
      }
    });
  });
  
  // Mobile menu functionality
  const mobileToggle = $('.mobile-menu-toggle');
  const mobileMenu = $('#mobile-menu');
  
  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', () => {
      mobileMenu.classList.toggle('show');
      mobileToggle.setAttribute('aria-expanded', 
        mobileMenu.classList.contains('show'));
    });
    
    // Close mobile menu on outside click
    document.addEventListener('click', (e) => {
      if (!mobileToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('show');
        mobileToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }
  
  // Flash messages auto-hide
  const flashMessages = $$('.flash-message');
  flashMessages.forEach((message, index) => {
    // Show with stagger effect
    setTimeout(() => {
      message.style.opacity = '1';
    }, index * 100);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      message.style.transform = 'translateX(100%)';
      setTimeout(() => message.remove(), 300);
    }, 5000 + (index * 100));
    
    // Manual close
    const closeBtn = $('.flash-message__close', message);
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        message.style.transform = 'translateX(100%)';
        setTimeout(() => message.remove(), 300);
      });
    }
  });
  
  // Show flash messages container
  const flashContainer = $('.flash-messages');
  if (flashContainer) {
    flashContainer.classList.add('loaded');
  }
  
  // Password field toggle
  const passwordToggles = $$('.password-field__toggle');
  passwordToggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      const input = toggle.previousElementSibling;
      const icon = $('i', toggle);
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
        toggle.setAttribute('aria-label', 'Hide password');
      } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
        toggle.setAttribute('aria-label', 'Show password');
      }
    });
  });
  
  // Form validation enhancement
  const forms = $$('form[data-validate]');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const requiredFields = $$('[required]', form);
      let isValid = true;
      
      requiredFields.forEach(field => {
        const errorMsg = $('.form-error', field.parentNode);
        
        if (!field.value.trim()) {
          field.classList.add('form-control--error');
          if (errorMsg) errorMsg.textContent = 'Este campo es requerido';
          isValid = false;
        } else {
          field.classList.remove('form-control--error');
          if (errorMsg) errorMsg.textContent = '';
        }
      });
      
      if (!isValid) {
        e.preventDefault();
      }
    });
  });
  
  // Table row click enhancement
  const clickableRows = $$('tr[data-href]');
  clickableRows.forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', (e) => {
      // Don't trigger if clicking on buttons or links
      if (e.target.closest('button, a')) return;
      
      const href = row.dataset.href;
      if (href) {
        window.location.href = href;
      }
    });
  });
});

// Global notification system
window.showNotification = function(message, type = 'info', duration = 5000) {
  const container = $('.flash-messages') || document.body;
  const notification = document.createElement('div');
  
  notification.className = `flash-message flash-message--${type}`;
  notification.innerHTML = `
    <div class="flash-message__icon">
      <i class="fas fa-${getIconForType(type)}"></i>
    </div>
    <div class="flash-message__content">${message}</div>
    <button class="flash-message__close" aria-label="Close notification">
      <i class="fas fa-times"></i>
    </button>
  `;
  
  container.appendChild(notification);
  
  // Show notification
  requestAnimationFrame(() => {
    notification.style.opacity = '1';
  });
  
  // Auto-hide
  if (duration > 0) {
    setTimeout(() => {
      notification.style.transform = 'translateX(100%)';
      setTimeout(() => notification.remove(), 300);
    }, duration);
  }
  
  // Manual close
  $('.flash-message__close', notification).addEventListener('click', () => {
    notification.style.transform = 'translateX(100%)';
    setTimeout(() => notification.remove(), 300);
  });
};

function getIconForType(type) {
  switch (type) {
    case 'success': return 'check-circle';
    case 'error': 
    case 'danger': return 'exclamation-circle';
    case 'warning': return 'exclamation-triangle';
    case 'info': return 'info-circle';
    default: return 'bell';
  }
}

// Export utilities to global scope for backward compatibility
window.B2B = {
  $,
  $$,
  ready,
  showNotification
};

// Material Design 3 initialization
ready(function() {
  console.log('Material Design 3 loaded successfully');
  
  // Add material theme class if not present
  if (!document.body.classList.contains('material-theme')) {
    document.body.classList.add('material-theme');
  }
  
  // Initialize Material components
  initMaterialComponents();
});

// Initialize Material Design 3 components
function initMaterialComponents() {
  // Ripple effect for material buttons
  const materialButtons = $$('.btn-material');
  materialButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
      `;
      
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 600);
    });
  });
}

// Add ripple animation CSS
const rippleCSS = document.createElement('style');
rippleCSS.textContent = `
@keyframes ripple {
  to {
    transform: scale(4);
    opacity: 0;
  }
}
`;
document.head.appendChild(rippleCSS);
</script>

<!-- Performance: DNS prefetch for external resources -->
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
<link rel="dns-prefetch" href="//fonts.googleapis.com">

<?php
// Load Google Fonts with optimal performance - Poppins & Montserrat
$googleFonts = 'family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . $googleFonts . '&display=swap">' . "\n";
?>
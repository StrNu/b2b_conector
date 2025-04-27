// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Cerrar notificaciones
    const closeButtons = document.querySelectorAll('.notification-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notification = this.closest('.notification');
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 300);
        });
    });
    
    // Auto-cerrar notificaciones después de 5 segundos
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.display = 'none';
                }
            }, 300);
        }, 5000);
    });
    
    // Toggle sidebar en dispositivos móviles
    const toggleSidebarBtn = document.querySelector('.toggle-sidebar');
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        });
    }
});
/**
 * Archivo principal de JavaScript para el Sistema de Gestión de Solicitudes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Activa los tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configuración de campos de fecha
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const today = new Date();
            if (input.id.includes('hasta')) {
                today.setDate(today.getDate() + 30); // Fecha hasta por defecto: 30 días después
            }
            const month = (today.getMonth() + 1).toString().padStart(2, '0');
            const day = today.getDate().toString().padStart(2, '0');
            input.valueAsDate = today;
        }
    });
    
    // Auto-cierre de alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000);
    });
    
    // Validación personalizada para formulario de registro
    const formRegistro = document.getElementById('form-registro');
    if (formRegistro) {
        formRegistro.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            return true;
        });
    }
    
    // Verificación del número de criterios en formularios de funcionalidad
    const formFuncionalidad = document.getElementById('form-nueva-funcionalidad');
    if (formFuncionalidad) {
        formFuncionalidad.addEventListener('submit', function(e) {
            const criterios = document.querySelectorAll('input[name="criterios[]"]');
            if (criterios.length < 3) {
                e.preventDefault();
                alert('Debe especificar al menos 3 criterios de aceptación');
                return false;
            }
            
            let criteriosVacios = false;
            criterios.forEach(criterio => {
                if (criterio.value.trim() === '') {
                    criteriosVacios = true;
                }
            });
            
            if (criteriosVacios) {
                e.preventDefault();
                alert('No puede haber criterios de aceptación vacíos');
                return false;
            }
            
            return true;
        });
    }
    
    // Activación automática de pestaña según URL
    const url = window.location.href;
    if (url.includes('#')) {
        const tabId = url.split('#')[1];
        const tab = document.getElementById(tabId + '-tab');
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    }
    
    // Función para marcar menú activo
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
});
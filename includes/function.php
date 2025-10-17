<?php
/**
 * Archivo con funciones útiles para el sistema
 */

/**
 * Obtiene el color de badge correspondiente al estado de una solicitud
 * 
 * @param string $estado Estado de la solicitud
 * @return string Nombre de clase CSS de color
 */
function getEstadoColor($estado) {
    switch ($estado) {
        case 'Abierto':
            return 'secondary';
        case 'En Progreso':
            return 'info';
        case 'Resuelto':
            return 'success';
        case 'Cerrado':
            return 'dark';
        case 'Archivado':
            return 'light';
        default:
            return 'secondary';
    }
}

/**
 * Genera un mensaje flash para mostrar en la siguiente página
 * 
 * @param string $mensaje Contenido del mensaje
 * @param string $tipo Tipo de alerta (success, danger, warning, info)
 */
function setFlashMessage($mensaje, $tipo) {
    $_SESSION['flash_message'] = $mensaje;
    $_SESSION['flash_type'] = $tipo;
}

/**
 * Muestra y elimina un mensaje flash si existe
 * 
 * @return string HTML del mensaje flash o string vacío
 */
function displayFlashMessage() {
    $output = '';
    if (isset($_SESSION['flash_message'])) {
        $output = '<div class="alert alert-' . $_SESSION['flash_type'] . ' alert-dismissible fade show" role="alert">';
        $output .= $_SESSION['flash_message'];
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $output .= '</div>';
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
    return $output;
}

/**
 * Valida un RUT chileno
 * 
 * @param string $rut RUT a validar
 * @return bool True si el RUT es válido, False en caso contrario
 */
function validarRut($rut) {
    if (!preg_match('/^\d{7,8}-[0-9kK]$/', $rut)) {
        return false;
    }
    
    // Obtener el número y el dígito verificador
    list($numero, $dv) = explode('-', $rut);
    $dv = strtoupper($dv);
    
    // Calcular el dígito verificador
    $suma = 0;
    $multiplicador = 2;
    
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $suma += $numero[$i] * $multiplicador;
        $multiplicador = $multiplicador == 7 ? 2 : $multiplicador + 1;
    }
    
    $resto = $suma % 11;
    $dvCalculado = 11 - $resto;
    
    if ($dvCalculado == 11) {
        $dvCalculado = '0';
    } elseif ($dvCalculado == 10) {
        $dvCalculado = 'K';
    } else {
        $dvCalculado = (string)$dvCalculado;
    }
    
    return $dv === $dvCalculado;
}

/**
 * Trunca un texto a un número específico de caracteres
 * 
 * @param string $texto Texto a truncar
 * @param int $longitud Longitud máxima
 * @param string $sufijo Sufijo a añadir si se trunca
 * @return string Texto truncado
 */
function truncarTexto($texto, $longitud = 100, $sufijo = '...') {
    if (strlen($texto) <= $longitud) {
        return $texto;
    }
    
    return substr($texto, 0, $longitud) . $sufijo;
}

/**
 * Convierte una fecha de base de datos a formato legible
 * 
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @return string Fecha en formato DD/MM/YYYY
 */
function formatearFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}
?>
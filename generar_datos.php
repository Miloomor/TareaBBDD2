<?php

// Incluir el autoloader de Composer para poder usar Faker
require_once 'vendor/autoload.php';

// Inicializar Faker, configurado para español de Chile para datos más localizados
$faker = Faker\Factory::create('es_CL');

// --- CONFIGURACIÓN ---
// Define cuántos registros quieres generar para cada tabla
$cantidad_usuarios = 20;
$cantidad_ingenieros = 8;
$cantidad_solicitudes_funcionalidad = 15;
$cantidad_reportes_error = 10;
// --- FIN DE LA CONFIGURACIÓN ---


/**
 * Función para generar un RUT chileno válido con su dígito verificador.
 * @return string RUT formateado (ej. 12.345.678-9)
 */
function generarRutValido() {
    $numero = random_int(1000000, 25000000);
    $sum = 0;
    $multiplier = 2;
    $num_copy = $numero;

    while ($num_copy > 0) {
        $digit = $num_copy % 10;
        $sum += $digit * $multiplier;
        $multiplier++;
        if ($multiplier > 7) {
            $multiplier = 2;
        }
        $num_copy = (int)($num_copy / 10);
    }

    $dv_calculado = 11 - ($sum % 11);
    $dv = '';
    if ($dv_calculado == 11) {
        $dv = '0';
    } elseif ($dv_calculado == 10) {
        $dv = 'K';
    } else {
        $dv = (string)$dv_calculado;
    }
    
    return number_format($numero, 0, ',', '.') . '-' . $dv;
}

// --- SECCIÓN 1: TÓPICOS (DATOS INICIALES) ---
// Estos son los datos base que nos proporcionaste.
echo "-- =======================================================\n";
echo "-- INSERTS PARA LA TABLA 'Topico'\n";
echo "-- =======================================================\n";
echo "INSERT INTO Topico (nombre) VALUES\n" .
     "('Backend'),\n" .
     "('Seguridad'),\n" .
     "('UX/UI'),\n" .
     "('Frontend'),\n" .
     "('Mobile'),\n" .
     "('DevOps'),\n" .
     "('Cloud'),\n" .
     "('Infraestructura'),\n" .
     "('Base de Datos'),\n" .
     "('Testing');\n\n";

// --- SECCIÓN 2: USUARIOS ---
echo "-- =======================================================\n";
echo "-- INSERTS PARA LA TABLA 'usuario' ($cantidad_usuarios registros)\n";
echo "-- =======================================================\n";
for ($i = 1; $i <= $cantidad_usuarios; $i++) {
    $rut = generarRutValido();
    $nombre = $faker->name();
    $email = $faker->unique()->safeEmail();
    $password = $faker->regexify('[a-zA-Z0-9]{12}'); // Genera una contraseña alfanumérica de 12 caracteres

    // Escapar comillas simples en los nombres para evitar errores de SQL
    $nombre_escapado = addslashes($nombre);

    echo "INSERT INTO usuario (rut, nombre, email, password) VALUES ('$rut', '$nombre_escapado', '$email', '$password');\n";
}
echo "\n";


// --- SECCIÓN 3: INGENIEROS Y SUS ESPECIALIDADES ---
echo "-- =======================================================\n";
echo "-- INSERTS PARA LA TABLA 'ingeniero' ($cantidad_ingenieros registros)\n";
echo "-- =======================================================\n";
for ($i = 1; $i <= $cantidad_ingenieros; $i++) {
    $rut = generarRutValido();
    $nombre = $faker->name();
    $email = $faker->unique()->safeEmail();
    $password = $faker->regexify('[a-zA-Z0-9]{12}');
    
    $nombre_escapado = addslashes($nombre);

    echo "INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('$rut', '$nombre_escapado', '$email', '$password');\n";
}
echo "\n";

echo "-- =======================================================\n";
echo "-- INSERTS PARA LA TABLA DE RELACIÓN 'ingeniero_topico'\n";
echo "-- =======================================================\n";
// Asocia cada ingeniero con 1 o 2 especialidades
$total_topicos = 10;
for ($id_ingeniero = 1; $id_ingeniero <= $cantidad_ingenieros; $id_ingeniero++) {
    $num_especialidades = $faker->numberBetween(1, 2);
    $topicos_asignados = [];
    
    for ($j = 0; $j < $num_especialidades; $j++) {
        do {
            $id_topico = $faker->numberBetween(1, $total_topicos);
        } while (in_array($id_topico, $topicos_asignados)); // Evita asignar el mismo tópico dos veces
        
        $topicos_asignados[] = $id_topico;
        echo "INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES ($id_ingeniero, $id_topico);\n";
    }
}
echo "\n";

// --- SECCIÓN 4: SOLICITUDES DE FUNCIONALIDAD Y ASIGNACIÓN ---
echo "-- =======================================================\n";
echo "-- INSERTS Y ASIGNACIONES PARA 'solicitud_funcionalidad' ($cantidad_solicitudes_funcionalidad registros)\n";
echo "-- =======================================================\n";
for ($i = 1; $i <= $cantidad_solicitudes_funcionalidad; $i++) {
    $titulo = $faker->sentence(21, false);
    $ambiente = $faker->randomElement(['Web', 'Móvil']);
    $resumen = $faker->paragraph(2);
    
    // Generar criterios (entre 3 y 5 criterios)
    $num_criterios = $faker->numberBetween(3, 5);
    $criterios_array = $faker->sentences($num_criterios);
    
    // Asignar a un usuario y tópico aleatorio
    $id_usuario = $faker->numberBetween(1, $cantidad_usuarios);
    $id_topico = $faker->numberBetween(1, $total_topicos);

    // Escapar datos para la consulta SQL
    $titulo_escapado = addslashes($titulo);
    $resumen_escapado = addslashes($resumen);

    echo "INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('$titulo_escapado', '$ambiente', '$resumen_escapado', $id_usuario, $id_topico);\n";
    
    // Insertar criterios en la tabla normalizada
    foreach ($criterios_array as $orden => $criterio) {
        $criterio_escapado = addslashes($criterio);
        $orden_num = $orden + 1;
        echo "INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES ($i, '$criterio_escapado', $orden_num);\n";
    }
    
    echo "CALL asignar_ing_funcionalidad($i);\n";
}
echo "\n";

// --- SECCIÓN 5: REPORTES DE ERROR Y ASIGNACIÓN ---
echo "-- =======================================================\n";
echo "-- INSERTS Y ASIGNACIONES PARA 'solicitud_error' ($cantidad_reportes_error registros)\n";
echo "-- =======================================================\n";
for ($i = 1; $i <= $cantidad_reportes_error; $i++) {
    $titulo = "Error en " . $faker->words(3, true);
    $descripcion = $faker->paragraph(3);

    // Asignar a un usuario y tópico aleatorio
    $id_usuario = $faker->numberBetween(1, $cantidad_usuarios);
    $id_topico = $faker->numberBetween(1, $total_topicos);

    $titulo_escapado = addslashes($titulo);
    $descripcion_escapada = addslashes($descripcion);

    echo "INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('$titulo_escapado', '$descripcion_escapada', $id_usuario, $id_topico);\n";
    echo "CALL asignar_ing_error($i);\n";
}
echo "\n";

?>


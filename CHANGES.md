# Cambios Realizados - Normalización de Base de Datos

## Resumen
Se ha normalizado la base de datos moviendo los criterios de funcionalidad desde un campo JSON en `Solicitud_Funcionalidad` a una nueva tabla relacional `Criterios_Funcionalidad`.

## Cambios en la Base de Datos (BBDD.sql)

### Tabla `Solicitud_Funcionalidad`
**ANTES:**
```sql
CREATE TABLE Solicitud_Funcionalidad (
    ...
    criterios JSON NOT NULL,
    ...
);
```

**DESPUÉS:**
```sql
CREATE TABLE Solicitud_Funcionalidad (
    ...
    -- Campo criterios eliminado
    ...
);
```

### Nueva Tabla `Criterios_Funcionalidad`
```sql
CREATE TABLE Criterios_Funcionalidad (
    id_criterio INT AUTO_INCREMENT PRIMARY KEY,
    id_funcionalidad INT NOT NULL,
    descripcion TEXT NOT NULL,  -- Cambiado de JSON a TEXT
    orden INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_funcionalidad) REFERENCES Solicitud_Funcionalidad(id_funcionalidad) ON DELETE CASCADE,
    INDEX idx_funcionalidad (id_funcionalidad)
);
```

### Datos de Ejemplo
Los datos de ejemplo ahora se insertan así:
```sql
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES (...);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (1, 'Criterio 1', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (1, 'Criterio 2', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (1, 'Criterio 3', 3);
```

## Cambios en PHP

### config/database.php
- **createFuncionalidad()**: Ahora usa transacciones para insertar en ambas tablas
- **updateFuncionalidad()**: Elimina criterios antiguos e inserta los nuevos
- **getCriteriosFuncionalidad()**: Nueva función para obtener criterios de una funcionalidad
- **verificarPropietarioFuncionalidad()**: Nueva función para validar propiedad

### Dashboards y Búsquedas
Todos los archivos que mostraban criterios han sido actualizados:
- `dashboard/usuario.php`
- `dashboard/ingeniero.php`
- `busqueda/simple.php`
- `busqueda/avanzada.php`

**ANTES:**
```php
$criterios = json_decode($f['criterios'], true);
```

**DESPUÉS:**
```php
$criterios = $db->getCriteriosFuncionalidad($f['id_funcionalidad']);
```

### generar_datos.php
Ahora genera datos para la estructura normalizada.

## Beneficios

1. ✅ **Normalización completa**: Los criterios están en una tabla separada
2. ✅ **Compatible con más sistemas**: Se usa TEXT en lugar de JSON
3. ✅ **Mejor rendimiento**: Se pueden indexar y buscar criterios individualmente
4. ✅ **Integridad de datos**: CASCADE DELETE elimina criterios huérfanos automáticamente
5. ✅ **Mantenibilidad**: Más fácil de mantener y extender

## Compatibilidad

Todos los cambios son compatibles con la aplicación existente. No se requieren cambios en:
- `solicitudes/funcionalidad/create.php`
- `solicitudes/funcionalidad/update.php`

Estos archivos siguen funcionando porque usan los métodos del DatabaseManager que ahora manejan la normalización internamente.

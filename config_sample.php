<?php

/// Nombre de la web. Ejemplo: kelinux
define('FS_NAME', '');

/// Descripción de la web. Ejemplo: Actualidad Linux.
define('FS_DESCRIPTION', '');

/* Directorio de la web.
 * Ejemplos:
 * - Si tienes esta web en /var/www, entonces el FS_PATH debe ser ''.
 * - Si tienes esta web en /var/www/feedstorm,
 *   entonces el FS_PATH debe ser '/feedstorm'.
 */
define('FS_PATH', '');

/// ¿Usas Google analytics? Pon aquí el identificador
define('FS_ANALYTICS', '');

/*
 * Configuración de MongoDB
 */
define('FS_MONGO_HOST', 'localhost');
define('FS_MONGO_DBNAME', '');

/// Número de historias máximo para cada feed y para la portada.
define('FS_MAX_STORIES', 50);

/*
 * Caducidad de los elementos, en segundos.
 * Se eliminarán:
 *  - los usuarios que no hayan vuelto en un máximo de FS_MAX_AGE segundos.
 *  - las historias con una edad superior a FS_MAX_AGE segundos.
 *  - las ediciones con una edad superior a FS_MAX_AGE segundos.
 *  - los elementos multimedia con una edad superior a FS_MAX_AGE segundos.
 */
define('FS_MAX_AGE', 2592000);

/*
 * Timeout de las conexiones curl.
 * El número de segundos máximo que se va a esperar antes de abortar
 * la descarga. Cuanto mayor el número, más tarda.
 */
define('FS_TIMEOUT', 3);

/*
 * Cuando se comprueba una fuente, se extraen las historias, y para
 * cada una se buscan imáganes asociadas. Pues esta constante
 * define el número máximo de imáganes descargadas de una sóla vez.
 * Cuanto mayor el número, más tarda.
 */
define('FS_MAX_DOWNLOADS', 5);

/*
 * Contraseña maestra, necesaria para tareas como eliminar fuentes.
 */
define('FS_MASTER_KEY', '');

?>
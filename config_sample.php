<?php

/// Nombre de la web. Ejemplo: kelinux
define('FS_NAME', '');

/// Descripción de la web. Ejemplo: Actualidad Linux.
define('FS_DESCRIPTION', '');

/* Directorio de la web.
 * Ejemplos:
 * - Si tienes esta web en /var/www, entonces el FS_PATH debe ser '/'.
 * - Si tienes esta web en /var/www/feedstorm,
 *   entonces el FS_PATH debe ser '/feedstorm/'.
 */
define('FS_PATH', '/');

/// ¿Usas Google analytics? Pon aquí el identificador
define('FS_ANALYTICS', '');

/*
 * Configuración de MongoDB
 */
define('FS_MONGO_HOST', 'localhost');
define('FS_MONGO_DBNAME', 'ponme_un_nombre');

/// Número de artículos en portada, búsquedas, etc...
define('FS_MAX_STORIES', 50);

/*
 * Caducidad de los elementos, en segundos.
 */
define('FS_MAX_AGE', 5184000);

/*
 * Timeout de las conexiones curl.
 * El número de segundos máximo que se va a esperar antes de abortar
 * la descarga. Cuanto mayor el número, más tarda.
 */
define('FS_TIMEOUT', 10);

/*
 * Contraseña maestra, necesaria para tareas como eliminar fuentes.
 */
define('FS_MASTER_KEY', '');

/*
 * True si quieres que te muestre el historial de consultas a mongodb.
 * Sólo para tareas de desarrollo.
 */
define('FS_DEBUG', FALSE);

/*
 * ¿Qué imagen quieres usar para la cabecera?
 * Debes estar alojada en view/img/
 */
define('FS_COVER', 'banner1.png');

?>
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
 * Configuración de Memcached.
 * El FS_CACHE_PREFIX es por si tienes varias webs usando el mismo servidor Memcached.
 * Asigna un prefijo distinto a cada una y evita problemas.
 */
define('FS_CACHE_HOST', 'localhost');
define('FS_CACHE_PORT', 11211);
define('FS_CACHE_PREFIX', '');

/// Número de historias máximo para cada feed y para la portada.
define('FS_MAX_STORIES', 50);

/* Hashtag que quieres usar para la web.
 * Si quieres usar #kelinux debes poner 'kelinux'.
 */
define('FS_TWITTER_HASHTAG', '');

?>

FeedStorm es un hibrido entre Google Reader y Pinterest,
pero anónimo y software libre.

FeedStorm usa RainTPL y MongoDB. para instalar MongoDB sigue este tutorial:
http://www.php.net/manual/es/mongo.installation.php

Una vez instalado y configurado MongoDB, copia FeedStorm a tu carpeta web,
dale permisos al servidor web para escribir sobre esa carpeta. Por ejemplo
si usas Ubuntu:
sudo chown -R www-data /var/www/donde-esté-feedstorm

Por último copia el archivo config-sample.php a config.php
y rellena los campos. Si tienes algún problema no dudes en informar:
https://github.com/NeoRazorX/feedstorm/issues
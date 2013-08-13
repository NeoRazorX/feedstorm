#!/bin/sh

php5 cron_multicore.php

echo "Paralelizando..."
cat tmp/feeds.txt | parallel --gnu 'php5 cron_multicore.php {}'
echo "FIN"
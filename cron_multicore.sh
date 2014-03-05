#!/bin/sh
start_time=$(date +%s)

php5 cron_multicore.php

echo "Paralelizando..."
cat tmp/feeds.txt | parallel --gnu 'php5 cron_multicore.php {}'

php5 cron_multicore.php END

finish_time=$(date +%s)
echo "Tiempo de ejecución: $((finish_time - start_time)) s"
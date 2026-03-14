<?php
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2019
 * @name            garbage collector
 */
# defining the maximum execution time to 1 hour
ini_set('max_execution_time', '3600');

# defining the socket timeout to 1 min
ini_set('default_socket_timeout', '60');

# defining the maximum memory limit 
ini_set('memory_limit', '-1');

# disabling remote file include
ini_set("allow_url_fopen", '1');
ini_set("allow_url_include", '0');

# defining the default time zone
date_default_timezone_set("UTC");

# empty folders big logs
exec('find /var/spool/iresponse/bad/ -maxdepth 1 -type f -name "*" -delete');
exec('find /var/log/pmta/ -maxdepth 1 -type f -name "pmta.log.*" -delete');
exec('find /var/log/pmta/ -maxdepth 1 -type f -name "pmtahttp.log.*" -delete');
exec('find /var/log/ -maxdepth 1 -type f -name "3proxy.log.*" -delete');
exec('find /var/log/ -maxdepth 1 -type f -name "secure-*" -delete');
exec('find /var/log/ -maxdepth 1 -type f -name "messages-*" -delete');
exec('find /var/log/ -maxdepth 1 -type f -name "spooler-*" -delete');

# empty proxy log files
exec('> /var/log/3proxy.log');

# empty pmta log files
exec('> /var/log/pmta/pmta.log');
exec('> /var/log/pmta/pmtahttp.log');

# empty httpd log files
exec('> /var/log/httpd/ssl_access_log');
exec('> /var/log/httpd/ssl_request_log');
exec('> /var/log/httpd/error_log');
exec('> /var/log/httpd/access_log');

# empty various logs
exec('> /var/log/btmp');
exec('> /var/log/secure');

# clear RAM and cache
exec('sync ; echo 3 > /proc/sys/vm/drop_caches');
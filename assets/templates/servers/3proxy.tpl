nserver 8.8.8.8
nserver 8.8.4.4
nscache 65536

daemon

timeouts 1 5 30 60 180 1800 15 60
log /var/log/3proxy.log D
logformat "- +_L%t.%. %N.%p %E %U %C:%c %R:%r %O %I %h %T"
archiver gz /usr/bin/gzip %F
rotate 3
maxconn 1000
authcache user 60

# authentication
$p_auth

# http proxies
$p_http_proxies

# socks proxies
$p_socks_proxies
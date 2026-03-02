stack lemp con docker compose
proyecto usando las tecnolgias comunes en un stack lemp

tecnologias

docker

docker compose

nginx alpine

mysql 8.0

php 8.2

Usuario-> index.php->http request->nginx -> fastcgi->php-fpm->mysql->html->php-nginx algo asi creo

se uso dos redes backends apra php y mysql y frontendd para nginx y php, no sé uso una sola red por seguridad y lest privilegds

requisitos:
- docker(docker compose)
instalacion:
instalar docker compose
ir a cd ~/proyecto-lemp
ejecutar docker compose up -d --build
verificar si see levanto con docker compsoe ps
hace rpruebas de coencion con docker exec lemp-nginx ping mysql
docker exec lemp-nignx ping php
6 uso
ir a http://localhost:8080/index.php o era /php/src/index.php? ambos valen verdad por lo de index en
nginx.conf
77 carcterizticas tecnicas
health checks configurados
networking cona islamiento
persistencia de datos
depend on con condiciones
8 problemas comunes
puerto 8080 ocupado
9 proximas mejoras
secrets managemt
https/ssl
backup automatizado
logging centralizado
docker compose override


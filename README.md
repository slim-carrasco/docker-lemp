# Stack LEMP con Docker Compose
Proyecto usando las tecnologías comunes en un stack LEMP haciendo uso de contenedores y orquestación con Docker Compose listo para usarse, con administración de redes para seguridad con imagen pequeña para rápido deploy

# Tecnologías
|Tecnologías|Versión|Propósito|
|-----------|-------|---------|
|Docker|29.2.1, build a5c7197|Contenerizar|
|Docker Compose|5.0.2|Orquestar|
|Nginx alpine|1.29.4|Servidor web, peticiones|
|MySql|8.0|Almacenamiento de datos|
|PHP-FPM|8.2|Procesar lógica del servidor, comunicarse con Nginx, Gestionar procesos|

# Requisitos
- Docker 29.2.1 o superior
- Docker Compose 5.0.2 o superior

# Instalación y uso
- Clonar repo
``` bash
git clone https://github.com/slim-carrasco/docker-lemp.git
```
- Configurar .env
Del archivo .env.example se debe copiar las variables al archivo .env los valores como contraseña root se pueden cambiar
``` env
MYSQL_ROOT_PASSWORD=contraseña_root
MYSQL_DATABASE=db
MYSQL_USER=usuario_aqui
MYSQL_PASSWORD=contraseña_aqui
```
- Levantar stack
```bash
docker compose up -d --build
```
- Verificar
El estado debe ser up y healthy, ademas de las pruebas de conexion
``` bash
docker compose ps
docker exec lemp-nignx ping mysql
docker exec lemp-nginx ping php
```
- URL
./web → /usr/share/nginx/html → archivos estáticos, accesible en http://localhost/
./php/src → /var/www/html → archivos PHP, accesible en http://localhost/archivo.php

> [!WARNING]
> El archivo .env nunca debe subir a GitHub

# Arquitectura
- Request: Usuario -> Navegador -> HTTP request -> Nginx -> FastCGI -> PHP-FPM -> MySql
- Response: MySql -> .html/.php -> PHP-FPM -> FastCGI -> Nginx -> HTTP response -> Usuario
- Nota: Nginx y PHP se encuentran en una red(bridge) frontend y PHP y MySql se encuentra en la red backend esto por seguridad y lest privile

# Variables de entorno
|Variable|Requerido|Descripción|
|----|----|---|
|MYSQL_ROOT_PASSWORD|Sí| Contraseña para ingresar como root al contenedor|
|MYSQL_DATABASE|Sí| Base de datos creada en la creación del contenedor y no desde dentro|
|MYSQL_USER|Sí| Usuario extra aparte de root|
|MYSQL_PASSWORD|Sí|Contraseña del usuario no root|

# Problemas comunes
- Puerto 8080/80 ocupado
```bash
ss -tulpn | grep :80
sudo kill -9 pid
```
En caso de que el puerto sea de un contenedor
```bash
docker compose down
docker rm nombre_contenedor
```
- Puerto PHP ocupado
Solución ver los puertos ocupados y matar el proceso
```bash
ss -tulpn | grep :9000
sudo kill -9 pid
```
En caso de que el puerto ocupado sea de un contenedor hacer
```bash
docker compose down
docker rm nombre_contenedor
```
- Permisos denegados
Casos donde un scrpit requiera permisos
```bash
chmod +x nombre_archivo
```
- Permisos denegados en volúmenes
Si los archivos montados no tienen los permisos correctos
```bash
sudo chown -R 1000:1000 carpeta/
```
1000:1000 es el UID/GID por defecto del usuario dentro del contenedor

# Próximas mejoras
- [ ] Add docker.compose.override.yml
- [ ] Add secrets management
- [ ] Add HTTPS/SSL support
- [ ] Add automated backup
- [ ] Add centralized logging

# Características técnicas
- Healthchecks configurados -> verificar que el contenedor este sano antes de considerarlo listo 
- Networking con aislamiento -> seguridad y least privilege
- Persistencia de datos ->al eliminar el contenedor los datos persisten
- Depend_on con condiciones -> espera que el contenedor dependiente este healthy

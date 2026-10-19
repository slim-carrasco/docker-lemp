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
Usar en la raiz para copiar el .env
```bash
cp .env.example .env
```
Del archivo .env.example se debe copiar las variables al archivo .env los valores como contraseña root se pueden cambiar antes del primer arranque
``` env
MYSQL_ROOT_PASSWORD=contraseña_root
MYSQL_DATABASE=nombre_db
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
docker exec lemp-nginx ping -c 3 mysql
docker exec lemp-nginx ping -c 3 php
```
El ping a mysql debe fallar, lo que comprueba las redes separadas

- URL
./web → /usr/share/nginx/html → archivos estáticos, accesible en http://localhost/ es la pagina principal que es index.html
./php/src → /var/www/html → archivos PHP, accesible en http://localhost/health.php debe decir OK y http://localhost/demo_db.php verifica conexion a mysql y crea una tabla lectura y escritura

http://localhost/health.php prueba la cadena: nginx -> FastCGI -> PHP-FPM -> MySql

> [!WARNING]
> El archivo .env nunca debe subir a GitHub
> Los valores solo se aplican al primer arranque, si se modifica debe borrarse el volumen
> MYSQL_ROOT no puede ser root
- Para borrar el volumen que destruye los datos y levantar
```bash
docker compose down -v
docker compose up -d
```
## Arquitectura
- Request: Usuario -> Navegador -> HTTP request -> Nginx -> FastCGI -> PHP-FPM -> MySql
- Response: MySql -> PHP-FPM -> FastCGI -> Nginx -> HTTP response -> Usuario
- Nota 1: Nginx y PHP se encuentran en una red(bridge) frontend y PHP y MySql se encuentra en la red backend esto por seguridad y least privilege
- Nota 2: Los .html los sirve nginx sin pasar por PHP
## Variables de entorno
|Variable|Requerido|Descripción|
|----|----|---|
|MYSQL_ROOT_PASSWORD|Sí| Contraseña para ingresar como root al contenedor de mysql|
|MYSQL_DATABASE|Sí| Base de datos creada en la creación del contenedor y no desde dentro|
|MYSQL_USER|Sí| Usuario extra aparte de root|
|MYSQL_PASSWORD|Sí|Contraseña del usuario no root|

## Persistencia y datos

Preserva los datos
```bash
docker compose down
```
Borra los datos
```bash
docker compose down -v
```

Para limpiar el demo de http://localhost/demo_db.php
```bash
docker compose exec mysql mysql -u root -p
```
Luego
```sql
USE nombre_db;
DROP TABLE productos;
```

## Problemas comunes (linux)
- Puerto 8080/80 ocupado
```bash
ss -tulpn | grep :80
```
En caso de que el puerto sea de un contenedor
```bash
docker compose down
docker rm nombre_contenedor
```
- Puerto 3306 ocupado
Solución ver los puertos ocupados, una vez identificado parar el servicio
```bash
ss -tulpn | grep :3306
```

En caso de que el puerto ocupado sea de un contenedor hacer
```bash
docker compose down
docker rm nombre_contenedor
```
- Permisos denegados
Casos donde un script requiera permisos
```bash
chmod +x nombre_archivo
```
- Permisos denegados en volúmenes
Si los archivos montados no tienen los permisos correctos
```bash
sudo chown -R 1000:1000 carpeta/
```
1000:1000 es el UID/GID por defecto del usuario dentro del contenedor
Nota: Ajusta el dueño de la carpeta al usuario que necesita escribir

- Access denied
Volumen con credenciales viejas, comparar el .env con el volumen

- Fatal error con usuario vacio
El servicio de PHP no recibe las variables, comprobar con:
```bash
docker compose exec php printenv | grep MYSQL
```

## Advertencias
> [!WARNING]
> demo_db.php escribe en la base de datos no debe exponerse en un servidor público
> up sin la -d termina el stack con Ctrl+C

# Próximas mejoras
- [ ] Add docker-compose.override.yml
- [ ] Add secrets management
- [ ] Add HTTPS/SSL support
- [ ] Add automated backup
- [ ] Add centralized logging

# Características técnicas
- Healthchecks configurados -> verificar que el contenedor este sano antes de considerarlo listo
- Networking con aislamiento -> seguridad y least privilege
- Persistencia de datos ->al eliminar el contenedor los datos persisten salvo con "down -v"
- Depend_on con condiciones -> espera que el contenedor dependiente este healthy

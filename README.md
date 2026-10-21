# Recetario - Stack LEMP con Docker Compose

Aplicación web para guardar recetas con escalado de porciones, costos, modo cocina con temporizadores y búsqueda por ingredientes. Corre sobre un stack LEMP (Linux, Nginx, MySQL, PHP-FPM) en contenedores, con redes separadas. Funciona sin conexión a internet.

## Funciones

- Recetas con ingredientes (cantidad, unidad y precio), pasos con temporizador, notas y errores registrados.
- Escalado de porciones: cantidades y costos se recalculan al cambiar las porciones.
- Costo total y por porción en el detalle de cada receta.
- Modo cocina: un paso a la vez, con temporizador por paso.
- Ficha imprimible de cada receta (botón "Imprimir ficha") con las cantidades de las porciones elegidas; permite guardarla como PDF.
- Búsqueda por nombre y por ingredientes que tienes (`pollo, papa` muestra las recetas que contienen ambos).
- Carpetas con color para organizar recetas.
- Pestaña Costos: precios de referencia por categoría.
- Importar JSON (una receta o varias) y Exportar todo (descarga un `.json`).
- Marca de altitud en recetas que requieren ajuste.

## Tecnologías

| Componente | Probado con | Propósito |
|---|---|---|
| Docker | 29.2.1 | Contenerizar |
| Docker Compose | 5.0.2 | Orquestar |
| Nginx (alpine) | 1.29.4 | Servir archivos estáticos y pasar `.php` a PHP-FPM |
| MySQL | 8.0 (8.0.45) | Almacenar los datos |
| PHP-FPM | 8.2 (8.2.34) | Lógica del servidor y API |
| React 18, Babel, íconos Tabler | locales en `web/vendor/` | Interfaz, sin paso de compilación |

"Probado con" indica las versiones usadas durante el desarrollo, no un mínimo garantizado.

## Estructura del proyecto

```
.
|-- docker-compose.yml
|-- .env.example
|-- nginx/
|   `-- nginx.conf
|-- mysql/
|   |-- init_recetario.sql        # esquema (6 tablas), se ejecuta una sola vez
|   `-- seed_recetario.sql        # datos de ejemplo (21 recetas y 22 precios)
|-- php/
|   |-- Dockerfile
|   `-- src/
|       |-- health.php            # chequeo de salud
|       |-- demo_db.php           # demo del stack (escribe en la base)
|       `-- recetario/
|           |-- api.php           # router
|           |-- db.php            # conexión PDO
|           |-- lib.php           # funciones compartidas
|           `-- handlers/         # state, recetas, carpetas, precios, import, export
`-- web/
    |-- index.html                # página principal
    |-- recetario.html            # interfaz del recetario
    `-- vendor/                   # React, Babel e íconos (sin internet)
```

## Requisitos

- Docker y Docker Compose.
- No necesita internet para ejecutarse.

## Instalación

1. Clonar el repositorio:
```bash
   git clone https://github.com/slim-carrasco/docker-lemp.git
   cd docker-lemp
```

2. Crear el `.env` desde el ejemplo y editar los valores **antes del primer arranque**:
```bash
   cp .env.example .env
```
```env
   MYSQL_ROOT_PASSWORD=contraseña_root
   MYSQL_DATABASE=recetario
   MYSQL_USER=usuario_aqui
   MYSQL_PASSWORD=contraseña_aqui
```

3. Levantar el stack:
```bash
   docker compose up -d --build
```

4. Esperar unos 30 segundos hasta que `docker compose ps` muestre los tres servicios `healthy` y abrir `http://localhost/recetario.html`.

> [!WARNING]
> - `MYSQL_DATABASE` debe ser `recetario`: el esquema crea esa base y el recetario se conecta a la que indique esta variable.
> - `MYSQL_USER` no puede ser `root`. Evita `$`, comillas y espacios al final en los valores.
> - Los valores del `.env` solo se aplican al primer arranque, cuando el volumen está vacío. Si los cambias después, MySQL los ignora (ver "Problemas comunes").
> - El archivo `.env` nunca debe subir a GitHub.
> - Usa `up -d`. Sin `-d`, el stack ocupa la terminal y Ctrl+C lo detiene.

## Verificación

```bash
docker compose ps
```
Los tres servicios deben estar `healthy`. Luego:

| URL | Debe mostrar |
|---|---|
| `http://localhost/` | La página principal (`web/index.html`) |
| `http://localhost/health.php` | `OK` (prueba nginx, FastCGI, PHP-FPM y MySQL) |
| `http://localhost/recetario/api.php?entity=state` | JSON con `carpetas` y `recetas` |
| `http://localhost/recetario.html` | El recetario, con 21 recetas y sin "Error de conexión" |

Si `health.php` responde `FALLO`, el detalle está en `docker compose logs php`.

## Datos de ejemplo

En el primer arranque MySQL ejecuta, en orden alfabético, los archivos de `/docker-entrypoint-initdb.d/`:

1. `init_recetario.sql`: crea las 6 tablas.
2. `seed_recetario.sql`: carga 21 recetas y 22 precios de ejemplo.

- Solo se ejecutan con el volumen vacío. En una instalación existente no se repiten.
- Si no quieres datos de ejemplo, borra la línea de `seed_recetario.sql` en `docker-compose.yml` antes del primer arranque.
- El primer arranque con datos tarda más. Si el recetario muestra "Error de conexión", espera unos segundos y recarga.

## Base de datos

| Tabla | Contenido |
|---|---|
| `carpetas` | Nombre, descripción y color |
| `recetas` | Nombre, descripción, porciones base, altitud, notas y fecha |
| `ingredientes` | Nombre, cantidad, unidad y precio, por receta |
| `pasos` | Título, descripción y temporizador (segundos), por receta |
| `errores` | Texto libre, por receta |
| `precios` | Precios de referencia por categoría (pestaña Costos) |

Relaciones: `ingredientes`, `pasos` y `errores` apuntan a `recetas` y se borran con ella (`ON DELETE CASCADE`). `recetas` apunta a `carpetas`; si borras una carpeta, sus recetas quedan sin carpeta (`ON DELETE SET NULL`).

El `precio` de un ingrediente es el costo de la cantidad usada en esa receta, no el precio por unidad de compra. No está enlazado a la tabla `precios`.

## API

Base: `/recetario/api.php?entity=<nombre>[&id=<n>]`. Responde JSON. Los errores devuelven `{"error": "..."}` con el código HTTP correspondiente.

| `entity` | Métodos | Descripción |
|---|---|---|
| `state` | GET | Carpetas y lista de recetas (con conteos e ingredientes en texto) |
| `receta` | GET `&id=` | Una receta con ingredientes, pasos y errores |
| `recetas` | POST, PUT `&id=`, DELETE `&id=` | Crear, editar y borrar recetas |
| `carpetas` | POST, PUT `&id=`, DELETE `&id=` | Gestionar carpetas |
| `precios` | GET, POST, PUT `&id=`, DELETE `&id=` | Gestionar precios de referencia |
| `import` | POST | Importar un arreglo de recetas |
| `export` | GET | Exportar todas las recetas sin ids, con el nombre de la carpeta |

Formato de una receta para importar:

```json
{
  "nombre": "Carlota de durazno",
  "descripcion": "Postre frío sin horno.",
  "carpeta": "Postres fríos",
  "porciones_base": 6,
  "altitud": true,
  "notas": "Texto libre",
  "ingredientes": [
    { "nombre": "Leche condensada", "cantidad": 150, "unidad": "g", "precio": 1.5 }
  ],
  "pasos": [
    { "titulo": "Refrigerar", "descripcion": "Toda la noche.", "timer": 14400 }
  ],
  "errores": ["Primera vez quedó muy líquida"]
}
```

Solo `nombre` es obligatorio. Si `carpeta` no existe, se crea con ese nombre.

## Copias de seguridad

**Recetas, en un archivo `.json`:** botón **Exportar todo** en la lista de recetas. No incluye la pestaña Costos. Para restaurar, usa **Importar JSON** en una instalación vacía.

**Toda la base, en SQL** (solo lee, no modifica nada):
```bash
mkdir -p backups
docker compose exec -T mysql sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --no-create-info --default-character-set=utf8mb4 recetario' > backups/recetario_datos.sql
```
Restaurar en una base **vacía** (si ya hay datos, da error de clave duplicada):
```bash
docker compose exec -T mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" --default-character-set=utf8mb4 recetario' < backups/recetario_datos.sql
```
La carpeta `backups/` está en el `.gitignore`. Prueba tus respaldos restaurándolos en un clon antes de confiar en ellos.

## Persistencia

```bash
docker compose down        # detiene y elimina contenedores; los datos se conservan
docker compose down -v     # elimina también el volumen: BORRA la base de datos
```

El volumen se llama `<carpeta-del-proyecto>_mysql-data`. Si borras la carpeta y clonas otra con el mismo nombre, Docker reutiliza el volumen y los datos siguen ahí.

## Problemas comunes

**Puerto 80 ocupado.** Identifica el proceso:
- Linux: `ss -tulpn | grep :80`
- Windows: `netstat -ano | findstr :80`

Si es un servicio, páralo con `sudo systemctl stop <servicio>`. Si es un contenedor de otro proyecto, `docker ps` para verlo y `docker stop <nombre>`.

**Puerto 3306 ocupado.** Mismo procedimiento con `:3306` (por ejemplo, un MySQL local).

**`container name already in use`.** El compose fija los nombres `lemp-nginx`, `lemp-php` y `lemp-mysql`; no puedes tener dos copias del stack a la vez. Baja la otra copia con `docker compose down` desde su carpeta.

**`Access denied` al conectar.** Casi siempre es un volumen creado con otras credenciales. MySQL solo lee `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` y `MYSQL_DATABASE` cuando el volumen está vacío. Si cambiaste el `.env` después, tienes dos opciones:
- Recrear el volumen: `docker compose down -v` y luego `docker compose up -d` (borra los datos).
- Cambiar la contraseña dentro de MySQL con `ALTER USER`, sin borrar nada.

**Fatal error con usuario o base vacíos.** El servicio `php` no recibe las variables. Comprueba con:
```bash
docker compose exec php printenv | grep MYSQL
```
Deben aparecer `MYSQL_DATABASE`, `MYSQL_USER` y `MYSQL_PASSWORD`. Si no, revisa que el servicio `php` tenga `environment:` en el compose y recréalo con `docker compose up -d --force-recreate php`.

**El recetario muestra "Error de conexión".** Revisa en orden:
1. `http://localhost/recetario/api.php?entity=state` responde JSON.
2. `MYSQL_DATABASE=recetario` en el `.env`.
3. Las tablas existen: `docker compose exec mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "USE recetario; SHOW TABLES;"'`.
4. `docker compose logs php` muestra el motivo real.

**`Can't initialize batch_readline` en el log de MySQL.** Un archivo montado desde `mysql/` no existe en tu carpeta, y Docker crea una carpeta vacía con ese nombre. Comprueba con `ls -la mysql/`: `init_recetario.sql` y `seed_recetario.sql` deben empezar con `-` (archivo), no con `d` (carpeta). Si son carpetas, bórralas con `rmdir`, restaura los archivos (`git checkout -- mysql/`) y recrea el volumen con `docker compose down -v`. El script solo corre con el volumen vacío.

**"Copiar JSON" no copia.** El portapapeles del navegador solo funciona en HTTPS o en `localhost`. Desde otra dirección (por ejemplo `http://192.168.1.9`), la app descarga el archivo en su lugar.

**Archivos montados sin permisos (Linux).** Si PHP o nginx no pueden leer `php/src` o `web`, da permiso de lectura: `chmod -R o+rX php/src web`.

**Windows (Git Bash).**
- Antepón `MSYS_NO_PATHCONV=1` a los `docker exec` o `docker compose exec` con rutas del contenedor, para que Git Bash no las convierta en rutas de Windows.
- Si un comando interactivo falla con "the input device is not a TTY", antepón `winpty`.
- Guarda los archivos del proyecto en UTF-8 sin BOM. Si ves `Ã³` en lugar de `ó`, el archivo se está leyendo con otra codificación.

## Seguridad

> [!WARNING]
> - **La API no tiene autenticación.** Cualquiera con acceso a la red puede crear, editar y borrar recetas y precios. No expongas el puerto 80 a internet.
> - `demo_db.php` crea la tabla `productos` e inserta datos en cada visita. Con `MYSQL_DATABASE=recetario` la crea dentro de la base del recetario. No lo dejes accesible en un servidor público.
> - La app usa un usuario con permisos solo sobre su base. El contenedor `php` no recibe `MYSQL_ROOT_PASSWORD`, a propósito.
> - MySQL publica el puerto 3306 solo en `127.0.0.1`.
> - La API del recetario no envía cabeceras CORS: solo la página servida desde el mismo origen puede usarla.

## Arquitectura

- Petición: usuario, navegador, HTTP, Nginx y, solo para `.php`, FastCGI, PHP-FPM y MySQL.
- Respuesta: MySQL, PHP-FPM, FastCGI, Nginx, HTTP y usuario.
- Nginx sirve `web/` (estáticos) directamente, sin pasar por PHP. Solo las rutas `.php` se envían a PHP-FPM.
- Redes: `frontend` (nginx y php) y `backend` (php y mysql). Nginx no puede alcanzar MySQL; es el aislamiento previsto.

## Limitaciones conocidas

- Babel compila el código en el navegador en cada carga, lo que hace más lenta la primera carga en celular.
- La búsqueda por ingredientes compara palabras completas en texto libre: no reconoce sinónimos (palta/aguacate) ni distingue variantes.
- Los precios de ingredientes no se enlazan con la pestaña Costos.
- Los textos de la pestaña Costos y los nombres de ingredientes son texto libre, sin catálogo ni unidades normalizadas.
- Sin autenticación ni respaldo automático.
- Nginx pasa cualquier `.php` a PHP-FPM sin comprobar que exista; por eso un `.php` inexistente responde "File not found." en texto plano, no un 404 de nginx.

## Próximas mejoras

- [ ] Estado de la receta (borrador / probada) con insignia y filtro.
- [ ] Filtro por altitud y orden de la lista.
- [ ] Catálogo de alimentos: búsqueda por ingredientes con sinónimos y costeo automático.
- [ ] `try_files` en nginx y `php.ini` de producción.
- [ ] Fijar la versión exacta de la imagen de MySQL.
- [ ] Autenticación y HTTPS.
- [ ] Copias de seguridad automáticas.
- [ ] `docker-compose.override.yml` para desarrollo.

## Características técnicas

- Healthchecks en los tres servicios; `depends_on` con condición `service_healthy`.
- Redes `frontend` y `backend` para aislar la base de datos.
- Volumen con nombre (`mysql-data`) para los datos: sobreviven a `down`, no a `down -v`.
- API modular: un router y un archivo por entidad, con lista cerrada de rutas permitidas.
- Detalle de errores del servidor en el log del contenedor (`docker compose logs php`), no en la respuesta al navegador.

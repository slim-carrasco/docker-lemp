# Recetario - Stack LEMP con Docker Compose

Aplicación web para guardar recetas con medidas, escalar porciones, conocer el costo, registrar los errores de cada prueba y publicar el menú del día para los clientes. Varios locales pueden usarla a la vez, cada uno con sus propios datos y con usuarios de distintos roles (dueño, chef y ayudante). Corre sobre un stack LEMP (Linux, Nginx, MySQL, PHP-FPM) en contenedores, con HTTPS y redes separadas, y funciona sin conexión a internet una vez instalada.

## Funciones

- Recetas con ingredientes (cantidad, unidad y precio), pasos con temporizador, notas y errores registrados.
- Escalado de porciones: cantidades y costos se recalculan al cambiar las porciones.
- Costo total y costo por porción de cada receta, y precio de venta opcional.
- Modo cocina: un paso a la vez, con temporizador por paso.
- Ficha imprimible de cada receta, con las cantidades de las porciones elegidas, que también se puede guardar como PDF.
- Búsqueda por nombre y por ingredientes que tienes (`pollo, arroz` muestra las recetas que llevan ambos).
- Carpetas con color; selección múltiple para mover o eliminar varias recetas.
- Inicio de sesión con roles, creada por (quién escribió cada receta) y gestión de usuarios.
- Menú de hoy: página pública para clientes con los platos disponibles, y precios que el dueño puede mostrar u ocultar.
- Registro de locales nuevos, cada uno aislado de los demás.
- Pestaña Costos: precios de referencia por categoría.
- Importar JSON y Exportar todo (descarga un `.json`).

## Quién puede hacer qué

| Acción | Dueño | Chef | Ayudante | Cliente |
|---|---|---|---|---|
| Ver recetas, escalar, modo cocina e imprimir la ficha | Sí | Sí | Sí, sin precios | No |
| Ver precios, costos y precio de venta | Sí | Sí | No | No |
| Crear y editar recetas, carpetas y precios de referencia | Sí | Sí | No | No |
| Marcar «Disponible hoy» y mover recetas de carpeta | Sí | Sí | No | No |
| Eliminar recetas (una o varias) | Sí | No | No | No |
| Importar y exportar | Sí | No | No | No |
| Mostrar u ocultar los precios en el menú público | Sí | No | No | No |
| Crear, desactivar y cambiar el rol de usuarios | Sí | No | No | No |
| Ver el menú de hoy | Sí | Sí | Sí | Sí, sin iniciar sesión |

El servidor quita los precios de las respuestas al ayudante: no es solo un botón oculto.

## Tecnologías

| Componente | Probado con | Propósito |
|---|---|---|
| Docker | 29.2.1 | Contenerizar |
| Docker Compose | 5.0.2 | Orquestar |
| Nginx (alpine) | 1.29.4 | HTTPS, archivos estáticos y paso de `.php` a PHP-FPM |
| MySQL | 8.0 (8.0.45) | Almacenar los datos |
| PHP-FPM | 8.2 (8.2.34) | API, sesiones y permisos |
| React 18, Babel e íconos Tabler | locales en `web/vendor/` | Interfaz, sin paso de compilación |
| Alpine | 3.20 | Solo genera el certificado HTTPS en el primer arranque |

"Probado con" indica las versiones usadas durante el desarrollo, no un mínimo garantizado.

## Estructura del proyecto

| Ruta | Contenido |
|---|---|
| `docker-compose.yml` | Servicios, redes, volumen y chequeos de salud |
| `.env.example` | Plantilla de variables de entorno |
| `nginx/nginx.conf` | Configuración de Nginx (HTTPS y redirección del 80 al 443) |
| `nginx/certs/` | Certificado y clave; se generan solos y no van a Git |
| `mysql/init_recetario.sql` | Esquema (9 tablas) y local de ejemplo; se ejecuta una sola vez |
| `mysql/seed_recetario.sql` | Datos de ejemplo: carpetas, recetas y precios |
| `php/Dockerfile` | Imagen de PHP-FPM |
| `php/src/health.php` | Chequeo de salud |
| `php/src/recetario/` | API: `api.php` (router y permisos), `auth.php`, `lib.php`, `db.php` y `handlers/` |
| `web/index.html` | Portada |
| `web/recetario.html` | Interfaz del personal del local |
| `web/menu.html` | Menú de hoy para clientes |
| `web/vendor/` | React, Babel e íconos locales |
| `tests/` | Pruebas automáticas de la API |

## Requisitos

- Docker y Docker Compose.
- Internet **solo la primera vez**: Docker descarga las imágenes y el contenedor de certificados instala `openssl`. Después funciona sin conexión.
- Los puertos 80, 443 y 3306 libres en el equipo.

## Instalación

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/slim-carrasco/docker-lemp.git
   cd docker-lemp
   ```

2. Crear el `.env` desde el ejemplo y **editar los valores antes del primer arranque** (en Windows con PowerShell: `copy .env.example .env`):
   ```bash
   cp .env.example .env
   ```

3. Levantar el stack:
   ```bash
   docker compose up -d --build
   ```

4. Esperar unos 30 segundos hasta que `docker compose ps` muestre los tres servicios `healthy`, y abrir `https://localhost/`.

5. El navegador mostrará un aviso de certificado: ver la sección «Certificado HTTPS».

> [!WARNING]
> - **Cambia las contraseñas de `.env.example`.** Son valores de ejemplo conocidos: quien las deje tal cual tendrá usuarios con claves públicas.
> - `MYSQL_DATABASE` debe ser `recetario`. `MYSQL_USER` no puede ser `root`. Evita `$`, comillas y espacios al final en los valores.
> - Los valores de MySQL del `.env` solo se aplican al primer arranque, con el volumen vacío.
> - Los usuarios del `.env` se crean una sola vez, cuando la aplicación carga por primera vez. Cambiar el `.env` después no los modifica: se gestionan desde la pantalla Usuarios.
> - El archivo `.env` nunca debe subir a GitHub.
> - Usa `up -d`. Sin `-d`, el stack ocupa la terminal y Ctrl+C lo detiene.

## Variables del `.env`

| Variable | Descripción |
|---|---|
| `MYSQL_ROOT_PASSWORD` | Contraseña del usuario root de MySQL. El contenedor `php` no la recibe |
| `MYSQL_DATABASE` | Base de datos; debe ser `recetario` |
| `MYSQL_USER` | Usuario de la aplicación en MySQL (no `root`) |
| `MYSQL_PASSWORD` | Contraseña de ese usuario |
| `LOCAL_NOMBRE` | Nombre del local de ejemplo; de él sale el código de acceso |
| `ADMIN_USER`, `ADMIN_PASSWORD`, `ADMIN_NOMBRE` | Usuario, contraseña y nombre del dueño del local de ejemplo |
| `CHEF_USER`, `CHEF_PASSWORD` | Chef de ejemplo (opcional) |
| `AYUDANTE_USER`, `AYUDANTE_PASSWORD` | Ayudante de ejemplo (opcional) |
| `PERMITIR_REGISTRO` | `1` permite registrar locales nuevos; `0` lo desactiva |

Reglas: el usuario lleva de 3 a 30 caracteres entre minúsculas, números, punto, guion y guion bajo; la contraseña, de 8 a 72 caracteres.

## Iniciar sesión

La pantalla pide tres datos:

- **Código del local:** el nombre del local en minúsculas, sin tildes y con guiones. Con `LOCAL_NOMBRE=Local de ejemplo`, el código es `local-de-ejemplo`.
- **Usuario y contraseña** del `.env`.

El navegador recuerda el código del local. La sesión se cierra al cerrar el navegador y caduca tras unas 8 horas de inactividad. Tras 5 contraseñas incorrectas, la cuenta se bloquea 5 minutos.

## Verificación

```bash
docker compose ps
docker compose ps -a
```

Los tres servicios deben estar `healthy`. `lemp-certs` aparece solo con `ps -a`, como `Exited (0)`: generó el certificado y terminó, y es lo esperado.

| URL | Debe mostrar |
|---|---|
| `http://localhost/` | Redirige a `https://localhost/` |
| `https://localhost/` | La portada |
| `https://localhost/health.php` | `OK` (prueba Nginx, FastCGI, PHP-FPM y MySQL) |
| `https://localhost/recetario.html` | La pantalla de inicio de sesión |
| `https://localhost/menu.html?local=local-de-ejemplo` | El menú de hoy (vacío hasta que se marquen platos) |

Si `health.php` responde `FALLO`, el detalle está en `docker compose logs php`.

## Certificado HTTPS

El tráfico viaja cifrado con TLS. El certificado es **autofirmado**: se genera en el primer arranque, en `nginx/certs/`, y dura 365 días. Por eso el navegador cifra la conexión pero no puede verificar quién es el servidor, y la marca como «No seguro».

Para continuar la primera vez:

- **Chrome y Edge:** en «Tu conexión no es privada», pulsa **Configuración avanzada** (abajo a la izquierda) y luego **Continuar a localhost (no seguro)**.
- **Firefox:** **Avanzado...** y **Aceptar el riesgo y continuar**.

Para regenerar el certificado, baja el stack, borra la carpeta `nginx/certs/` y vuelve a levantarlo. La clave `server.key` es privada y no debe compartirse ni subirse a Git. En un despliegue real se usaría Let's Encrypt con un dominio propio.

## Datos de ejemplo

En el primer arranque, con el volumen vacío, MySQL ejecuta en orden alfabético los archivos de `/docker-entrypoint-initdb.d/`:

1. `init_recetario.sql`: crea las 9 tablas y el local de ejemplo.
2. `seed_recetario.sql`: carga 5 carpetas, 21 recetas con sus ingredientes, pasos y errores, y 22 precios de referencia. Seis recetas tienen precio de venta.

El menú de hoy empieza vacío y los usuarios salen del `.env`. Estos scripts no se repiten en una instalación existente. Si no quieres datos de ejemplo, borra la línea de `seed_recetario.sql` en `docker-compose.yml` antes del primer arranque. El primer arranque tarda más: si el recetario muestra un error de conexión, espera unos segundos y recarga.

## Menú de hoy para clientes

- El dueño o el chef marcan **Disponible hoy** en cada receta, o las agregan desde la pestaña **Menú de hoy**.
- Esa pestaña muestra el enlace público: `https://localhost/menu.html?local=codigo-del-local`. No requiere iniciar sesión.
- El cliente ve solo nombre, descripción y, si el dueño activó **Mostrar precios de venta** y la receta tiene precio, el precio. Nunca ve ingredientes, costos ni notas.
- «Hoy» se calcula con la hora de Perú (`America/Lima`).

## Registro de locales

Con `PERMITIR_REGISTRO=1`, la pantalla de inicio de sesión ofrece **Registrar mi local**: crea el local y la cuenta del dueño, que luego agrega a su equipo desde **Usuarios**. Cada local ve únicamente sus propios datos. En un servidor expuesto conviene `PERMITIR_REGISTRO=0`.

## Base de datos

| Tabla | Contenido |
|---|---|
| `locales` | Nombre, código de acceso e interruptor de precios públicos |
| `usuarios` | Usuario, nombre, rol, contraseña cifrada, estado y control de intentos |
| `carpetas` | Nombre, descripción y color, por local |
| `recetas` | Datos de la receta, precio de venta, carpeta y quién la creó, por local |
| `ingredientes` | Nombre, cantidad, unidad y precio, por receta |
| `pasos` | Título, descripción y temporizador (segundos), por receta |
| `errores` | Texto libre, por receta |
| `precios` | Precios de referencia por categoría, por local |
| `menu_dia` | Recetas disponibles en una fecha |

`usuarios`, `carpetas`, `recetas` y `precios` pertenecen a un local, y borrar el local los borra. `ingredientes`, `pasos`, `errores` y `menu_dia` se borran con su receta. Al borrar una carpeta, sus recetas quedan sin carpeta, y al borrar un usuario, sus recetas conservan el registro sin autor.

El `precio` de un ingrediente es el costo de la cantidad usada en esa receta, no el precio por unidad de compra. No está enlazado con la tabla `precios`.

## API

Base: `/recetario/api.php?entity=<nombre>[&id=<n>]`. Responde JSON. Los errores devuelven `{"error": "..."}` con estos códigos: `400` datos inválidos, `401` sin sesión, `403` sin permiso, `404` no existe o pertenece a otro local, `429` demasiados intentos.

| `entity` | Métodos | Quién |
|---|---|---|
| `login`, `logout` | POST | Público |
| `registro` | GET, POST | Público (el POST solo si el registro está permitido) |
| `menu_publico` | GET | Público |
| `me`, `clave` | GET, POST | Cualquier sesión |
| `state`, `receta` | GET | Cualquier sesión |
| `recetas` | POST, PUT | Dueño y chef |
| `recetas` | DELETE | Dueño |
| `carpetas` | POST, PUT, DELETE | Dueño y chef |
| `precios` | GET, POST, PUT, DELETE | Dueño y chef |
| `menu` | GET | Cualquier sesión |
| `menu` | POST, DELETE | Dueño y chef |
| `mover` | POST | Dueño y chef |
| `eliminar`, `import`, `export` | POST, POST, GET | Dueño |
| `usuarios` | GET, POST, PUT | Dueño |
| `ajustes` | PUT | Dueño |

Los permisos se validan en el servidor, en una sola tabla dentro de `api.php`. El local sale de la sesión, nunca de lo que envíe el navegador, y todo acceso por número comprueba que la fila sea del local de quien pregunta.

Formato de una receta para importar:

```json
{
  "nombre": "Carlota de durazno",
  "descripcion": "Postre frío sin horno.",
  "carpeta": "Postres fríos",
  "porciones_base": 6,
  "altitud": true,
  "notas": "Texto libre",
  "precio_venta": 6,
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

## Pruebas automáticas

Los scripts de `tests/` recorren la API con `curl`: sesiones, permisos de cada rol, aislamiento entre locales, menú público, usuarios, contraseñas, bloqueo por intentos y registro. Cada comprobación se imprime como `OK` o `FALLA`.

```bash
bash tests/pruebas_api.sh
bash tests/pruebas_api_2.sh
```

Requisitos: el stack levantado en `https://localhost`, un `.env` con `ADMIN_USER`, `CHEF_USER` y `AYUDANTE_USER`, `PERMITIR_REGISTRO=1` y los datos de ejemplo sin modificar. Dejan en la base locales y usuarios de prueba (los usuarios quedan desactivados), así que deben ejecutarse en una instalación de pruebas. `docker compose down -v` la limpia.

## Copias de seguridad

**Recetas de un local, en un archivo `.json`:** botón **Exportar todo** (solo el dueño). Incluye las recetas de su local con ingredientes, pasos y errores; no incluye la pestaña Costos ni los usuarios. Para restaurar, **Importar JSON** en una instalación vacía.

**Toda la base, con estructura y datos** (solo lee, no modifica nada):
```bash
mkdir -p backups
docker compose exec -T mysql sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --default-character-set=utf8mb4 recetario' > backups/recetario_completo.sql
```
Restaurar **reemplaza** las tablas actuales:
```bash
docker compose exec -T mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" --default-character-set=utf8mb4 recetario' < backups/recetario_completo.sql
```
La carpeta `backups/` está en el `.gitignore`. Prueba tus respaldos en un clon antes de confiar en ellos.

## Persistencia

```bash
docker compose down        # detiene y elimina contenedores; los datos se conservan
docker compose down -v     # elimina también el volumen: BORRA la base de datos
```

El volumen se llama `<carpeta-del-proyecto>_mysql-data`. Si borras la carpeta y clonas otra con el mismo nombre, Docker reutiliza el volumen y los datos siguen ahí. Las sesiones viven dentro del contenedor `php`: si lo recreas, todos deben iniciar sesión otra vez.

## Problemas comunes

**Puertos 80, 443 o 3306 ocupados.** Identifica el proceso con `ss -tulpn | grep :80` (Linux) o `netstat -ano | findstr :80` (Windows). Si es un servicio, páralo con `sudo systemctl stop <servicio>`; si es un contenedor de otro proyecto, `docker ps` y `docker stop <nombre>`.

**`container name already in use`.** El compose fija los nombres `lemp-nginx`, `lemp-php`, `lemp-mysql` y `lemp-certs`: no puedes tener dos copias del stack a la vez. Baja la otra con `docker compose down` desde su carpeta.

**«Local, usuario o contraseña incorrectos».** Revisa el código del local (por defecto `local-de-ejemplo`) y que uses las credenciales del `.env`. Si cambiaste el `.env` después del primer arranque, esos usuarios ya existían con los valores anteriores.

**«Demasiados intentos».** La cuenta se bloquea 5 minutos. El dueño también puede desbloquearla restableciendo la contraseña desde Usuarios.

**«El registro de locales está desactivado».** Falta `PERMITIR_REGISTRO=1` en el `.env`. Después, `docker compose up -d` recrea el contenedor `php`.

**Los usuarios del `.env` no se crearon.** Mira `docker compose logs php | tail -5`: si dice «usuarios iniciales no creados», alguna contraseña no tiene de 8 a 72 caracteres o el usuario tiene un formato inválido.

**`Access denied` al conectar con MySQL.** Casi siempre es un volumen creado con otras credenciales: MySQL lee sus variables solo cuando el volumen está vacío. Recrea el volumen (`down -v`, borra los datos) o cambia la contraseña dentro de MySQL con `ALTER USER`.

**Fatal error con usuario o base vacíos.** El servicio `php` no recibe las variables. Comprueba con `docker compose exec php printenv | grep MYSQL`.

**«Error de conexión» en el recetario.** Revisa en orden: que `https://localhost/recetario/api.php?entity=registro` responda JSON, que `MYSQL_DATABASE=recetario`, que existan las tablas (`docker compose exec mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "USE recetario; SHOW TABLES;"'`) y el motivo real en `docker compose logs php`.

**`Can't initialize batch_readline` en el log de MySQL.** Un archivo montado desde `mysql/` no existe y Docker creó una carpeta con su nombre. `ls -la mysql/` debe mostrar los dos `.sql` como archivos (empiezan con `-`). Si son carpetas, bórralas con `rmdir`, restaura los archivos con `git checkout -- mysql/` y recrea el volumen.

**Aviso «Tu conexión no es privada».** Es el certificado autofirmado: ver «Certificado HTTPS».

**«Copiar JSON» no copia.** El portapapeles del navegador exige HTTPS o `localhost`; en otro caso, la aplicación descarga el archivo.

**Windows (Git Bash).** Antepón `MSYS_NO_PATHCONV=1` a los comandos de Docker con rutas del contenedor. Si un comando interactivo falla con «the input device is not a TTY», antepón `winpty`. Guarda los archivos en UTF-8 sin BOM. Con `curl` en Git Bash, los textos con `ñ` o tildes pueden enviarse en otra codificación; el navegador no tiene ese problema.

## Seguridad

- El tráfico va cifrado con TLS (certificado autofirmado).
- Las contraseñas se guardan con `password_hash`, nunca en texto. La cuenta se bloquea 5 minutos tras 5 intentos fallidos.
- La cookie de sesión es `HttpOnly` y `SameSite=Lax`, y `Secure` bajo HTTPS. `Lax` impide que otros sitios envíen peticiones de escritura con tu sesión; no hay un token CSRF adicional.
- Los permisos por rol se validan en el servidor. El ayudante no recibe precios.
- Cada local está aislado: acceder a datos ajenos responde `404`, sin revelar que existen.
- Consultas preparadas con PDO, validación en el servidor y lista cerrada de entidades.
- Los detalles de los errores van al log del contenedor, no a la respuesta.
- La aplicación usa un usuario de MySQL con permisos solo sobre su base. El contenedor `php` no recibe `MYSQL_ROOT_PASSWORD`, y MySQL publica el 3306 solo en `127.0.0.1`.
- La API no envía cabeceras CORS.
- `health.php` es público y solo responde `OK` o `FALLO`.

## Arquitectura

- **Capas:** la interfaz (React, en el navegador) calcula el escalado, el costo y la búsqueda; la API (PHP) valida, aplica permisos y guarda; MySQL almacena. La interfaz y la API se comunican con JSON sobre HTTPS.
- **Petición:** usuario, navegador, HTTPS, Nginx y, solo para `.php`, FastCGI, PHP-FPM y MySQL. La respuesta hace el camino inverso.
- Nginx sirve `web/` directamente, sin pasar por PHP. El puerto 80 redirige al 443.
- **Redes:** `frontend` (Nginx y PHP) y `backend` (PHP y MySQL). Nginx no alcanza MySQL.
- **Arranque en cadena:** `certs` genera el certificado, MySQL debe estar sano antes de PHP, y PHP antes de Nginx.

## Limitaciones conocidas

- El certificado es autofirmado: el navegador muestra un aviso.
- No hay recuperación de contraseña por correo: la restablece el dueño.
- Si dos personas editan la misma receta a la vez, gana quien guarda último.
- Las sesiones se pierden al recrear el contenedor `php`.
- Babel compila en el navegador en cada carga, lo que hace más lenta la primera carga en celular.
- Los ingredientes son texto libre: la búsqueda no reconoce sinónimos y los precios de ingredientes no se enlazan con la pestaña Costos.
- No hay copias de seguridad automáticas ni paginación de la lista de recetas.
- Con `PERMITIR_REGISTRO=1`, quien llegue a la pantalla puede crear locales.

## Próximas mejoras

- [ ] Certificado de Let's Encrypt con dominio propio.
- [ ] Recuperación de contraseña por correo y token CSR
- [ ] Aplicación instalable (PWA) para el celular.
- [ ] Copias de seguridad automáticas y paginación.

## Características técnicas

- Chequeos de salud en los tres servicios y `depends_on` con condición.
- Redes `frontend` y `backend` para aislar la base de datos.
- Volumen con nombre (`mysql-data`): los datos sobreviven a `down`, no a `down -v`.
- API modular con un controlador por grupo de entidades y una única tabla de permisos.
- Esquema y datos de ejemplo cargados por scripts en el primer arranque.
- Pruebas automáticas de la API en `tests/`.

#!/bin/bash
cd "$(dirname "$0")/.." || exit 1
VAL() { grep "^$1=" .env | head -1 | cut -d= -f2- | tr -d '\r'; }
API="https://localhost/recetario/api.php"
J='Content-Type: application/json'
SLUG="local-de-ejemplo"
DU=$(VAL ADMIN_USER);    DP=$(VAL ADMIN_PASSWORD)
CU=$(VAL CHEF_USER);     CP=$(VAL CHEF_PASSWORD)
AU=$(VAL AYUDANTE_USER); AP=$(VAL AYUDANTE_PASSWORD)
if [ -z "$DU" ] || [ -z "$CU" ] || [ -z "$AU" ]; then
  echo "Este script necesita ADMIN_USER, CHEF_USER y AYUDANTE_USER en el .env"; exit 1
fi
T=$(mktemp -d)
N=$RANDOM
ok=0; mal=0
chk()    { if [ "$2" = "$3" ]; then echo "OK     $1"; ok=$((ok+1)); else echo "FALLA  $1 (esperado $3, obtenido $2)"; mal=$((mal+1)); fi; }
cod()    { curl -sk -o /dev/null -w "%{http_code}" "$@"; }
cuenta() { grep -o "$1" | wc -l | tr -d ' '; }
idde()   { grep -o '"id":[0-9]*' | head -1 | cut -d: -f2; }
login()  { cod -c "$T/$1" -H "$J" -d "{\"local\":\"$SLUG\",\"usuario\":\"$2\",\"clave\":\"$3\"}" "$API?entity=login"; }

echo "== Sesiones =="
chk "Login del dueño" "$(login d "$DU" "$DP")" "200"
chk "Login del chef" "$(login c "$CU" "$CP")" "200"
chk "Login del ayudante" "$(login a "$AU" "$AP")" "200"

echo "== Ajustes =="
chk "El dueño activa los precios públicos" "$(cod -b $T/d -X PUT -H "$J" -d '{"mostrar_precios":true}' "$API?entity=ajustes")" "200"
chk "Un cuerpo vacío responde 400" "$(cod -b $T/d -X PUT -H "$J" -d '{}' "$API?entity=ajustes")" "400"
chk "El chef no cambia los ajustes (403)" "$(cod -b $T/c -X PUT -H "$J" -d '{"mostrar_precios":false}' "$API?entity=ajustes")" "403"
chk "El ayudante no cambia los ajustes (403)" "$(cod -b $T/a -X PUT -H "$J" -d '{"mostrar_precios":false}' "$API?entity=ajustes")" "403"

echo "== Receta con precio de venta =="
R1=$(curl -sk -b $T/c -H "$J" -d '{"nombre":"Prueba precio","precio_venta":12.5,"ingredientes":[{"nombre":"Sal","cantidad":1,"unidad":"g","precio":1}]}' "$API?entity=recetas")
RID=$(echo "$R1" | idde)
chk "El chef crea una receta con precio de venta" "$([ -n "$RID" ] && echo si || echo no)" "si"
chk "Un precio de venta negativo responde 400" "$(cod -b $T/c -H "$J" -d '{"nombre":"x","precio_venta":-5}' "$API?entity=recetas")" "400"
chk "Una receta sin nombre responde 400" "$(cod -b $T/c -H "$J" -d '{"nombre":""}' "$API?entity=recetas")" "400"
chk "El dueño ve el precio de venta" "$(curl -sk -b $T/d "$API?entity=receta&id=$RID" | cuenta '"precio_venta":12.5')" "1"
chk "El ayudante no ve el precio de venta" "$(curl -sk -b $T/a "$API?entity=receta&id=$RID" | cuenta '"precio_venta":12.5')" "0"
chk "Se registra quién creó la receta" "$(curl -sk -b $T/d "$API?entity=receta&id=$RID" | cuenta '"creado_por_nombre":"[^"]')" "1"
chk "Editar sin enviar el precio de venta funciona" "$(cod -b $T/c -X PUT -H "$J" -d '{"nombre":"Prueba precio"}' "$API?entity=recetas&id=$RID")" "200"
chk "El precio de venta se conserva después de editar" "$(curl -sk -b $T/d "$API?entity=receta&id=$RID" | cuenta '"precio_venta":12.5')" "1"

echo "== Menú de hoy y precios públicos =="
chk "El chef agrega la receta al menú de hoy" "$(cod -b $T/c -H "$J" -d "{\"receta_id\":$RID}" "$API?entity=menu")" "200"
curl -sk -b $T/d -o /dev/null -X PUT -H "$J" -d '{"mostrar_precios":false}' "$API?entity=ajustes"
M=$(curl -sk "$API?entity=menu_publico&local=$SLUG")
chk "Con el interruptor apagado el cliente no ve precios" "$(echo "$M" | cuenta '"precio_venta":12.5')" "0"
chk "Con el interruptor apagado el cliente sí ve el plato" "$(echo "$M" | cuenta 'Prueba precio')" "1"
curl -sk -b $T/d -o /dev/null -X PUT -H "$J" -d '{"mostrar_precios":true}' "$API?entity=ajustes"
M=$(curl -sk "$API?entity=menu_publico&local=$SLUG")
chk "Con el interruptor activado el cliente ve el precio" "$(echo "$M" | cuenta '"precio_venta":12.5')" "1"
chk "El menú público no muestra ingredientes" "$(echo "$M" | cuenta '"ingredientes"')" "0"
chk "El chef ve el precio en el menú interno" "$(curl -sk -b $T/c "$API?entity=menu" | cuenta '"precio_venta":12.5')" "1"
chk "El ayudante no ve el precio en el menú interno" "$(curl -sk -b $T/a "$API?entity=menu" | cuenta '"precio_venta":12.5')" "0"
chk "El ayudante no puede agregar al menú (403)" "$(cod -b $T/a -H "$J" -d "{\"receta_id\":$RID}" "$API?entity=menu")" "403"
chk "Un local inexistente responde 404" "$(cod "$API?entity=menu_publico&local=no-existe-$N")" "404"
curl -sk -b $T/d -o /dev/null -X PUT -H "$J" -d '{"mostrar_precios":false}' "$API?entity=ajustes"
chk "El chef quita la receta del menú" "$(cod -b $T/c -X DELETE "$API?entity=menu&id=$RID")" "200"

echo "== Carpetas y mover =="
CID=$(curl -sk -b $T/c -H "$J" -d "{\"nombre\":\"Carpeta prueba $N\",\"color\":\"#2ecc71\"}" "$API?entity=carpetas" | idde)
chk "El chef crea una carpeta" "$([ -n "$CID" ] && echo si || echo no)" "si"
chk "El chef mueve la receta a la carpeta" "$(cod -b $T/c -H "$J" -d "{\"ids\":[$RID],\"carpeta_id\":$CID}" "$API?entity=mover")" "200"
chk "La lista muestra la receta dentro de la carpeta" "$(curl -sk -b $T/d "$API?entity=state" | cuenta "\"carpeta_nombre\":\"Carpeta prueba $N\"")" "1"
chk "El ayudante no puede mover recetas (403)" "$(cod -b $T/a -H "$J" -d "{\"ids\":[$RID],\"carpeta_id\":$CID}" "$API?entity=mover")" "403"
chk "Mover sin ids responde 400" "$(cod -b $T/c -H "$J" -d '{"ids":[]}' "$API?entity=mover")" "400"
chk "Mover a una carpeta inexistente responde 400" "$(cod -b $T/c -H "$J" -d "{\"ids\":[$RID],\"carpeta_id\":999999}" "$API?entity=mover")" "400"
chk "Mover una receta inexistente responde 404" "$(cod -b $T/c -H "$J" -d '{"ids":[999999],"carpeta_id":null}' "$API?entity=mover")" "404"

echo "== Aislamiento ampliado (segundo local) =="
IDD=$(curl -sk -b $T/d "$API?entity=me" | idde)
RO=$(curl -sk -c $T/o -w "\n%{http_code}" -H "$J" -d "{\"local_nombre\":\"Local B $N\",\"nombre\":\"Duena B\",\"usuario\":\"duenob\",\"clave\":\"clave-de-prueba-1\"}" "$API?entity=registro")
chk "Se registra un segundo local" "$(echo "$RO" | tail -1)" "200"
chk "El segundo local tiene sesión activa" "$(cod -b $T/o "$API?entity=me")" "200"
chk "No ve las carpetas de otro local" "$(curl -sk -b $T/o "$API?entity=state" | cuenta '"color"')" "0"
chk "No puede editar una carpeta ajena (404)" "$(cod -b $T/o -X PUT -H "$J" -d '{"nombre":"hack"}' "$API?entity=carpetas&id=$CID")" "404"
chk "No puede borrar una carpeta ajena (404)" "$(cod -b $T/o -X DELETE "$API?entity=carpetas&id=$CID")" "404"
chk "No ve los precios de referencia de otro local" "$(curl -sk -b $T/o "$API?entity=precios" | cuenta '"nombre"')" "0"
chk "No puede editar un precio ajeno (404)" "$(cod -b $T/o -X PUT -H "$J" -d '{"nombre":"x","precio":1,"unidad":"kg"}' "$API?entity=precios&id=1")" "404"
chk "No puede borrar un precio ajeno (404)" "$(cod -b $T/o -X DELETE "$API?entity=precios&id=1")" "404"
RB=$(curl -sk -b $T/o -H "$J" -d '{"nombre":"Receta de B"}' "$API?entity=recetas" | idde)
chk "No puede mover su receta a una carpeta ajena (400)" "$(cod -b $T/o -H "$J" -d "{\"ids\":[$RB],\"carpeta_id\":$CID}" "$API?entity=mover")" "400"
chk "No puede crear una receta en una carpeta ajena (400)" "$(cod -b $T/o -H "$J" -d "{\"nombre\":\"x\",\"carpeta_id\":$CID}" "$API?entity=recetas")" "400"
chk "No puede cambiar usuarios de otro local (404)" "$(cod -b $T/o -X PUT -H "$J" -d '{"activo":false}' "$API?entity=usuarios&id=$IDD")" "404"
chk "No puede eliminar por lote recetas ajenas (404)" "$(cod -b $T/o -H "$J" -d "{\"ids\":[$RID]}" "$API?entity=eliminar")" "404"

echo "== Eliminar por lote =="
IDS=""
for i in 1 2 3 4 5 6; do
  X=$(curl -sk -b $T/d -H "$J" -d "{\"nombre\":\"Lote $N-$i\"}" "$API?entity=recetas" | idde)
  IDS="$IDS${IDS:+,}$X"
done
chk "Se crearon 6 recetas de prueba" "$(echo "$IDS" | tr ',' '\n' | grep -c '^[0-9][0-9]*$')" "6"
chk "El chef no puede eliminar por lote (403)" "$(cod -b $T/c -H "$J" -d "{\"ids\":[$IDS]}" "$API?entity=eliminar")" "403"
chk "Eliminar más de 5 sin confirmar responde 400" "$(cod -b $T/d -H "$J" -d "{\"ids\":[$IDS]}" "$API?entity=eliminar")" "400"
chk "Eliminar más de 5 escribiendo ELIMINAR responde 200" "$(cod -b $T/d -H "$J" -d "{\"ids\":[$IDS],\"confirmar\":\"ELIMINAR\"}" "$API?entity=eliminar")" "200"
chk "Las recetas eliminadas ya no existen (404)" "$(cod -b $T/d "$API?entity=receta&id=${IDS%%,*}")" "404"
X=$(curl -sk -b $T/d -H "$J" -d "{\"nombre\":\"Una sola $N\"}" "$API?entity=recetas" | idde)
chk "Eliminar una sola receta no pide confirmación" "$(cod -b $T/d -H "$J" -d "{\"ids\":[$X]}" "$API?entity=eliminar")" "200"
chk "El chef puede eliminar una carpeta" "$(cod -b $T/c -X DELETE "$API?entity=carpetas&id=$CID")" "200"

echo "== Usuarios =="
chk "El dueño lista los usuarios (al menos 3)" "$([ "$(curl -sk -b $T/d "$API?entity=usuarios" | cuenta '"usuario"')" -ge 3 ] && echo si || echo no)" "si"
chk "El ayudante no lista usuarios (403)" "$(cod -b $T/a "$API?entity=usuarios")" "403"
NU="prueba$N"
NID=$(curl -sk -b $T/d -H "$J" -d "{\"usuario\":\"$NU\",\"nombre\":\"Persona Prueba\",\"rol\":\"ayudante\",\"clave\":\"clave-segura-1\"}" "$API?entity=usuarios" | idde)
chk "El dueño crea un usuario" "$([ -n "$NID" ] && echo si || echo no)" "si"
chk "Un usuario repetido responde 409" "$(cod -b $T/d -H "$J" -d "{\"usuario\":\"$NU\",\"nombre\":\"x\",\"rol\":\"chef\",\"clave\":\"clave-segura-1\"}" "$API?entity=usuarios")" "409"
chk "Un rol inválido responde 400" "$(cod -b $T/d -H "$J" -d "{\"usuario\":\"otro$N\",\"nombre\":\"x\",\"rol\":\"admin\",\"clave\":\"clave-segura-1\"}" "$API?entity=usuarios")" "400"
chk "Un usuario con mayúsculas o espacios responde 400" "$(cod -b $T/d -H "$J" -d '{"usuario":"Mal Usuario","nombre":"x","rol":"chef","clave":"clave-segura-1"}' "$API?entity=usuarios")" "400"
chk "Una contraseña de menos de 8 caracteres responde 400" "$(cod -b $T/d -H "$J" -d "{\"usuario\":\"corta$N\",\"nombre\":\"x\",\"rol\":\"chef\",\"clave\":\"1234567\"}" "$API?entity=usuarios")" "400"
chk "El usuario nuevo puede entrar" "$(login n "$NU" "clave-segura-1")" "200"
chk "El usuario nuevo no ve la lista de usuarios (403)" "$(cod -b $T/n "$API?entity=usuarios")" "403"
chk "El usuario nuevo, ayudante, no ve Costos (403)" "$(cod -b $T/n "$API?entity=precios")" "403"
chk "El dueño le cambia el rol a chef" "$(cod -b $T/d -X PUT -H "$J" -d '{"rol":"chef"}' "$API?entity=usuarios&id=$NID")" "200"
chk "El cambio de rol rige en la misma sesión (ve Costos)" "$(cod -b $T/n "$API?entity=precios")" "200"
chk "El dueño desactiva al usuario" "$(cod -b $T/d -X PUT -H "$J" -d '{"activo":false}' "$API?entity=usuarios&id=$NID")" "200"
chk "Su sesión abierta deja de valer (401)" "$(cod -b $T/n "$API?entity=me")" "401"
chk "Un usuario desactivado no puede entrar (403)" "$(login n2 "$NU" "clave-segura-1")" "403"
chk "El dueño lo reactiva" "$(cod -b $T/d -X PUT -H "$J" -d '{"activo":true}' "$API?entity=usuarios&id=$NID")" "200"
chk "Reactivado, vuelve a entrar" "$(login n "$NU" "clave-segura-1")" "200"
chk "El último dueño no puede dejar de serlo (400)" "$(cod -b $T/d -X PUT -H "$J" -d '{"rol":"chef"}' "$API?entity=usuarios&id=$IDD")" "400"
chk "El último dueño no puede desactivarse (400)" "$(cod -b $T/d -X PUT -H "$J" -d '{"activo":false}' "$API?entity=usuarios&id=$IDD")" "400"

echo "== Cambio de contraseña =="
chk "Con la contraseña actual incorrecta responde 400" "$(cod -b $T/n -H "$J" -d '{"actual":"incorrecta-99","nueva":"clave-cambiada-3"}' "$API?entity=clave")" "400"
chk "Con una contraseña nueva corta responde 400" "$(cod -b $T/n -H "$J" -d '{"actual":"clave-segura-1","nueva":"corta"}' "$API?entity=clave")" "400"
chk "Con los datos correctos cambia la contraseña" "$(cod -b $T/n -c $T/n -H "$J" -d '{"actual":"clave-segura-1","nueva":"clave-cambiada-3"}' "$API?entity=clave")" "200"
chk "La contraseña vieja ya no entra (401)" "$(login x "$NU" "clave-segura-1")" "401"
chk "La nueva sí entra" "$(login n "$NU" "clave-cambiada-3")" "200"
chk "El dueño restablece la contraseña de otro usuario" "$(cod -b $T/d -X PUT -H "$J" -d '{"clave":"clave-nueva-44"}' "$API?entity=usuarios&id=$NID")" "200"
chk "La anterior ya no sirve (401)" "$(login x "$NU" "clave-cambiada-3")" "401"
chk "La restablecida sí sirve" "$(login n "$NU" "clave-nueva-44")" "200"

echo "== Bloqueo por intentos fallidos =="
BU="bloqueo$N"
RBL=$(curl -sk -b $T/d -w "\n%{http_code}" -H "$J" -d "{\"usuario\":\"$BU\",\"nombre\":\"Prueba bloqueo\",\"rol\":\"ayudante\",\"clave\":\"clave-segura-1\"}" "$API?entity=usuarios")
BID=$(echo "$RBL" | head -1 | idde)
chk "Se crea un usuario para la prueba de bloqueo" "$(echo "$RBL" | tail -1)" "200"
for i in 1 2 3 4 5; do login b "$BU" "mal-$i" > /dev/null; done
chk "Tras 5 fallos, incluso la contraseña correcta responde 429" "$(login b "$BU" "clave-segura-1")" "429"
chk "El dueño desbloquea restableciendo la contraseña" "$(cod -b $T/d -X PUT -H "$J" -d '{"clave":"clave-segura-2"}' "$API?entity=usuarios&id=$BID")" "200"
chk "Después de desbloquear, entra" "$(login b "$BU" "clave-segura-2")" "200"

echo "== Registro de locales =="
chk "El registro está permitido" "$(curl -sk "$API?entity=registro" | cuenta '"permitido":true')" "1"
chk "Un nombre de local corto responde 400" "$(cod -H "$J" -d '{"local_nombre":"ab","nombre":"Duena","usuario":"duena1","clave":"clave-de-prueba-1"}' "$API?entity=registro")" "400"
chk "Un usuario inválido responde 400" "$(cod -H "$J" -d '{"local_nombre":"Local valido","nombre":"Duena","usuario":"A B","clave":"clave-de-prueba-1"}' "$API?entity=registro")" "400"
chk "Una contraseña corta responde 400" "$(cod -H "$J" -d '{"local_nombre":"Local valido","nombre":"Duena","usuario":"duena1","clave":"123"}' "$API?entity=registro")" "400"
G1=$(curl -sk -H "$J" -d "{\"local_nombre\":\"Local Gemelo $N\",\"nombre\":\"Duena\",\"usuario\":\"gemelo\",\"clave\":\"clave-de-prueba-1\"}" "$API?entity=registro" | grep -o '"slug":"[^"]*"')
G2=$(curl -sk -H "$J" -d "{\"local_nombre\":\"Local Gemelo $N\",\"nombre\":\"Duena\",\"usuario\":\"gemelo\",\"clave\":\"clave-de-prueba-1\"}" "$API?entity=registro" | grep -o '"slug":"[^"]*"')
chk "Dos locales con el mismo nombre reciben códigos distintos" "$([ -n "$G1" ] && [ -n "$G2" ] && [ "$G1" != "$G2" ] && echo si || echo no)" "si"

echo "== Limpieza =="
chk "Se elimina la receta de prueba" "$(cod -b $T/d -X DELETE "$API?entity=recetas&id=$RID")" "200"
curl -sk -b $T/d -o /dev/null -X PUT -H "$J" -d '{"mostrar_precios":false}' "$API?entity=ajustes"
curl -sk -b $T/d -o /dev/null -X PUT -H "$J" -d '{"activo":false}' "$API?entity=usuarios&id=$NID"
curl -sk -b $T/d -o /dev/null -X PUT -H "$J" -d '{"activo":false}' "$API?entity=usuarios&id=$BID"
rm -rf "$T"
echo
echo "Resultado: $ok pruebas correctas, $mal con fallas"

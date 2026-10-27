#!/bin/bash
cd "$(dirname "$0")/.." || exit 1
VAL() { grep "^$1=" .env | head -1 | cut -d= -f2- | tr -d '\r'; }
API="https://localhost/recetario/api.php"
J='Content-Type: application/json'
SLUG="local-de-ejemplo"
DU=$(VAL ADMIN_USER);    DP=$(VAL ADMIN_PASSWORD)
CU=$(VAL CHEF_USER);     CP=$(VAL CHEF_PASSWORD)
AU=$(VAL AYUDANTE_USER); AP=$(VAL AYUDANTE_PASSWORD)
[ -z "$DU" ] && echo "AVISO: falta ADMIN_USER en .env"
[ ${#DP} -ge 8 ] && [ ${#DP} -le 72 ] || echo "AVISO: ADMIN_PASSWORD debe tener de 8 a 72 caracteres"
T=$(mktemp -d)
ok=0; mal=0
chk() { if [ "$2" = "$3" ]; then echo "OK     $1"; ok=$((ok+1)); else echo "FALLA  $1 (esperado $3, obtenido $2)"; mal=$((mal+1)); fi; }
cod() { curl -sk -o /dev/null -w "%{http_code}" "$@"; }
cuenta() { grep -o "$1" | wc -l | tr -d ' '; }
login() { cod -c "$T/$1" -H "$J" -d "{\"local\":\"$SLUG\",\"usuario\":\"$2\",\"clave\":\"$3\"}" "$API?entity=login"; }

echo "== Sesión =="
chk "Sin sesión, state responde 401" "$(cod "$API?entity=state")" "401"
chk "Login del dueño" "$(login d "$DU" "$DP")" "200"
chk "Login con clave incorrecta responde 401" "$(login x "$DU" "clave-incorrecta-xyz")" "401"

echo "== Dueño =="
chk "El dueño ve las 21 recetas de ejemplo" "$(curl -sk -b $T/d "$API?entity=state" | cuenta '"carpeta_nombre"')" "21"
RID=$(curl -sk -b $T/d -H "$J" -d '{"nombre":"Receta de prueba","ingredientes":[{"nombre":"Sal","cantidad":1,"unidad":"g","precio":1}]}' "$API?entity=recetas" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
if [ -z "$RID" ]; then echo "No se pudo crear la receta de prueba: se detienen las pruebas"; rm -rf "$T"; exit 1; fi
echo "OK     El dueño crea una receta de prueba (id $RID)"; ok=$((ok+1))

echo "== Ayudante =="
if [ -n "$AU" ]; then
  chk "Login del ayudante" "$(login a "$AU" "$AP")" "200"
  chk "El dueño ve el precio del ingrediente" "$(curl -sk -b $T/d "$API?entity=receta&id=$RID" | cuenta '"precio":"[0-9.]*"')" "1"
  chk "El ayudante no ve precios" "$(curl -sk -b $T/a "$API?entity=receta&id=$RID" | cuenta '"precio":"[0-9.]*"')" "0"
  chk "El ayudante no puede borrar (403)" "$(cod -b $T/a -X DELETE "$API?entity=recetas&id=$RID")" "403"
  chk "El ayudante no puede crear (403)" "$(cod -b $T/a -H "$J" -d '{"nombre":"x"}' "$API?entity=recetas")" "403"
  chk "El ayudante no accede a Costos (403)" "$(cod -b $T/a "$API?entity=precios")" "403"
else
  echo "(sin AYUDANTE_USER en .env: se omiten)"
fi

echo "== Chef =="
if [ -n "$CU" ]; then
  chk "Login del chef" "$(login c "$CU" "$CP")" "200"
  chk "El chef ve Costos (200)" "$(cod -b $T/c "$API?entity=precios")" "200"
  chk "El chef no puede borrar recetas (403)" "$(cod -b $T/c -X DELETE "$API?entity=recetas&id=$RID")" "403"
  chk "El chef no puede exportar (403)" "$(cod -b $T/c "$API?entity=export")" "403"
  chk "El chef no gestiona usuarios (403)" "$(cod -b $T/c "$API?entity=usuarios")" "403"
else
  echo "(sin CHEF_USER en .env: se omiten)"
fi

echo "== Aislamiento entre locales =="
N="Otro restaurante $RANDOM"
chk "Se registra un segundo local" "$(cod -c $T/o -H "$J" -d "{\"local_nombre\":\"$N\",\"nombre\":\"Duena Otro\",\"usuario\":\"otro\",\"clave\":\"clave-de-prueba-1\"}" "$API?entity=registro")" "200"
chk "El segundo local tiene sesión activa" "$(cod -b $T/o "$API?entity=me")" "200"
chk "El segundo local no ve recetas ajenas" "$(curl -sk -b $T/o "$API?entity=state" | cuenta '"carpeta_nombre"')" "0"
chk "No puede abrir una receta ajena (404)" "$(cod -b $T/o "$API?entity=receta&id=$RID")" "404"
chk "No puede editar una receta ajena (404)" "$(cod -b $T/o -X PUT -H "$J" -d '{"nombre":"hack"}' "$API?entity=recetas&id=$RID")" "404"
chk "No puede borrar una receta ajena (404)" "$(cod -b $T/o -X DELETE "$API?entity=recetas&id=$RID")" "404"
chk "No puede mover una receta ajena (404)" "$(cod -b $T/o -H "$J" -d "{\"ids\":[$RID]}" "$API?entity=mover")" "404"
chk "No puede eliminar por lote una receta ajena (404)" "$(cod -b $T/o -H "$J" -d "{\"ids\":[$RID]}" "$API?entity=eliminar")" "404"
chk "No puede ponerla en su menú (404)" "$(cod -b $T/o -H "$J" -d "{\"receta_id\":$RID}" "$API?entity=menu")" "404"
chk "La receta sigue existiendo para su dueño" "$(cod -b $T/d "$API?entity=receta&id=$RID")" "200"

echo "== Menú público =="
chk "El dueño agrega la receta al menú de hoy" "$(cod -b $T/d -H "$J" -d "{\"receta_id\":$RID}" "$API?entity=menu")" "200"
M=$(curl -sk "$API?entity=menu_publico&local=$SLUG")
chk "El cliente ve el plato sin iniciar sesión" "$(echo "$M" | cuenta 'Receta de prueba')" "1"
chk "El menú público no muestra ingredientes ni costos" "$(echo "$M" | cuenta '"ingredientes"\|"precio"\|"notas"')" "0"
chk "El dueño quita la receta del menú" "$(cod -b $T/d -X DELETE "$API?entity=menu&id=$RID")" "200"

echo "== Limpieza =="
chk "El dueño elimina la receta de prueba" "$(cod -b $T/d -X DELETE "$API?entity=recetas&id=$RID")" "200"
rm -rf "$T"
echo
echo "Resultado: $ok pruebas correctas, $mal con fallas"

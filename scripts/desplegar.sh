#!/usr/bin/env bash
#
# Despliegue manual a producción (https://erp.juancker.com).
# Referencia y contexto: DESPLIEGUE.md
#
# Empaqueta lo que cambió desde el último despliegue, respalda base de datos y
# código, sube los archivos, migra y regenera las cachés.
#
#   ./scripts/desplegar.sh                 # despliega HEAD, pidiendo confirmación
#   ./scripts/desplegar.sh -n              # sólo muestra qué haría
#   ./scripts/desplegar.sh --desde 362f6d4 # fuerza la base de comparación
#   ./scripts/desplegar.sh --sin-migrar    # no toca la base de datos
#   ./scripts/desplegar.sh -y              # sin confirmación (para CI)

set -euo pipefail

HOST=${DEPLOY_HOST:-smileintelli}
RUTA=${DEPLOY_PATH:-/var/www/erp}
RESPALDOS=${DEPLOY_BACKUPS:-/var/backups/erp}
URL=${DEPLOY_URL:-https://erp.juancker.com}
MARCA="$RUTA/.deployed-commit"

DRY=0; SI=0; MIGRAR=1; BASE=""
while [ $# -gt 0 ]; do
  case "$1" in
    -n|--dry-run)   DRY=1 ;;
    -y|--yes)       SI=1 ;;
    --sin-migrar)   MIGRAR=0 ;;
    --desde)        BASE="${2:?--desde necesita un commit}"; shift ;;
    -h|--help)      sed -n '3,13p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *)              echo "Opción desconocida: $1" >&2; exit 2 ;;
  esac
  shift
done

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
paso()  { printf '\n\033[1m▶ %s\033[0m\n' "$*"; }

remoto() { ssh -o ConnectTimeout=20 "$HOST" "$@"; }

cd "$(git rev-parse --show-toplevel)"

# ---------------------------------------------------------------- validaciones
paso "Revisando estado local"
COMMIT=$(git rev-parse HEAD)
if [ -n "$(git status --porcelain)" ]; then
  rojo "El árbol de trabajo tiene cambios sin commitear."
  rojo "Se despliega HEAD ($(git rev-parse --short HEAD)), así que esos cambios NO van a subir."
  git status --short | sed 's/^/    /'
  [ "$SI" -eq 1 ] || { printf '¿Continuar de todos modos? [s/N] '; read -r r; [ "$r" = "s" ] || exit 1; }
fi

paso "Consultando producción ($HOST:$RUTA)"
remoto "test -d '$RUTA'" || { rojo "No existe $RUTA en $HOST"; exit 1; }
remoto 'sudo -n true' 2>/dev/null || { rojo "El usuario remoto no tiene sudo sin contraseña"; exit 1; }

if [ -z "$BASE" ]; then
  BASE=$(remoto "sudo cat '$MARCA' 2>/dev/null" || true)
  [ -n "$BASE" ] || BASE=$(remoto "git -c safe.directory='$RUTA' -C '$RUTA' rev-parse HEAD 2>/dev/null" || true)
fi
[ -n "$BASE" ] || { rojo "No pude determinar el último commit desplegado. Usa --desde <commit>."; exit 1; }
git cat-file -e "$BASE^{commit}" 2>/dev/null || { rojo "El commit base '$BASE' no existe localmente."; exit 1; }

if [ "$BASE" = "$COMMIT" ]; then
  verde "Producción ya está en $(git rev-parse --short "$COMMIT"). No hay nada que desplegar."
  exit 0
fi

# ------------------------------------------------------------------ el paquete
TRABAJO=$(mktemp -d); trap 'rm -rf "$TRABAJO"' EXIT
git diff --name-only --diff-filter=ACMR "$BASE" HEAD > "$TRABAJO/subir.txt"
git diff --name-only --diff-filter=D    "$BASE" HEAD > "$TRABAJO/borrar.txt"
N_SUBIR=$(grep -c . "$TRABAJO/subir.txt" || true)
N_BORRAR=$(grep -c . "$TRABAJO/borrar.txt" || true)

paso "Cambios de $(git rev-parse --short "$BASE") a $(git rev-parse --short "$COMMIT")"
echo "  $N_SUBIR archivo(s) a subir, $N_BORRAR a borrar"
git diff --name-status "$BASE" HEAD | sed 's/^/    /'

MIGRACIONES=$(grep '^database/migrations/' "$TRABAJO/subir.txt" || true)
if [ -n "$MIGRACIONES" ]; then
  echo; echo "  Migraciones nuevas:"; echo "$MIGRACIONES" | sed 's|.*/|    |'
fi

if [ "$DRY" -eq 1 ]; then
  echo; verde "Dry-run: no se tocó producción."
  exit 0
fi

if [ "$SI" -eq 0 ]; then
  echo; printf '¿Desplegar a PRODUCCIÓN (%s)? [s/N] ' "$URL"
  read -r r; [ "$r" = "s" ] || { echo "Cancelado."; exit 1; }
fi

[ "$N_SUBIR" -gt 0 ] || { rojo "No hay archivos que subir."; exit 1; }
tr '\n' '\0' < "$TRABAJO/subir.txt" | xargs -0 git archive --format=zip -o "$TRABAJO/paquete.zip" HEAD --

# ------------------------------------------------------------------- respaldos
FECHA=$(date +%F-%H%M)
paso "Respaldando base de datos y código en $RESPALDOS"
remoto sudo bash -s -- "$FECHA" "$RUTA" "$RESPALDOS" <<'REMOTO'
set -euo pipefail
FECHA=$1; RUTA=$2; RESPALDOS=$3
mkdir -p "$RESPALDOS"; chmod 700 "$RESPALDOS"; cd "$RESPALDOS"
cfg() { grep "^$1=" "$RUTA/.env" | head -1 | cut -d= -f2- | tr -d '"'; }
umask 077
# --defaults-extra-file para no exponer la contraseña en `ps`
printf '[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n' \
  "$(cfg DB_USERNAME)" "$(cfg DB_PASSWORD)" "$(cfg DB_HOST)" "$(cfg DB_PORT)" > .my.cnf
trap 'rm -f "$RESPALDOS/.my.cnf"' EXIT
mysqldump --defaults-extra-file=.my.cnf --single-transaction --routines "$(cfg DB_DATABASE)" \
  | gzip > "db-$FECHA.sql.gz"
tar -czf "code-$FECHA.tar.gz" -C "$(dirname "$RUTA")" \
  --exclude="$(basename "$RUTA")/storage/logs" \
  --exclude="$(basename "$RUTA")/node_modules" \
  --exclude="$(basename "$RUTA")/vendor" "$(basename "$RUTA")"
ls -lh "db-$FECHA.sql.gz" "code-$FECHA.tar.gz"
# Un volcado sin CREATE TABLE es un respaldo inservible: mejor fallar aquí.
[ "$(zcat "db-$FECHA.sql.gz" | grep -c '^CREATE TABLE')" -gt 0 ] || { echo "Respaldo de BD vacío"; exit 1; }
REMOTO

# --------------------------------------------------------------------- aplicar
paso "Subiendo archivos"
ETAPA="/tmp/despliegue-$FECHA"
remoto "mkdir -p '$ETAPA'"
scp -q "$TRABAJO/paquete.zip" "$TRABAJO/subir.txt" "$TRABAJO/borrar.txt" "$HOST:$ETAPA/"

paso "Aplicando en $RUTA"
remoto sudo bash -s -- "$ETAPA" "$RUTA" "$COMMIT" "$MIGRAR" <<'REMOTO'
set -euo pipefail
ETAPA=$1; RUTA=$2; COMMIT=$3; MIGRAR=$4
cd "$RUTA"
unzip -oq "$ETAPA/paquete.zip" -d "$RUTA"
tr '\n' '\0' < "$ETAPA/subir.txt" | (cd "$RUTA" && xargs -0 -r chown www-data:www-data)
while IFS= read -r f; do [ -n "$f" ] && rm -f "$RUTA/$f"; done < "$ETAPA/borrar.txt"

if [ "$MIGRAR" -eq 1 ]; then
  echo "-- migraciones pendientes:"
  sudo -u www-data php artisan migrate:status | grep -i pending || echo "   (ninguna)"
  sudo -u www-data php artisan migrate --force
fi

for c in config:clear route:clear view:clear cache:clear; do sudo -u www-data php artisan "$c" -q; done
sudo -u www-data php artisan config:cache -q
sudo -u www-data php artisan route:cache -q
echo "-- cachés regeneradas"

echo "$COMMIT" > "$RUTA/.deployed-commit"
chown www-data:www-data "$RUTA/.deployed-commit"
rm -rf "$ETAPA"
REMOTO

# ----------------------------------------------------------------- verificación
paso "Verificando $URL"
FALLOS=0
for ruta in / /login /crm /crm/tablero; do
  codigo=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$URL$ruta" || echo 000)
  case "$codigo" in
    200|302) printf '  %-16s %s\n' "$ruta" "$codigo" ;;
    *)       printf '  %-16s ' "$ruta"; rojo "$codigo"; FALLOS=$((FALLOS + 1)) ;;
  esac
done

# La fecha se calcula en el servidor: producción va en UTC y la local no.
ERRORES=$(remoto "sudo grep -c \"^\\[\$(date +%F).*ERROR\" '$RUTA/storage/logs/laravel.log' 2>/dev/null; true" | tr -dc '0-9')
echo "  errores en laravel.log hoy: ${ERRORES:-0}"

echo
if [ "$FALLOS" -eq 0 ]; then
  verde "Desplegado $(git rev-parse --short "$COMMIT") en $URL"
else
  rojo "Terminó con $FALLOS ruta(s) respondiendo mal. Revisa antes de irte."
fi
echo "Para revertir el código:"
echo "  ssh $HOST 'sudo tar -xzf $RESPALDOS/code-$FECHA.tar.gz -C $(dirname "$RUTA")'"
echo "La base de datos está en $RESPALDOS/db-$FECHA.sql.gz (restaurar sólo si una migración rompió algo)."

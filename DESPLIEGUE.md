# Despliegue

Producción: **https://erp.juancker.com** — instancia AWS EC2, compartida con
Clínica Dental. Acceso por SSH con el alias `smileintelli` de `~/.ssh/config`
(`54.213.100.154`, usuario `ubuntu`, llave `ClinicaDental.pem`).

    RUTA_PRODUCCION=/var/www/erp

Los archivos son de `www-data`, no de `ubuntu`: sube a `/tmp` y descomprime con
`sudo`, y corre cada comando de artisan con `sudo -u www-data`. Tinker además
necesita un `HOME` escribible (`sudo -u www-data env HOME=/tmp php artisan
tinker`), porque `/var/www/.config` es de solo lectura.

**Último despliegue:** 2026-09-09 — commit `993183e` (módulo CRM completo:
código, rutas y las tres migraciones `2026_09_09_*`). Usa ese commit como
`<ultimo-desplegado>` en el paso 1.

El repositorio `origin` (github.com/Fercho3d/juankker_erp) **no es la fuente de
producción**: `origin/main` está varios commits atrás de lo que corre en línea.
Mientras eso siga así, el despliegue es manual y este documento es la referencia.

## Despliegue automatizado

    ./scripts/desplegar.sh -n     # muestra qué cambiaría, sin tocar nada
    ./scripts/desplegar.sh        # respalda, sube, migra y limpia cachés

El script hace por sí solo todo lo de la sección siguiente: calcula el diff
contra el último commit desplegado (lo guarda en `.deployed-commit` dentro del
servidor), respalda base de datos y código, sube sólo lo que cambió, borra lo
que se eliminó, corre `migrate --force`, regenera las cachés y verifica que
`/`, `/login`, `/crm` y `/crm/tablero` respondan. Al final imprime el comando
de reversión.

Opciones: `--desde <commit>` para forzar la base de comparación,
`--sin-migrar` para no tocar la base de datos, `-y` para no preguntar.

## Despliegue manual

Si el script no sirve (servidor distinto, sudo con contraseña), desde la
máquina local, en la raíz del proyecto:

    # 1. Empaquetar solo lo que cambió respecto al último despliegue
    git archive --format=zip --output=/tmp/deploy.zip HEAD $(git diff --name-only <ultimo-desplegado> HEAD)

    # 2. Subir
    scp /tmp/deploy.zip smileintelli:/tmp/

    # 3. Aplicar
    ssh smileintelli
    cd "$RUTA_PRODUCCION"
    cp -r . ../respaldo-$(date +%F-%H%M)     # respaldo antes de sobrescribir
    unzip -o /tmp/deploy.zip -d .
    php artisan migrate --force
    php artisan config:clear && php artisan route:clear && php artisan view:clear

Si producción usa cachés compiladas, regenéralas al final:

    php artisan config:cache && php artisan route:cache

Respalda también la base de datos antes de cualquier migración. Los respaldos
viven en `/var/backups/erp/` (`db-<fecha>.sql.gz` y `code-<fecha>.tar.gz`); el
volcado se hace con `mysqldump --defaults-extra-file=` para no exponer la
contraseña en `ps`.

## Después de desplegar, revisar

- `/login` responde y el campo de contraseña trae el botón de ver/ocultar.
- `/crm` y `/crm/tablero` responden (404 aquí = faltó `route:clear`).
- La organización propia está en plan Profesional o Enterprise; si sigue en
  Gratis, `/crm` redirige a `/precios`.

## Errores frecuentes

| Síntoma | Causa |
| --- | --- |
| 404 en rutas nuevas | Caché de rutas sin limpiar |
| Redirige a `/precios` | La organización no tiene el módulo en su plan |
| 500 al abrir una vista | `storage/framework/views` sin permiso de escritura para el usuario del servidor web |
| Cambios que no se ven | Caché de configuración o de vistas |

## Correo

Servicio: **Resend**, cuenta de `jfs.ddd.artist@gmail.com`. Antes colgaba de la
cuenta del Workspace `fernando.salas@smileintelli.com`, que se dio de baja;
migrado el 2026-09-09.

Dos dominios verificados, ambos en región `us-east-1`, con el DNS en Cloudflare
y los buzones en Migadu:

| Sitio | Dominio | Remitente | Transporte | Llave |
| --- | --- | --- | --- | --- |
| ERP | `juancker.com` | `no-reply@juancker.com` | SMTP (`smtp.resend.com:587`, usuario `resend`) | `erp-juancker` en `MAIL_PASSWORD` |
| Clínica | `smileintelli.com` | `no-reply@smileintelli.com` | driver `resend` | `clinica-smileintelli` en `RESEND_API_KEY` |

Cada llave es de **sólo envío y acotada a su dominio**: no puede leer la cuenta
ni emitir otras llaves (la API responde 401 a cualquier lectura). No compartas
una sola llave entre los dos sitios, y nunca uses una *full access* como
contraseña SMTP: quien entre al servidor se llevaría la cuenta entera.

Cada dominio necesita tres registros en Cloudflare: `MX send` →
`feedback-smtp.us-east-1.amazonses.com`, `TXT send` →
`v=spf1 include:amazonses.com ~all`, y `TXT resend._domainkey` con la clave
DKIM que genera Resend.

Lo que nos costó media hora averiguar, para no repetirlo:

- **El DKIM es uno por cuenta.** Si mueves un dominio a otra cuenta de Resend
  hay que *reemplazar* ese TXT, no añadir otro. Con dos TXT en ese nombre la
  verificación falla siempre.
- **El autoconfigure de Cloudflare añade, no reemplaza.** En un dominio que
  viene de otra cuenta te deja justo ese duplicado.
- **Un dominio en `failed` no se revalida solo.** Hay que darle *Verify* a mano
  aunque el DNS ya esté correcto.
- **No toques el SPF del dominio raíz** (`include:spf.migadu.com`). El de
  Resend va en `send`, que es otro nombre. Un segundo TXT de SPF en la raíz
  invalida el SPF entero y tumba el correo de Migadu.
- En Cloudflare el campo *Name* va corto (`send`, `resend._domainkey`); si
  escribes el FQDN te queda `send.juancker.com.juancker.com`.

Para probar un envío sin abrir el sitio:

    sudo -u www-data env HOME=/tmp php artisan tinker --execute='Mail::raw("prueba", fn($m) => $m->to("tu@correo")->subject("prueba"));'

## Variables de entorno propias del proyecto

    DENUE_TOKEN=      # Token del API del DENUE (INEGI), para los comandos de prospección

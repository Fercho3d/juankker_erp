# Despliegue

Producción: **https://erp.juancker.com** — instancia AWS EC2, compartida con
Clínica Dental. Acceso por SSH con el alias `smileintelli` de `~/.ssh/config`
(`54.213.100.154`, usuario `ubuntu`, llave `ClinicaDental.pem`).

> **Pendiente de confirmar:** la ruta del proyecto dentro del servidor.
> Averíguala con `ssh smileintelli 'ls -d /var/www/*'` y anótala aquí la
> primera vez, para no volver a buscarla.
>
>     RUTA_PRODUCCION=/var/www/____

El repositorio `origin` (github.com/Fercho3d/juankker_erp) **no es la fuente de
producción**: `origin/main` está varios commits atrás de lo que corre en línea.
Mientras eso siga así, el despliegue es manual y este documento es la referencia.

## Despliegue manual

Desde la máquina local, en la raíz del proyecto:

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

Respalda también la base de datos antes de cualquier migración.

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

## Variables de entorno propias del proyecto

    DENUE_TOKEN=      # Token del API del DENUE (INEGI), para los comandos de prospección

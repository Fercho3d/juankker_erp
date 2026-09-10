# Antigravity & Opus Optimization Rules

## Reasoning Controls (Token Saving)
- **Adaptive Thinking:** Use `effort: low` for routine tasks (refactoring, documentation, CSS, standard Laravel controllers/migrations).
- **Thinking Budget:** Do not exceed 500 words of reasoning for tasks involving < 3 files.
- **Direct Action:** If the solution is obvious, skip the "Implementation Plan" artifact and go straight to the code diff.

## Context Management (PHP/Laravel Specifics)
- **Focused Scoping:** Only analyze files explicitly requested or directly related to the current bug/feature. 
- **Migration Policy:** Do not read the entire `database/migrations` folder unless database schema changes are required. Use `php artisan model:show [Model]` if you need table structure.
- **Vendor Exclusion:** Never index or read the `vendor/` or `node_modules/` directories.
- **Route Pruning:** Use `php artisan route:list` sparingly; only look at relevant route files.

## Antigravity Agent Rules
- **Lazy Loading:** Do not use `grep` or `find` on the whole project. Target specific directories (`app/`, `resources/`, `routes/`).
- **One-Shot Execution:** Try to batch terminal commands. Instead of 3 separate commands, run `composer install && php artisan migrate && npm run dev`.
- **Artifact Control:** Only generate a `Task List` for features that require more than 5 distinct steps.

## Coding Style & Standards
- Follow PSR-12 and Laravel's latest conventions.
- Keep methods lean. If a method exceeds 20 lines, suggest a refactor instead of just adding more code.
## Arquitectura del proyecto

ERP SaaS multi-tenant en Laravel 10. Todo dato de negocio cuelga de
`organization_id`; el scope de tenant se aplica en cada consulta, no por
middleware global. Los planes (`plans.modules`) definen qué módulos ve cada
organización, y las rutas se protegen con `premium:<modulo>`.

### Módulo CRM

- **Modelos:** `CrmStage` (etapas del embudo, se siembran solas por organización),
  `Lead` (prospecto y oportunidad en una sola entidad), `CrmActivity` (bitácora
  y agenda a la vez).
- **Pantallas:** `/crm` pendientes del día, `/crm/tablero` Kanban,
  `/crm/leads/{id}` ficha con bitácora, `/crm/importar` alta masiva.
- **API:** `/api/crm/*` con tokens de Sanctum. Genera uno con
  `php artisan crm:token <correo>`.
- **Prospección:** `denue:prospectar` (API del INEGI, requiere `DENUE_TOKEN`) y
  `denue:csv` (archivos descargados, sin token).
- `Lead.client_id` enlaza al `Client` del ERP cuando el prospecto se gana.

Al agregar un módulo nuevo: dale su clave en `plans.modules`, protégelo con
`premium:<clave>` y agrega el enlace en `layouts/app.blade.php`.

### Menú, tema e idiomas

- **Cascarón:** `layouts/app.blade.php` (menú lateral por secciones, barra superior
  y menú de usuario) con estilos propios en `public/css/shell.css` y
  `public/js/shell.js`, sin Tailwind ni compilación. Un módulo nuevo se agrega
  al arreglo `$nav` del layout, con su ícono en `partials/nav-icon`.
- **Tema** claro/oscuro/sistema (`App\Support\Theme`) e **idioma** es/en
  (`App\Support\Locale`), mismo esquema que CargoSuite: se guardan en
  `users.theme`/`users.locale` y en cookie, y los aplica `SetLocale` y
  `partials/theme-script`.
- **Modo oscuro:** las vistas usan colores de Tailwind escritos a mano y
  `public/css/dark.css` los reasigna bajo `html.dark`. Si una vista nueva usa un
  tono que no está ahí y se ve mal en oscuro, se agrega a esa hoja.
- **Idiomas:** el español es la lengua base. Todo texto visible va en
  `__('Texto en español')` y su traducción en `lang/en.json`; lo que falte sale
  en español. Frases con datos van con marcadores (`__('Van :n', ['n' => $n])`)
  y plurales con `trans_choice`. En JavaScript, `{{ Js::from(__('...')) }}`:
  **no** `@json()`, que parte el argumento por las comas. Revisa lo pendiente con
  `php artisan idiomas:revisar`.

## Despliegue

Ver `DESPLIEGUE.md`. En resumen: producción es manual por SSH/scp contra el
host `smileintelli`, **no** por `git pull` — `origin/main` está atrás de lo que
corre en línea. Después de subir archivos hay que correr `migrate --force` y
limpiar las cachés de rutas, configuración y vistas.

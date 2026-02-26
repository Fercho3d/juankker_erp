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
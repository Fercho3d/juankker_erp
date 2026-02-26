# ERP System with POS

## Overview
This repository contains a lightweight **PHP‑based ERP** application that includes a **Point‑of‑Sale (POS)** module. The system provides features for managing clients, suppliers, products, inventory, sales, and user access.

## Core Features
- **Clients & Suppliers** – CRUD interfaces, searchable tables, import/export utilities.
- **Product Catalog** – Categories, brands, attributes, and variant management.
- **Inventory Management** – Stock tracking, adjustments, low‑stock alerts.
- **Sales & POS** – Real‑time checkout, payment handling, receipt (PDF) generation, and sales reporting.
- **User Management** – Role‑based access control and profile editing.

## Technology Stack
- **Backend**: Plain PHP 8.x with Composer.
- **Database**: MySQL / MariaDB using PDO.
- **Templating**: Simple Blade‑like syntax (e.g., `philo/templating`).
- **Styling**: Tailwind CSS for a modern responsive UI.
- **Build Tools**: Vite for asset bundling.
- **Version Control**: Git, hosted on GitHub.

## Getting Started
1. Clone the repository.
2. Run `composer install`.
3. Copy `.env.example` to `.env` and configure the database.
4. Run migrations (`php artisan migrate`).
5. Install front‑end dependencies: `npm install && npm run dev`.
6. Serve the app: `php -S localhost:8000 -t public`.

## License
This project is open‑sourced software licensed under the MIT license.

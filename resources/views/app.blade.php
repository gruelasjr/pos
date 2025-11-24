<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
<?php
/**
 * View: application layout (Blade template).
 *
 * Root layout used by the application pages.
 *
 * PHP 8.1+
 *
 * @package   Resources\Views
 */
?>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'POS') }}</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        @inertia
    </body>
</html>

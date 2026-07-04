<?php

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';

    // Base directory for the namespace prefix
    // By default, assuming App points to web/app/
    // But if it's App\Admin, it points to web/admin/

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Check if it's an admin class
    if (str_starts_with($relative_class, 'Admin\\')) {
        $base_dir = __DIR__ . '/../../admin/';
        $relative_class = substr($relative_class, 6);
    } else {
        $base_dir = __DIR__ . '/../';
    }

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    // To match directory structures (e.g. Controllers, Core) with actual paths (e.g. controllers, core)
    // we need to lowercase the first directory segment if appropriate, but since Linux is case sensitive
    // and we created lowercase folders (`controllers`, `core`), let's map them.

    $parts = explode('\\', $relative_class);
    // Lowercase the first part (e.g. Core -> core, Controllers -> controllers)
    if (isset($parts[0])) {
        $parts[0] = strtolower($parts[0]);
    }

    $file = $base_dir . implode('/', $parts) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

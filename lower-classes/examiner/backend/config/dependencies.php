<?php
use Auryn\Injector;
use Medoo\Medoo;

return function (): Injector {
    // --- PHP error logging & timezone (runs early) ---
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
    error_reporting(E_ALL);
    date_default_timezone_set('Africa/Nairobi'); // PHP time in Nairobi

    $injector = new Injector();

    // Bind the Medoo database
    $config   = require __DIR__ . '/config.php';
    $database = new Medoo($config['database']);

    // --- Ensure MySQL session time zone is Nairobi too ---
    try {
        // Works if MySQL has time zone tables loaded
        $database->query("SET time_zone = 'Africa/Nairobi'");
    } catch (\Throwable $e) {
        // Fallback to fixed offset (EAT = UTC+03:00; no DST)
        try {
            $database->query("SET time_zone = '+03:00'");
        } catch (\Throwable $e2) {
            // Optional: log but don't break the app
            error_log('Failed to set MySQL time_zone: ' . $e->getMessage());
        }
    }

    $injector->share($database); // Medoo will be injected wherever required
    return $injector;
};

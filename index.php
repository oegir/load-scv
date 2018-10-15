<?php
/**
 * Точка входа в приложение
 * 
 * @author Alexey Petrov
 */

// Подключаются ресурсы приложения
require_once str_replace(['/', '\\'], DIRECTORY_SEPARATOR, __DIR__ . '/App/resource/strings.php');
// Автозагрузка классов
spl_autoload_register(function ($class_name) {
    include str_replace(['/', '\\'], DIRECTORY_SEPARATOR, __DIR__ . '/' . $class_name . '.php');
});
// Выполнение приложения
$controller = new App\Controller(App\Config::getConfig());
$controller->execute($_REQUEST['action'] ?? 'index');

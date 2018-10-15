<?php
namespace App;

use App\Base\BaseConfig;

/**
 * Конфигурация приложения
 * 
 * @author Alexey Petrov
 */
class Config extends BaseConfig
{
    protected $config = [
        'App\Controller:index' => 'App\View',   // Вид для действия по умолчанию
        'App\View:Template' => __DIR__ . '/tpl/default.php',    // Шаблон для действия по умолчанию
        
        'App\Controller:import' => 'App\AjaxView',  // Вид для действия импорта
        
        'App\Controller:parallel_import' => 'App\AjaxView', // Вид для действия параллельного импорта
        
        'App\Model:ImportTimeLimit' => 30,  // Время в сек, ожидания итерации импорта
         
        'App\CurlModel:PartFileSize' => 1000,   // Количество строк в частичном файле для импорта
        'App\CurlModel:CurlWaitTime' => 10,  // Частота опроса cURL-потоков в микросекундах
        'App\CurlModel:CurlThreads' => 8,  // Количество одновременно запускаемых cURL-потоков
        
        'App\Base\BaseModel:DbHost' => 'localhost', // Данные для подключения к БД
        'App\Base\BaseModel:DbName' => 'kelnik_test',
        'App\Base\BaseModel:DbUser' => 'user',
        'App\Base\BaseModel:DbPass' => '12345',
    ];
}
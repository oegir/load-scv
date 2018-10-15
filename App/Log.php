<?php
namespace App;

class Log
{
    public static function write(string $message): void
    {
        $data = date('Y-m-d H:i:s (T)') . ' - ' . $message . PHP_EOL;
        file_put_contents (str_replace(['/', '\\'], DIRECTORY_SEPARATOR, __DIR__ . '/log.txt') , $data, FILE_APPEND);
    }
}
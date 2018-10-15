<?php
namespace App\Base;

/**
 * Класс конфигурации приложения.
 * Конфигурация должна задаваться в конкретном классе-наследнике путем переопределения свойства $config,
 * Ключи массива $config - свойства конфигурации
 * 
 * @author Alexey Petrov
 *
 */
abstract class BaseConfig
{
    /**
     * @var array конфигурация приложения
     */
    protected $config = [];

    /**
     * @var BaseConfig объект конфигурации
     */
    private static $object;
    
    /**
     * Непосредственное создание объекта конфигурации запрещено
     */
    private function __construct()
    {}
    
    /**
     * Геттер для свойства конфигурации
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function get(string $name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        } else {
            throw new \Exception(sprintf(BASE_ERROR_CONFIG_NOT_SET, $name), 1001);
        }
    }
    
    /**
     * Геттер конфигурации
     * 
     * @return array
     */
    public static function getConfig(): BaseConfig
    {
        if (empty(self::$object)) {
            $class_name = static::class;
            self::$object = new $class_name();
        }
        return self::$object;
    }
}
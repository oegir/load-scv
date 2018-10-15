<?php
namespace App\Base;

use \PDO;
/**
 * Базовый класс модели
 * При иницализации класса создает подключение к БД MySQL
 * Поддерживает действие по умолчанию (index)
 * Вид для отображения по этому действию должен быть задан ключем конфигурации 'indexView'
 *
 * @author Alexey Petrov
 */
abstract class BaseModel
{
    /**
     * @var BaseConfig конфигурация приложения
     */
    private $config;
    /**
     * @var PDO объект соединения с БД
     */
    private $db;
    
    /**
     * @throws \Exception
     */
    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
        
        $dsn = 'mysql:host=' . $config->get(self::class . ':DbHost') . ';dbname=' . $config->get(self::class . ':DbName');
        $this->db = new PDO($dsn, $config->get(self::class . ':DbUser'), $config->get(self::class . ':DbPass'));
    }
    
    /**
     * Возвращает значение парметра конфигурации
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    protected function getConfigParam(string $name)
    {
        return $this->config->get($name);
    }
    
    /**
     * Геттер для $this->db
     * @return PDO
     */
    protected function getDb(): PDO
    {
        return $this->db;
    }
}
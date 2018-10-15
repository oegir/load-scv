<?php
namespace App\Base;

use App;

/**
 * Базовый класс контроллера
 * Поддерживает действие по умолчанию (index)
 * Вид для отображения по этому действию должен быть задан ключем конфигурации 'indexView'
 * 
 * @author Alexey Petrov
 */
abstract class BaseController
{
    /**
     * @var BaseConfig конфигурация приложения
     */
    private $config;
    
    /**
     * Конструктор класса
     * @param BaseConfig $config конфигурация приложения
     */
    public function __construct(BaseConfig $config) {
        $this->config = $config;
    }

    /**
     * Выполняет действие контроллера
     * 
     * @param string $action имя действия
     */
    public function execute(string $action = 'index'): void
    {
        
        try {
            // Получение имени действия
            if (! method_exists($this, $action)) {
                $action = 'index'; // Действие по умолчанию
            }
            // Выполнение действия
            $result = $this->$action();
            // Отображение результатов действия
            $view_name = $this->getConfigParam(static::class . ':' . $action);
            $view = $this->getView($view_name, $result);
            $view->display();
        } catch (\Exception $e) {
            // Журналирование ошибки
            App\Log::write(sprintf(BASE_ERROR_WEBAPP_END, $e->getCode(), $e->getMessage()));
            exit;
        }
    }
    
    /**
     * Геттер для конфигурации
     * @return BaseConfig
     */
    public function getConfig(): BaseConfig
    {
        return $this->config;
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
     * Метод для получения вида
     * 
     * @param string $view_name имя класса вида
     * @param array $data данные для отображения
     * @return BaseView
     */
    private function getView(string $view_name, array $data): BaseView
    {
        return new $view_name($this->getConfig(), $data);
    }
    
    /**
     * Действие по умолчанию
     * 
     * @return array
     * @throws \Exception
     */
    abstract public function index(): array;
}
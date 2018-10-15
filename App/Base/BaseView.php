<?php
namespace App\Base;

/**
 * @author Alexey Petrov
 *
 */
abstract class BaseView
{
    /**
     * @var BaseConfig конфигурация приложения
     */
    private $config;
    /**
     * @var array данные для отображения
     */
    protected $data = [];
    
    /**
     * Конструктор
     * @param BaseConfig $config конфигурация приложения
     * @param array $data данные для вывода
     * @throws \Exception
     */
    public function __construct(BaseConfig $config, array $data)
    {
        $this->config = $config;
        $this->data = $data;
    }
    
    /**
     * Событие перед отображением шаблона
     * Для отмены отображения шаблона нужно вернуть FALSE
     * @return bool|NULL
     */
    public function beforeDisplay(): ?bool
    {
        return TRUE;
    }
    
    /**
     * Отображение шаблона
     * @throws \Exception
     */
    public function display(): void
    {
        ob_start();
        // Возможна отмена вывода шаблона в событии перед отображением
        if ($this->beforeDisplay() !== FALSE) {
            extract($this->getData());
            include str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->getConfigParam(static::class . ':Template'));
        }
        
        ob_end_flush();
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
    
    protected function getData(): array
    {
        return $this->data;
    }
}
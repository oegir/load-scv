<?php

namespace App;

use App\Base\BaseModel;

/**
 * Импорт информации из файла в базу данных
 * 
 * @author Alexey Petrov
 *
 */
class LoadModel extends BaseModel
{
    /**
     * @var array уже существующие в базе артикулы
     * [ int => [
     *      'ID' => string,
     *      'ARTICUL' => string
     *  ],
     * ]
     */
    private $exists_articuls = [];
    /**
     * @var array данные из файла импорта
     * [ int => [
     *      'ARTICUL' => string,
     *      'PRICE' => string,
     *      'COUNT' => string
     *  ],
     * ]
     */
    private $file_data = [];
    /**
     * @var string имя файла для импорта
     */
    private $file_name = '';
    /**
     * @var resource ресурс открытого файла
     */
    private $file_resource;
    /**
     * @var integer счетчик добавленных артикулов
     */
    private $inserted = 0;
    /**
     * @var string исходное имя файла, под которым его загрузил пользователь
     */
    private $original_file_name = '';
    /**
     * @var integer счетчик прочитанных строк
     */
    private $row_count = 0;
    /**
     * @var integer количество обновленных строк
     */
    private $updated = 0;
    
    /**
     * Добавляет в базу новые артикулы
     */
    private function addNew(): void
    {
        if (empty($new_data = $this->findNew())) {
            return;
        }
        // Подготовка запроса
        $query = 'INSERT INTO `test` (`ARTICUL`, `PRICE`, `COUNT`) VALUES' . PHP_EOL;
        $params = [];
        
        // Подготовка параметров запроса
        foreach ($new_data as $key => $new_articul_data) {
            $query .= "(:ARTICUL$key, :PRICE$key, :COUNT$key)," . PHP_EOL;
            
            $params["ARTICUL$key"] = $new_articul_data['ARTICUL'];
            $params["PRICE$key"] = $new_articul_data['PRICE'];
            $params["COUNT$key"] = $new_articul_data['COUNT'];
        }
        $query = rtrim($query, ',' . PHP_EOL);
        
        $statement = $this->getDb()->prepare($query);
        // Выполнение запроса
        if (! $statement->execute($params)) {
            throw new \Exception($statement->errorInfo(), $statement->errorCode());
        }
        $this->increaseInserted($statement->rowCount());
    }
    
    /**
     * Находит уже существующие в базе артикулы
     * @return array существующие артикулы
     * [ int => [
     *      'ID' => string,
     *      'ARTICUL' => string
     *  ],
     * ]
     */
    private function findExists(): array
    {
        $articuls = array_column($this->getFileData(), 'ARTICUL');
        
        $in_condition  = str_repeat('?,', count($articuls) - 1) . '?';
        $query = "SELECT `ID`, `ARTICUL` FROM `test` WHERE `ARTICUL` IN ($in_condition)";
        $statement = $this->getDb()->prepare($query);
        $statement->execute($articuls);
        
        $exists_articuls = $statement->fetchAll(\PDO::FETCH_ASSOC);
        
        return $exists_articuls;
    }
    
    /**
     * Выбирает из данных файла импорта только новые артикулы
     * @return array артикулы, которых еще нет в базе данных
     * [ int => [
     *      'ARTICUL' => string,
     *      'PRICE' => string,
     *      'COUNT' => string
     *  ],
     * ]
     */
    private function  findNew(): array
    {
        $file_data = $this->getFileData();
        $file_articuls = array_column($file_data, 'ARTICUL');
        $exists_articuls = array_column($this->getExistsArticuls(), 'ARTICUL');
        // Новые артикулы - это разница значений массивов артикулов из файла и существующих
        $new_articuls = array_diff($file_articuls, $exists_articuls);
        // Новые данные выбираются по ключам из полных данных файла
        $new_data = array_intersect_key($file_data, $new_articuls);
        
        return $new_data;
    }
    
    /**
     * Геттер для $this->exists_articuls
     * @return array уже существующие в базе артикулы из тех, что импортируются из файла
     * [ int => [
     *      'ID' => string,
     *      'ARTICUL' => string
     *  ],
     * ]
     */
    private function getExistsArticuls(): array
    {
        if (empty($this->exists_articuls)) {
            $this->setExistsArticuls($this->findExists());
        }
        return $this->exists_articuls;
    }
    
    /**
     * Геттер для $this->file_data
     * @return array данные из импортируемого файла
     * [ int => [
     *      'ARTICUL' => string,
     *      'PRICE' => string,
     *      'COUNT' => string
     *  ],
     * ]
     */
    private function getFileData(): array
    {
        if (empty($this->file_data)) {
            $this->setFileData($this->loadFile());
        }
        return $this->file_data;
    }
    
    /**
     * Геттер для $this->file_name
     * @return string имя файла для импорта
     */
    private function getFileName(): string
    {
        return $this->file_name;
    }
    
    /**
     * Открыает на чтение файл импорта
     * Геттер для $this->file_resource
     * @return resource
     * @throws \Exception
     */
    private function getFileResource()
    {
        if (! $this->file_resource) {
            ini_set('auto_detect_line_endings', true);
            
            if (!($this->file_resource = @fopen($this->getFileName(), 'r'))) {
                throw new \Exception(sprintf(ERROR_MODEL_OPEN_FILE, $this->getOriginalFileName()), 1006);
            }
        }
        return $this->file_resource;
    }
    
    /**
     * Геттер для $this->inserted
     * @return int количество добавленных артикулов
     */
    public function getInserted(): int
    {
        return $this->inserted;
    }
    
    /**
     * Геттер для $this->original_file_name
     * @return string имя файла для импорта
     */
    private function getOriginalFileName(): string
    {
        return $this->original_file_name;
    }
    
    /**
     * Геттер для $this->row_count
     * @return int количество обработанных строк
     */
    public function getRowCount(): int
    {
        return $this->row_count;
    }
    
    /**
     * Геттер для $this->updated
     * @return int количество обновленных артикулов
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }
    
    /**
     * Импорт данных из файла
     * @throws \Exception
     */
    public function import_file(): void
    {
        try {
            // Импорт данных
            $this->updateExists();
            $this->addNew();
        } catch (\Exception $e) {
            // Логирование ошибки, передача выше по стеку
            Log::write(sprintf(ERROR_MODEL, $e->getCode(), $e->getMessage()));
            throw new \Exception(sprintf(ERROR_MODEL_IMPORT, $this->getOriginalFileName()), 1002);
        } finally {
            $this->removeFile();
        }
    }
    
    /**
     * Увеличивает значение счетчика обновленных строк на указанную величину
     * @param int $value
     */
    private function increaseInserted(int $value): void
    {
        $this->inserted += $value;
    }
    
    /**
     * Увеличивает значение счетчика обновленных строк на указанную величину
     * @param int $value
     */
    private function increaseUpdated(int $value): void
    {
        $this->updated += $value;
    }
    
    /**
     * Увеличивает значение счетчика на 1
     * @return int
     */
    private function incrementRowCount(): int
    {
        $this->row_count++;
        
        return $this->row_count;
    }
    
    /**
     * Читает содержимое импортируемого файла
     * @return array результат импорта
     * [ int => [
     *      'ARTICUL' => string,
     *      'PRICE' => string,
     *      'COUNT' => string
     *  ],
     * ]
     * @throws \Exception
     */
    private function loadFile(): ?array
    {
        // Данные из файла
        $data = [];
        
         do {
            // Чтение строки из файла
            if (($buffer = fgets($this->getFileResource(), 4096)) === FALSE) {
                break;
            }
            // Парсинг прочитанной строки
            $values = explode(';', $buffer);
            
            if (count($values) != 3) {
                throw new \Exception(sprintf(ERROR_MODEL_FILE_ROW_READ, $this->getRowCount(), $this->getOriginalFileName()), 1003);
            }
            // Сохранение данных
            $this->incrementRowCount();
            
            $data [] = [
                'ARTICUL' => trim($values[0]),
                'PRICE' => trim($values[1]),
                'COUNT' => trim($values[2])
            ];
         } while ($buffer !== FALSE);
         
         return $data;
    }
    
    /**
     * Закрывет и удаляет временный файл
     */
    private function removeFile(): void
    {
        fclose($this->getFileResource());
        unlink($this->getFileName());
    }
    
    /**
     * Сеттер для $this-exists_articuls
     * @param array $exists_articuls уже существующие в базе артикулы из тех, что загружаеются из файла
     * [ int => [
     *      'ID' => string,
     *      'ARTICUL' => string
     *  ],
     * ]
     */
    private function setExistsArticuls(array $exists_articuls): void
    {
        $this->exists_articuls = $exists_articuls;
    }
    
    /**
     * Сеттер для $this->file_data
     * @param array $data данные, полученные из файла
     * [ int => [
     *      'ARTICUL' => string,
     *      'PRICE' => string,
     *      'COUNT' => string
     *  ],
     * ]
     */
    private function setFileData(array $data): void
    {
        $this->file_data = $data;
    }
    
    /**
     * Сеттер для $this->file_name
     * @param string $file_name имя файла для импорта
     */
    public function setFileName(string $file_name): void
    {
        $this->file_name = $file_name;
    }
    
    /**
     * Сеттер для $this->original_file_name
     * @param string $file_name имя файла для импорта
     */
    public function setOriginalFileName(string $original_file_name): void
    {
        $this->original_file_name = $original_file_name;
    }
    
    /**
     * Обновляет информацию о существующих артикулах
     * @throws \Exception
     */
    private function updateExists(): void
    {
        $file_data = $this->getFileData();
        $articul_file = array_column($file_data, 'ARTICUL');
        // Подготовка запроса
        $query = "UPDATE `test` SET `PRICE` = ?, `COUNT`=? WHERE `ID` = ?";
        $statement = $this->getDb()->prepare($query);
        
        foreach ($this->getExistsArticuls() as $id_articul) {
            // Ключ текущего артикула в массиве данных из файла
            $key_in_file = array_search($id_articul['ARTICUL'], $articul_file);
            
            if ($key_in_file !== FALSE) {
                // Параметры запроса
                $params = [];
                $params[] = $file_data[$key_in_file]['PRICE'];
                $params[] = $file_data[$key_in_file]['COUNT'];
                $params[] = $id_articul['ID'];
                // Выполнение запроса
                if (! $statement->execute($params)) {
                    throw new \Exception($statement->errorInfo(), $statement->errorCode());
                }
                $this->increaseUpdated($statement->rowCount());
            }
        }
    }
}
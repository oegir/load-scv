<?php
namespace App;

use App\Base\BaseModel;

/**
 * Распараллеливание процесса импорта с помощью Curl
 *
 * @author Alexey Petrov
 *
 */
class CurlModel extends BaseModel
{
    /**
     * @var integer количество обнуленных строк
     */
    private $cleared = 0;
    /**
     * @var string имя файла для импорта
     */
    private $file_name = '';
    /**
     * @var resource ресурс открытого файла
     */
    private $file_resource;
    /**
     * @var integer Общее количество добавленных артикулов
     */
    private $inserted = 0;
    /**
     * @var string исходное имя файла, под которым его загрузил пользователь
     */
    private $original_file_name = '';
    /**
     * @var integer количество обработанных строк файла
     */
    private $processed = 0;
    /**
     * @var string url для curl запросов
     */
    private $request_url = '';
    /**
     * @var array имена временных файлов 
     */
    private $temp_files = [];
    /**
     * @var integer количество обновленных артикулов
     */
    private $updated = 0;
    
    /**
     * @return integer
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * Сбрасывает количество в 0 у всех артикулов
     */
    private function clearCounts(): void
    {
        $sql = 'UPDATE `test` SET `COUNT`=0 WHERE `ID` > 0';
        $stmt = $this->getDb()->prepare($sql);
        
        if (! $stmt->execute()) {
            throw new \Exception($stmt->errorInfo(), $stmt->errorCode());
        }
        $this->setCleared($stmt->rowCount());
    }
    
    /**
     * Суммирует данные, полученные от потоков
     * @param string $data строка json с данными, полученными из потока
     * @throws \Exception
     */
    private function collectStatistic(string $data): void
    {
        $data_object = json_decode($data);
        
        if (! isset($data_object->status)) {
            throw new \Exception(ERROR_CURL_TREAD_BAD_ANSWER, 1010);
        } elseif ($data_object->status == 'error') {
            throw new \Exception($data_object->reason, 1011);
        }
        
        $this->setInserted($this->getInserted() + $data_object->inserted);
        $this->setProcessed($this->getProcessed() + $data_object->processed);
        $this->setUpdated($this->getUpdated() + $data_object->updated);
    }
    
    /**
     * Создает временные файлы с частями исходного файла
     */
    private function createPartFiles(): void
    {
        $row = 0;
        $last_closed = TRUE;
        
        while (($buffer = fgets($this->getFileResource(), 4096)) !== FALSE) {
            
            if ($row == 0) {
                // Открываем новый файл
                $temp_resource = fopen($this->getPartFile(), 'w');
                $last_closed = FALSE;
            }
            
            fwrite($temp_resource, $buffer);
            $row++;
            
            if ($row == $this->getConfigParam(static::class . ':PartFileSize')) {
                // Закрываем записанный файл
                fclose($temp_resource);
                $last_closed = TRUE;
                $row = 0;
            }
        }
        // Последний временный файл мог быть заполнен частично
        if (!$last_closed) {
            fclose($temp_resource);
        }
    }
    
    /**
     * Добавляет cURL-запрос с именем временного файла
     * @param resource $multi_handle ресурс мульти запроса
     * @param string $multi_handle имя врменного файла
     */
    private function curlAddFile($multi_handle, string $temp_file): void
    {
        $data = [
            'action' => 'import',
            'import_file' => $temp_file,
            'original_name' => $this->getOriginalFileName(),
        ];
        
        $channel = curl_init();
        curl_setopt($channel, CURLOPT_URL, $this->getRequestUrl());
        curl_setopt($channel, CURLOPT_HEADER, false);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel, CURLOPT_POST, true);
        curl_setopt($channel, CURLOPT_POSTFIELDS, $data);
        // Добавление дескриптора в набор
        curl_multi_add_handle($multi_handle, $channel);
    }
    
    /**
     * Геттер для $this->cleared
     * @return int количество сброшенных в 0 артикулов (по сути изначальное количество строк в таблице)
     */
    private function getCleared(): int
    {
        return $this->cleared;
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
                throw new \Exception(sprintf(ERROR_MODEL_OPEN_FILE, $this->getOriginalFileName()), 1009);
            }
        }
        return $this->file_resource;
    }
    
    /**
     * Геттер для $this->inserted
     * @return int количество добавленных записей
     */
    public function getInserted(): int
    {
        return $this->inserted;
    }
    
    /**
     * Количество отстутсвующих в файле артикулов
     * @return int
     */
    public function getMissing(): int
    {
        return $this->getCleared() - $this->getUpdated();
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
     * Создает временный файл с расширением .csv
     * @return string имя временного файла
     */
    private function getPartFile(): string
    {
        $tmp_file = tempnam(sys_get_temp_dir(), 'kel_');
        $tmp_file_ext = $tmp_file . '.csv';
        rename($tmp_file, $tmp_file_ext);
        $this->pushTempFileName($tmp_file_ext);
        
        return $tmp_file_ext;
    }
    
    /**
     * Геттер для $this->processed
     * @return int количество обработанных строк файла
     */
    public function getProcessed(): int
    {
        return $this->processed;
    }
    
    /**
     * Геттер для $this->request_url
     * @return string
     */
    private function getRequestUrl(): string
    {
        return $this->request_url;
    }
    
    /**
     * Геттер для $this->temp_files
     * @return array список имен временных файлов
     */
    private function getTempFiles(): array
    {
        return $this->temp_files;
    }
    
    /**
     * Распараллеливает процедуру импорта с помощью curl
     */
    public function parallel(): void
    {
        $this->createPartFiles();
        $this->clearCounts();
        // Cоздание набора дескрипторов cURL
        $multi_handle = curl_multi_init();
        
        for ($i = 0; $i < $this->getConfigParam(static::class . ':CurlThreads'); $i++) {
            
            if ($temp_file = $this->popTempFileName()) {
                $this->curlAddFile($multi_handle, $temp_file);
            } else {
                break;
            }
        }
        // Запуск дескрипторов
        $active = null;
        do {
            $multi_code = curl_multi_exec($multi_handle, $active);
        } while ($multi_code == CURLM_CALL_MULTI_PERFORM);
        
        while ($active && $multi_code == CURLM_OK) {
            
            if (curl_multi_select($multi_handle) == -1) {
                usleep(1);
            }
            
            do {
                $multi_code = curl_multi_exec($multi_handle, $active);
            } while ($multi_code == CURLM_CALL_MULTI_PERFORM);
            
            if ($multi_info = curl_multi_info_read($multi_handle)) {
                // Получение информации из мульти-cURL
                $channel_content = curl_multi_getcontent($multi_info['handle']);
                $this->collectStatistic($channel_content);
                // Удаление дескриптора из набора
                curl_multi_remove_handle($multi_handle, $multi_info['handle']);
                curl_close($multi_info['handle']);
                
                if ($temp_file = $this->popTempFileName()) {
                    // Добавление и запуск следующего дескриптора
                    $this->curlAddFile($multi_handle, $temp_file);
                    
                    do {
                        $multi_code = curl_multi_exec($multi_handle, $active);
                    } while ($multi_code == CURLM_CALL_MULTI_PERFORM);
                }
            }
        }
        curl_multi_close($multi_handle);
    }
    
    /**
     * Извлекает элемент из списка временных файлов
     * @return NULL | string имя временного файла
     */
    private function popTempFileName(): ?string
    {
        return array_pop($this->temp_files);
    }
    
    /**
     * Добавляет элемент в список временных файлов
     * @param string $temp_name
     */
    private function pushTempFileName(string $temp_name): void
    {
        array_push($this->temp_files, $temp_name);
    }
    
    /**
     * Сеттер для $this->cleared
     * @param int $value количество обнуленных строк
     */
    private function setCleared(int $value): void
    {
        $this->cleared = $value;
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
     * Сеттер для $this->inserted
     * @param int $value количестов добавленных записей
     */
    private function setInserted(int $value): void
    {
        $this->inserted = $value;
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
     * Сеттер для $this->processed
     * @param int $value количество обработанных строк файла
     */
    private function setProcessed(int $value): void
    {
        $this->processed = $value;
    }
    
    /**
     * Сеттер для $this->request_url получает значение самостоятельно на базе массива $_SERVER
     */
    public function setRequestUrl(): void
    {
        $this->request_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * @param integer $updated количество обновленных записей
     */
    private function setUpdated($updated): void
    {
        $this->updated = $updated;
    }
}
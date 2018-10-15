<?php
namespace App;

use App\Base\BaseController;

/**
 * Контроллер приложения
 * 
 * @author Alexey Petrov
 *
 */
class Controller extends BaseController
{
    /**
     * {@inheritDoc}
     * @see \App\Base\BaseController::index()
     */
    public function index(): array
    {
        // Возврат результата для отображения
        return [];
    }
    
    /**
     * Действие импорта данных
     * @return array
     */
    public function import(): array
    {
        try {
            $this->validateImport();
            // Получение пользовательских данных
            $file_name = $_REQUEST['import_file'];
            $original_name = $_REQUEST['original_name'];
            // Установка данных модели
            $model = new LoadModel($this->getConfig());
            
            $model->setFileName($file_name);
            $model->setOriginalFileName($original_name);
            // Импорт данных
            $model->import_file();
            $result = [
                'status' => 'success',
                'inserted' => $model->getInserted(),
                'updated' => $model->getUpdated(),
                'processed' => $model->getRowCount(),
            ];
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'reason' => $e->getMessage(),
            ];
        }
        // Возврат результата для отображения
        return $result;
    }
    
    /**
     * Действие многопоточного импорта данных
     * @return array
     */
    public function parallel_import(): array
    {
        try {
            $this->validateParallelImport();
            // Получение пользовательских данных
            $file_name = $_FILES['import_data']['tmp_name'];
            $original_name = $_FILES['import_data']['name'];
            // Установка данных модели
            $model = new CurlModel($this->getConfig());
            
            $model->setFileName($file_name);
            $model->setOriginalFileName($original_name);
            $model->setRequestUrl();
            $model->parallel();
            
            
            $result = [
                'status' => 'success',
                'inserted' => $model->getInserted(),
                'updated' => $model->getUpdated(),
                'missing' => $model->getMissing(),
                'processed' => $model->getProcessed(),
            ];
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'reason' => $e->getMessage(),
            ];
        }
        // Возврат результата для отображения
        return $result;
    }
    
    /**
     * Валидация входных данных
     * @throws \Exception
     */
    private function validateImport(): void
    {
        // Файл не был передан
        if (!isset($_REQUEST['import_file'])) {
            throw new \Exception(ERROR_CONTROLLER_IMPORT_VALIDATION_NO_FILE, 1007);
        }
        // Ошибка загрузки файла
        if (!isset($_REQUEST['original_name'])) {
            throw new \Exception(ERROR_CONTROLLER_IMPORT_VALIDATION_NO_ORIG_FILE, 1008);
        }
    }
    
    /**
     * Валидация входных данных
     * @throws \Exception
     */
    private function validateParallelImport(): void
    {
        // Файл не был передан
        if (!isset($_FILES['import_data'])) {
            throw new \Exception(ERROR_CONTROLLER_PARALLEL_VALIDATION_NO_FILE, 1004);
        }
        // Ошибка загрузки файла
        if (isset($_FILES['import_data']['error']) && ($_FILES['import_data']['error'] > 0)) {
            throw new \Exception(ERROR_CONTROLLER_PARALLEL_VALIDATION_FILE_UPLOAD, 1005);
        }
    }
}
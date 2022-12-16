<?php
    namespace App\Controllers;

    use App\Exceptions\CustomException;
    use App\Helpers\Helpers;

    class FilesController extends AbstractController
    {
        //копирование файлов в отдельную папку, перед удалением (на случай восстановления)
        public static function copyFilesBeforeDelete(array $files, string $source, $dest):array
        {
            if(!empty($files)){
                foreach($files as $key => $file){
                    if(file_exists($source.$file) && is_file($source.$file)){
                        if(file_exists($dest.$file)){
                            $file = self::$time.'_'.$file;
                            $files[$key] = $file;
                        }
                        if(copy($source.$file, $dest.$file)){
                            unlink($source.$file);
                        }
                    }
                }
            }
            return $files;
        }

        //сравнение двух списков файлов и удаление отсутствующих файлов
        public static function compareFileLists(array $currentFiles, array $existFiles):array
        {
            $missingFiles = array_diff($existFiles, $currentFiles);
            if(!empty($missingFiles)){
                self::copyFilesBeforeDelete($missingFiles, IMGPATH, DELETEIMGPATH);
            }
            return $currentFiles;
        }

      
        //загрузка файлов
        public function uploadFiles():void
        {
            Helpers::checkIfAdmin();
            $files = array();
            foreach($_FILES as $file)
            {
                if($file['size'] < 10000000){
                    $valid_formats = array("jpg", "png", "gif", "bmp","jpeg");
                    $name = strtolower($file['name']);
                    $ext = substr($name, strrpos($name, ".")+1);
                    $fn = uniqid().'.'.$ext;
                    $uploadfile = TMPIMGPATH . $fn;
                    if(in_array($ext, $valid_formats)){
                        if(move_uploaded_file($file['tmp_name'], $uploadfile)) {
                            $files[] = $fn;
                        }
                    }
                }
            }
            if(empty($files)){
                throw new CustomException('SAVE_FILE_ERROR');
            }
            $files = implode(";", $files);
            echo json_encode(["files" => $files]);
            exit;
        }

        //удаление файлов из временной папки
        public function deleteTmpFile():void
        {
            Helpers::checkIfAdmin();
            $params = $this->request->getUrl()->getParams();
            if(!isset($params['deleteFile'])){
                throw new CustomException('WRONG_DATA');
            }

            $file = Helpers::sanitize($params['deleteFile']);

            if(!file_exists(TMPIMGPATH.$file)){
                throw new CustomException('FILE_DOESNT_EXIST');
            }
            unlink(TMPIMGPATH.$file);
            echo json_encode(['answer' => 'FILE_DELETED']);
            exit;
        }
    }
?>
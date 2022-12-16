<?php
    namespace App\Exceptions;

    class CustomException extends \Exception
    {
        
        CONST WRONG_DATA = 'Указаны не все данные';
        CONST WRONG_EMAIL = 'Указан некорректный адрес электронной почты';
        CONST WRONG_PASSWORD = 'Неверный пароль';

        CONST USER_DOESNT_EXIST = 'Пользователь не существует';
        CONST USER_ALLREADY_EXIST = 'Пользователь с таким адресом электронной почты уже существует';
        CONST USER_ERROR = 'Пользователь не существует или защищён от записи';

        CONST HASH_DOESNT_EXIST = 'Запрос на смену пароля не существует';
        CONST HASH_ALLREADY_ACTIVATED = 'Запрос на смену пароля уже выполнен';
        CONST HASH_ALLREADY_EXPIRED = 'Время действия ссылки закончилось. Повторите запрос на смену пароля';
        CONST HASH_IS_NOT_EQUAL = 'Хэш не совпадает. Пароль не будет изменён';

        CONST AUTH_ERROR = 'Ошибка авторизации';

        CONST TOKEN_EXPIRED = 'Время действия токена истекло';
        CONST TOKEN_INVALID = 'Неверный токен';
        CONST NO_TOKEN = 'Требуется токен для авторизации';

        CONST ITEM_DOESNT_EXIST = 'Товар не найден';
        CONST ITEM_ALLREADY_EXIST = 'Товар с таким артикулом, брэндом и размером уже существует';

        CONST SAVE_FILE_ERROR = 'Ошибка при загрузке файла';
        CONST FILE_DOESNT_EXIST = 'Ошибка при удалении файла. Файл не найден';
        
        CONST NO_ACTIVE_SUPPLY = 'Нет активной поставки';
        CONST SUPPLY_DOESNT_EXIST = 'Поставка не существует';

        CONST PICKLIST_DOESNT_EXIST = 'Лист подбора не существует';

        protected $error;
        protected $errorText;

        public function __construct(string $error, $code = 0, $option = 'Неизвестная ошибка')
        {
            $this->error = $error;
            $errorText = (new \ReflectionClass(__CLASS__))->getConstants();
            $this->errorText = $errorText[$error] ?? $option;
            parent::__construct($this->error, $code);
        }

        public function output():array
        {
            return array("error" => $this->error, "errorText" => $this->errorText);
        }
    }
?>
<?php
    namespace App\Controllers;

use App\Helpers\Helpers;
use App\Middlewares\Authenticate;

    use App\Models\UserModel;

    class UserController extends AbstractController
    {
        private array $params;
        public function __construct()
        {
            $this->params = array();
            parent::__construct();
        }

        //вывод списка пользователей
        public function getUsersList():void
        {
            Helpers::checkIfAdmin();
            echo json_encode(['usersList' => (new UserModel($this->params))->getList()]);
            exit;
        }

        //сохранение при редактировании пользователя
        public function saveUser():void
        {
            Helpers::checkIfAdmin();

            $this->params['id']       = Helpers::sanitize($this->request->id);
            $this->params['email']    = Helpers::sanitize($this->request->email);
            $this->params['role']     = Helpers::sanitize($this->request->role);
            $this->params['lastName'] = Helpers::sanitize($this->request->lastName);
            $this->params['name']     = Helpers::sanitize($this->request->name);
            $this->params['sureName'] = Helpers::sanitize($this->request->sureName);
            $this->params['banned']   = Helpers::sanitize($this->request->banned);

            $user = new UserModel($this->params);
            $user->save();

            echo json_encode(['answer' => 'USER_SAVE_SUCCESS']);
            exit;
        }

        //удаление пользователя
        public function deleteUser(int $id):void
        {
            Helpers::checkIfAdmin();
            $this->params['id'] = $id;

            $user = new UserModel($this->params);
            $user->delete();
            echo json_encode(['answer' => 'DELETE_SUCCESS']);
            exit;
        }
    }


?>
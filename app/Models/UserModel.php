<?php
    namespace App\Models;

    use App\Controllers\AbstractController;
    use App\Controllers\MailController;

    use App\Exceptions\CustomException;
    use App\Structures\User;


    class UserModel extends AbstractController{
        private $user;

        public function __construct($userParams)
        {
            $this->user = User::fromArray($userParams);
            parent::__construct();
        }

        //проверка логина и пароля
        public function loginChecking(string $password):void
        {
            $this->loadInfo();
            if(hash("sha256", $password . $this->user->getSalt()) != $this->user->getHash()){
                throw new CustomException('WRONG_PASSWORD');
            }
        }

        //регистрация пользователя
        public function registration():void
        {
            if(!filter_var($this->user->email, FILTER_VALIDATE_EMAIL) || !$this->user->getPassword()){
                throw new CustomException('WRONG_DATA');
            }

            if($this->isExists()){
                throw new CustomException('USER_ALLREADY_EXIST');
            }

            $this->user->fio = $this->shortFio();
            $salt = $this->salt();
            $hash = hash("sha256", $this->user->getPassword() . $salt);

            $stmt = self::$db->prepare("INSERT INTO `users` (`email`, `fio`, `lastname`, `name`, `surename`, `hash`, `salt`, `regTime`, `regIp`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssis", $this->user->email, $this->user->fio, $this->user->lastName, $this->user->name, $this->user->sureName, $hash, $salt, self::$time , self::$userIp);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
            
            $this->loadInfo();
            $this->sendMail('registration');
        }

        
        //сохранение пользователя
        public function save():void
        {
            if(!filter_var($this->user->email, FILTER_VALIDATE_EMAIL) || !$this->user->id){
                throw new CustomException('WRONG_DATA');
            }

            if(!$this->isExists()){
                throw new CustomException('USER_DOESNT_EXIST');
            }

            $this->user->fio = $this->shortFio();
            $stmt = self::$db->prepare("UPDATE `users` SET `email`=?, `fio`=?, `lastname`=?, `name`=?, `surename`=?, `role`=?, `banned`=? WHERE id=? AND `protected`<>'1'");
            $stmt->bind_param("ssssssii", $this->user->email, $this->user->fio, $this->user->lastName, $this->user->name, $this->user->sureName, $this->user->role, $this->user->banned, $this->user->id);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
        }

        //удаление пользователя
        public function delete():void
        {
            $this->loadInfo();
            if($this->user->protected){
                throw new CustomException('USER_ERROR');
            }
            $stmt = self::$db->prepare("UPDATE `users` SET `deleted`='1' WHERE id=? AND protected<>'1'");
            $stmt->bind_param("i", $this->user->id);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
        }

        //запрос на смену пароля
        public function changePassRequest():void
        {
            $this->loadInfo();

            $expires = self::$time + 60*60;
            $hash = md5($this->user->id.$this->user->email.SECRETWORD.$expires);

            $stmt = self::$db->prepare("INSERT INTO `users_pass_recovery` (`user_email`, `user_id`, `hash`, `expires`, `request_ip`, `request_date`)  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisiss", $this->user->email, $this->user->id, $hash, $expires, self::$userIp, date("Y-m-d H:i:s"));
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
        
            $url = SITENAME."/recovery?recoveryId=".self::$db->insert_id."&recoveryEmail={$this->user->email}&recoveryHash=$hash";
            $this->sendMail('changePassRequest', $url);
        }

        //смена пароля
        public function changePass(array $changeParams):void
        {
            $this->loadInfo();

            $result = self::$db->query("SELECT * FROM `users_pass_recovery` WHERE `id`='$changeParams[recoveryId]' AND `user_email`='{$this->user->email}' AND `hash`='$changeParams[hash]'");
            $result = $result->fetch_assoc();
            if(empty($result)){
                throw new CustomException('HASH_DOESNT_EXIST');
            }

            if($result['active']){
                throw new CustomException('HASH_ALLREADY_ACTIVATED');
            }

            if(self::$time > $result['expires']){
                throw new CustomException('HASH_ALLREADY_EXPIRED');
            }

            if(md5($this->user->id.$this->user->email.SECRETWORD.$result['expires']) != $changeParams['hash']){
                throw new CustomException('HASH_IS_NOT_EQUAL');
            }

            $salt = $this->salt();
            $newHash = hash("sha256", $changeParams['password'] . $salt);


            $stmt = self::$db->prepare("UPDATE `users` SET `hash`=?, `salt`=? WHERE id=?");
            $stmt->bind_param("ssi", $newHash, $salt, $this->user->id);
            $stmt->execute();

            $stmt = self::$db->prepare("UPDATE `users_pass_recovery` SET `active`='1' WHERE id=?");
            $stmt->bind_param("i", $result['id']);
            $stmt->execute();

            $this->sendMail('changePassSuccess', $changeParams['password']);
        }

        //вывод фио пользователя
        public function getFio():string
        {
            $this->loadInfo();
            return $this->user->fio;
        }

        //проверка на существование пользователя в БД
        public function isExists():int
        {
            $result = self::$db->query("SELECT `id` FROM `users` WHERE (`id`='{$this->user->id}' OR  `email`='{$this->user->email}' ) AND `deleted`='0'");
            return $result->num_rows;
        }

        //проверка ролей
        public function isAdmin():bool
        {
            $this->loadInfo();
            if($this->user->role == 'admin'){
                return true;
            }
            return false;
        }

        public function isOperator():bool
        {
            $this->loadInfo();
            if($this->user->role == 'admin' || $this->user->role == 'operator'){
                return true;
            }
            return false;
        }

         //загрузка данных о пользователе
         public function loadInfo():void
         {
             if(!$this->user->email && !$this->user->id){
                 throw new CustomException('WRONG_DATA');
             }

             $result = self::$db->query("SELECT * FROM `users` WHERE (`id`='{$this->user->id}' OR `email`='{$this->user->email}') AND `deleted`='0' AND `banned`='0'");
             $result = $result->fetch_assoc();
             if(empty($result)){
                 throw new CustomException('USER_DOESNT_EXIST');
             }
             $this->user = User::fromArray($result);
         }
 

        //загрузка списка пользователей
        public function getList():array
        {
            $users = array();
            $result = self::$db->query("SELECT * FROM `users` WHERE `deleted`='0' ORDER BY id ASC");
            while ($user = $result->fetch_assoc()){
                $user['regTime'] = date("d.m.Y H:i", $user['regTime']);
                $users[] = User::fromArray($user);
            }
            return $users;
        }


        //вывод свойств пользователя в массиве
        public function toArray():array
        {
            return $this->user->toArray();
        }

        //вычисление соли
        private function salt():string
        {
            $salt = md5(uniqid(rand(), true));
            return substr($salt, 0, 10);
        }
        
        //формирование короткой записи ФИО
        private function shortFio():string
        {
            $fio = $this->user->lastName;
            if($this->user->name){
                $fio .= " " . mb_strtoupper(mb_substr($this->user->name, 0, 1, 'utf-8')).".";
                if($this->user->sureName){
                    $fio .= " " . mb_strtoupper(mb_substr($this->user->sureName, 0, 1, 'utf-8')).".";
                }
            }
            return $fio;
        }

        //отправка писем
        private function sendMail(string $type, string $option = ''):void
        {
            $msg = '';
            $subject = '';
            switch($type){
                case 'registration':
                    $subject = SITENAME.', регистрация на сайте';
                    $msg = '<div style="padding:20px; font-family:Tahoma, Arial; color:#333; font-size:14px;">';
                    $msg .= '<p style="font-size:18px; margin:0 0 20px 0;">Благодарим за регистрацию на сайте <a href="'.SITENAME.'" style="color:#0d6efd;">'.SITENAME.'</a></p>';
                    $msg .= '<p style="margin:0 0 10px 0;">При регистрации вами была указана следующая информация:</p>';
                    $msg .= '<p style="margin:0 0 10px 0;">Адрес электронной почты: <b><a href="mailto:'.$this->user->email.'" style="color:#0d6efd;">'.$this->user->email.'</a></b></p>';
                    $msg .= '<p style="margin:0 0 10px 0;">Имя пользователя: <b>'.$this->user->fio.'</b></p>';
                    $msg .= '<p style="margin:0 0 10px 0;">Пароль: <b>'.$this->user->getPassword().'</b></p>';
                    $msg .= '<p style="margin:0 0 20px 0;">Для входа в личный кабинет, воспользуйтесь <a href="'.SITENAME.'" style="color:#0d6efd;">формой</a> на сайте.</p>';
                    $msg .= '</div>';
                break;
                case 'changePassRequest':
                    $subject = SITENAME.', сброс пароля';
                    $msg = '<div style="padding:20px; font-family:Tahoma, Arial; color:#333; font-size:14px;">';
                        $msg .= '<p style="font-size:18px; margin:0 0 20px 0;">Сброс пароля на сайте '.SITENAME.'</a></p>';
                        $msg .= '<p style="margin:0 0 10px 0;">Для сброса пароля перейдите по ссылке: <b><a href="'.$option.'" style="color:#0d6efd;">'.$option.'</a></b></p></p>';
                    $msg .= '</div>';
                break;
                case 'changePassSuccess':
                    $subject = SITENAME.', пароль изменён';
                    $msg = '<div style="padding:20px; font-family:Tahoma, Arial; color:#333; font-size:14px;">';
                        $msg .= '<p style="margin:0 0 10px 0;">Ваш пароль на сайте: <b><a href="'.SITENAME.'" style="color:#0d6efd;">'.SITENAME.'</a></b> был успешно изменён.</p>';
                        $msg .= '<p style="margin:0 0 10px 0;">Новый пароль: <b>'.$option.'</b></p>';
                    $msg .= '</div>';
                break;
            }
            new MailController($this->user->email, $subject, $msg);
        }
    }
?>
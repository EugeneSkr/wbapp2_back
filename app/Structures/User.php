<?php
    namespace App\Structures;

    use Spatie\DataTransferObject\DataTransferObject;

    class User extends DataTransferObject
    {
        public int $id;
        public string $email;
        public string $fio;
        public string $role;
        public string $lastName;
        public string $name;
        public string $sureName;
        public string $regTime;
        public string $regIp;
        public bool $banned;
        public bool $protected;

        private string $password;
        private string $hash;
        private string $salt;

        public static function fromArray(array $params):self
        {
            $user = [
                'id' => (int) $params['id'] ?? 0,
                'email' => $params['email'] ?? '',
                'fio' => $params['fio'] ?? '',
                'role' => $params['role'] ?? '',
                'lastName' => $params['lastName'] ?? '',
                'name' => $params['name'] ?? '',
                'sureName' => $params['sureName'] ?? '',
                'regTime' =>  $params['regTime'] ?? '',
                'regIp' => $params['regIp'] ?? '',
                'banned' => (bool) $params['banned'] ?? false,
                'protected' => (bool) $params['protected'] ?? false
            ];

            return (new self($user))->setPassword($params['password'] ?? '')->setHash($params['hash'] ?? '')->setSalt($params['salt'] ?? '');
        }

        //сеттеры для приватных свойств
        public function setPassword(string $password):self
        {
            $this->password = $password;
            return $this;
        }


        public function setHash(string $hash):self
        {
            $this->hash = $hash;
            return $this;
        }

        public function setSalt(string $salt):self
        {
            $this->salt = $salt;
            return $this;
        }

        //геттеры для приватных свойств
        public function getPassword():string
        {
            return $this->password;
        }

        public function getHash():string
        {
            return $this->hash;
        }

        public function getSalt():string
        {
            return $this->salt;
        }
    }
?>
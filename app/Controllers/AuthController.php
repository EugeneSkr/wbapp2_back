<?php
    namespace App\Controllers;

    use DateTimeImmutable;
    
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    use Firebase\JWT\ExpiredException;
    use Firebase\JWT\SignatureInvalidException;

    use App\Models\UserModel;
    use App\Exceptions\CustomException;
    use App\Helpers\Helpers;


    class AuthController extends AbstractController
    {
        private array $params;
        public function __construct()
        {
            $this->params = array();
            parent::__construct();
        }

        //проверка авторизации пользователя
        public function signin():void
        {
            $this->params['email']    = Helpers::sanitize($this->request->email);
            $this->params['password'] = Helpers::sanitize($this->request->password);

            $user = new UserModel($this->params);
            $user->loginChecking($this->params['password']);

            echo json_encode(['user' =>  $user->toArray(), 'token' => $this->createJwt($user->toArray())]);
            exit;
        }

        //регистрация пользователя
        public function signup():void
        {
            $this->params['email']    = Helpers::sanitize($this->request->email);
            $this->params['password'] = Helpers::sanitize($this->request->password);
            $this->params['lastName'] = Helpers::sanitize($this->request->lastName);
            $this->params['name']     = Helpers::sanitize($this->request->name);
            $this->params['sureName'] = Helpers::sanitize($this->request->sureName);

            $user = new UserModel($this->params);
            $user->registration();
            echo json_encode(['user' => $user->toArray(), 'token' => $this->createJwt($user->toArray())]);
            exit;
        }

        //обновление данных о пользователе по токену
        public function refresh():void
        {
            $token = Helpers::sanitize($this->request->refreshToken);
            try{
                $decoded = JWT::decode($token, new Key(SECRETWORD, 'HS256'));
                $decoded = (array)$decoded;
                $this->params['email'] = Helpers::sanitize($decoded['email']);
            }
            catch (ExpiredException $e){
                list($header, $payload, $signature) = explode(".", $token);
                $payload = json_decode(base64_decode($payload));
                $payload = (array)$payload;
                $this->params['email'] = Helpers::sanitize($payload['email']);
            }
            catch(SignatureInvalidException $e){
                throw new CustomException('TOKEN_INVALID', 403);
            }

            $user = new UserModel($this->params);
            $user->loadInfo();
            echo json_encode(['user' => $user->toArray(), 'token' => $this->createJwt($user->toArray())]);
            exit;
        }

        //запрос на смену пароля
        public function recovery():void
        {
            $this->params['email'] = Helpers::sanitize($this->request->recoveryPasswordEmail);
            $user = new UserModel($this->params);
            $user->changePassRequest();
            echo json_encode(['answer' => 'RECOVERY_SUCCESS']);
            exit;
        }

        //смена пароля
        public function changePass():void
        {
            $this->params['email']  = Helpers::sanitize($this->request->userEmail);

            $changeParams = array();
            $changeParams['recoveryId'] = Helpers::sanitize($this->request->recoveryId);
            $changeParams['password']   = Helpers::sanitize($this->request->newpass);
            $changeParams['hash']       = Helpers::sanitize($this->request->hash);

            $user = new UserModel($this->params);
            $user->changePass($changeParams);
            echo json_encode(['answer' => 'CHANGE_PASSWORD_SUCCESS']);
            exit;
         }

        //создание Javascript Web Token
        private function createJwt(array $user):string
        {
            $jTime = new DateTimeImmutable();
            $jExpired = $jTime->modify('+1 day')->getTimestamp();
            $data = [
                'iat'   => $jTime,           
                'iss'   => SITENAME,         
                'nbf'   => $jTime,           
                'exp'   => $jExpired,
                'email' => $user['email'] ?? '',
                'id'    => $user['id'] ?? '',
                'role'  => $user['role'] ?? ''
            ];
            return JWT::encode($data, SECRETWORD, 'HS256');
        }
    }

?>
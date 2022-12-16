<?php

    namespace App\Middlewares;

    use Pecee\Http\Middleware\IMiddleware;
    use Pecee\Http\Request;

    use App\Exceptions\CustomException;
    
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    use Firebase\JWT\ExpiredException;
    use Firebase\JWT\SignatureInvalidException;

    class Authenticate implements IMiddleware
    {
        public static $userEmail = '';
        public static $userId = 0;
        //обработка JWT из заголовка и получение адреса почты и id пользователя
        public function handle(Request $request):void
        {
            if(isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']){
                $token = $_SERVER['HTTP_AUTHORIZATION'];
                $token = str_replace('Bearer ', '', $token);
                try{
                    $decoded = JWT::decode($token, new Key(SECRETWORD, 'HS256'));
                    $decoded = (array)$decoded;

                    self::$userEmail = $decoded['email'];
                    self::$userId    = $decoded['id'];
                }
                catch (ExpiredException $e){
                    throw new CustomException('TOKEN_EXPIRED', 401);
                }
                catch(SignatureInvalidException $e){
                    throw new CustomException('TOKEN_INVALID', 403);
                }
            }
            else{
                throw new CustomException('NO_TOKEN', 403);
            }
        }
    }
?>
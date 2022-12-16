<?php
    namespace App\Helpers;

    use App\Controllers\AbstractController;
    use App\Exceptions\CustomException;
    use App\Models\UserModel;
    use App\Middlewares\Authenticate;

    class Helpers extends AbstractController
    {

        public function __construct()
        {
            parent::__construct();
        }

        //обработка входящих данных
        public static function sanitize($str):string
        {
            return trim(htmlspecialchars(strip_tags(mysqli_real_escape_string(self::$db, $str)), ENT_QUOTES, "utf-8"));
        }

        //проверка ролей пользователя
        public static function checkIfAdmin():void
        {
            $user = new UserModel(['email' => Authenticate::$userEmail]);
            if(!$user->isAdmin()){
                throw new CustomException('AUTH_ERROR', 401);
            }
        }

        public static function checkIfOperator():void
        {
            $user = new UserModel(['email' => Authenticate::$userEmail]);
            if(!$user->isOperator()){
                throw new CustomException('AUTH_ERROR', 401);
            }
        }

        //обрезка наименования товара
        public static function shrinkTitle(string $title):string
        {
            if(strlen($title) > 200){
                return  mb_substr($title, 0, 50, 'utf-8')."...";
            }
            return $title;
        }

        //основное фото товара
        public static function mainPhoto(string $photo):string
        {
            if($photo){
                $photo = explode(";", $photo);
                $photo = $photo[0];
            }
            return $photo;
        }

        //вывод количества строк по запросу
        public static function getCount(string $query):int
        {
            $result = self::$db->query($query);
            $result = $result->fetch_assoc();
            return $result['count'] ?? 0;
        }

         //загрузка гет параметров
         public function getParams(array $sourceArray):array
         {
             $params = $this->request->getUrl()->getParams();
             if(!empty($params) && !empty($sourceArray)){
                 foreach($params as $paramKey => $param){
                     foreach($sourceArray as $sourceKey => $sourceValue){
                         if($paramKey == $sourceKey){
                             $sourceArray[$sourceKey] = self::sanitize($param);
                             break;
                         }
                     }
                 }
             }
             return $sourceArray;
         }
    }
?>
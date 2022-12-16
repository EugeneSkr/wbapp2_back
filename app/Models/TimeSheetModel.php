<?php
    namespace App\Models;

    use App\Controllers\AbstractController;
use App\Helpers\Helpers;

    class TimeSheetModel extends AbstractController
    {
        private $month;
        private $year;

        public function __construct(int $month,int $year)
        {
            parent::__construct();
            $this->month = $month;
            $this->year  = $year;
        }

        //формирование табеля закрытых и открых поставок по пользователям за месяц
        public function getTimeSheet():array
        {
            $startDate = mktime(0, 0, 0, $this->month, 1, $this->year);
            $endDate = mktime(0, 0, 0, $this->month + 1, 1, $this->year);

            $users = array();
            //выбираем пользователей, оформлявших заказы в указанном месяце
            $result = self::$db->query("SELECT DISTINCT(`user_id`) id, us.fio FROM `orders` as ord, `users` as us WHERE us.id=ord.user_id AND  ord.`touched_time` >= $startDate AND ord.`touched_time` <= $endDate AND `status`>0 ORDER BY us.fio ASC");
            while($user = $result->fetch_assoc()){
                $users[] = array("id" => $user['id'], "fio" => "$user[fio]", "dates" => array(), "calendarStr" => "", "openedSupplies" => 0, "closedSupplies" => 0, "totalOrdersCount" => 0);
            }

            if(empty($users)){
                return [];
            }

            foreach($users as $key => $user){
                $dates = array();

                //подсчёт открытых и закрытых поставок
                $users[$key]['openedSupplies'] = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `supplies` WHERE `open_user_id`='$user[id]'  AND `open_time` >= $startDate AND `open_time` <= $endDate");
                $users[$key]['closedSupplies'] = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `supplies` WHERE `close_user_id`='$user[id]'  AND `close_time` >= $startDate AND `close_time` <= $endDate");

                //выбираем листы подбора сформированные оператором за день 
                $result = self::$db->query("SELECT * FROM `pick_lists` WHERE `user_id`='$user[id]' AND `time` >= $startDate AND `time` <= $endDate ORDER BY `time` ASC");
                while($picklist = $result->fetch_assoc()){
                    $day = date("Y-n-j", $picklist['time']);

                    //создаем массив данных за этот день
                    if(!isset($dates[$day])){
                        $dates[$day]['count'] = 0;
                        $dates[$day]['supplies'] = array();
                      }

                    //подсчитываем количество заказов по листу подбора и заносим в массив
                    $countOrders = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `pick_lists_strings` WHERE `pickListId`='$picklist[id]' AND `userId`='$user[id]'");
                    if(!($countOrders)){
                        continue;
                    }
                    $dates[$day]['count'] += $countOrders;
                    $users[$key]['totalOrdersCount'] += $countOrders;

                    //проверяем поставку по листу подбора, если поставка открыта и закрыта пользователем, то записываем время создания и закрытия
                    $subResult = self::$db->query("SELECT pls.`supplyId`, sup.`status`, sup.`open_time`, sup.`open_user_id`, sup.`close_time`, sup.`close_user_id`
                                                    FROM `pick_lists_strings` as pls, `supplies` as sup WHERE pls.`pickListId`='$picklist[id]' AND pls.`userId`='$user[id]' AND sup.`supplyId`=pls.`supplyId` LIMIT 0, 1");
                    $supply = $subResult->fetch_assoc();
                    if(empty($supply)){
                        continue;
                    }
                    $exists = 0;
                    if(!empty($dates[$day]['supplies'])){
                        foreach($dates[$day]['supplies'] as $supplyId){
                            if($supplyId == $supply['supplyId']){
                                $exists = 1;
                                break;
                            }
                        }
                    }
                    if(!$exists){
                        $dates[$day]['supplies'][] = $supply['supplyId'];
                    }
                }
                $users[$key]['dates'] =  $dates;
            }

            return array("usersTimesheet" => $users);
        }
    }
?>
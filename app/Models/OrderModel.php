<?php
    namespace App\Models;

    use App\Controllers\AbstractController;
    use App\Controllers\WBRequestController;
    use App\Exceptions\CustomException;
    use App\Models\UserModel;
    use App\Middlewares\Authenticate;
    use App\Helpers\Pagination;
    use App\Helpers\Helpers;
    use App\Structures\Item;
    use App\Structures\Order;

    class OrderModel extends AbstractController
    {
        private string $activeSupplyId;
        private int $totalCountInSuplly;

        public function __construct()
        {
            parent::__construct();

            $this->activeSupplyId = '';
            $this->totalCountInSuplly = 0;
            $this->getActiveSupply();
        }

        //загрузка новых заказов из БД
        public function getNewOrders($params):array
        {
            if($params['checkNewOrders']){
                $this->getNewOrdersFromWB();
            }
           
            $search = '';
            if($params['search']){
                $search = $params['search'];
                $costsSearch = $search;
                if(is_numeric($search)){
                    $costsSearch = $search * 100;
                }
                $search = "AND (ord.`orderId` LIKE '%$search%' OR ord.`convertedPrice` LIKE '%$costsSearch%' OR it.`type` LIKE '%$search%' OR it.`articul` LIKE '%$search%' OR it.`brand` LIKE '%$search%' OR it.`size` LIKE '%$search%' OR it.`title` LIKE '%$search%')";
            }

            
            $params['totalCount'] = Helpers::getCount("SELECT COUNT(ord.`id`) as `count` FROM `orders` as ord, `items` as it WHERE it.`chrtId`=ord.`chrtId` AND it.`deleted`='0' AND `ord`.`status`='0' $search");
            $pagination = new Pagination($params);
            
            $sort = $this->switchSort($params['sort'], $params['order']);
           
            //загрузка заказов
            $orders = array();
            $result = self::$db->query("SELECT it.`id` as `itemId`, it.`articul`, it.`articulWB`, it.`title`, it.`brand`, it.`type`, it.`size`, it.`barcode`, `it`.`chrtId`, it.`photoList`, ord.id as orderInnerId, ord.`orderId`, ord.`orderUID`, ord.`dateCreated`, ord.`convertedPrice`, ord.`officeAddress`, ord.`scOfficesNames`, ord.`storeId`, ord.`status`, ord.`userStatus`, ord.`wbWhId`, ord.`touched_time`, ord.`time`, ord.`deliveryAddress`
                                        FROM `orders` as ord, `items` as it
                                        WHERE it.`chrtId`=ord.`chrtId` AND it.`deleted`='0' AND `ord`.`status`='0' $search ORDER BY $sort LIMIT $pagination->start, $pagination->onPage");
            while($order = $result->fetch_assoc()){
                $order['shortTitle'] = Helpers::shrinkTitle($order['title']);
                $order['photo'] = Helpers::mainPhoto($order['photoList']);
                $order['photoList'] = explode(';', $order['photoList']);
                $order['item'] = Item::fromArray($order)->toArray();
    
                $order['outputTimeInterval'] = $this->outputTimeInterval($order['dateCreated']);
                $order['dateCreated'] = date("H:i d.m.Y", $order['dateCreated']);

                if($order['scOfficesNames']){
                    $order['officeAddress'] = $order['officeAddress'] ? "$order[scOfficesNames]<br />$order[officeAddress]" : $order['scOfficesNames'];
                }

                $orders[] = Order::fromArray($order)->toArray();
            }

            return array('answer' => 'ORDERS_LIST', "orders" => $orders, "totalPages" => $pagination->totalPages, "totalCount" => $pagination->totalCount, "currentSupllyId" => $this->activeSupplyId, "totalCountInSuplly" => $this->totalCountInSuplly);
        
        }

        //загрузка собранных заказов по листам подбора
        public function getOrdersInSupply():array
        {
            if(!$this->activeSupplyId){
                throw new CustomException('NO_ACTIVE_SUPPLY');
            }
            $totalCount = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `orders` WHERE `status`='0'");

            //листы подбора в поставке
            $pickLists = array();
            $pickListsCount = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `pick_lists` WHERE `supplyId`='$this->activeSupplyId'");
            
            $result = self::$db->query("SELECT `id`, `time`, `user_id` FROM `pick_lists` WHERE `supplyId`='$this->activeSupplyId' ORDER BY id DESC");
            while($pickList = $result->fetch_assoc()){
                $date = date("H:i d.m.Y", $pickList['time']);
                $orders = array();

                $subResult = self::$db->query("SELECT DISTINCT(pls.`orderId`), pls.`stickerPartA`, pls.`stickerPartB`, it.id as itemId, it.`photoList`, it.brand, it.title, it.size, it.articul
                                            FROM `pick_lists_strings` as pls, items as it
                                            WHERE pls.`pickListId`=$pickList[id] AND it.`chrtId`=pls.`chrtId` ORDER BY pls.id ASC");
                while($order = $subResult->fetch_assoc()){
                    $order['shortTitle'] = Helpers::shrinkTitle($order['title']);
                    $order['photo'] = Helpers::mainPhoto($order['photoList']);
                    $order['photoList'] = explode(';', $order['photoList']);
                    $order['item'] = Item::fromArray($order)->toArray();
                    $orders[] = Order::fromArray($order)->toArray();
                }
                $pickLists[] = array("id" => $pickList['id'], "number" => $pickListsCount, "date" => "$date", "orders" => $orders);
                $pickListsCount--;
            }
            return array('answer' => 'PICK_LISTS', "pickLists" => $pickLists, "totalPages" => 0, "totalCount" => $totalCount, "currentSupllyId" => $this->activeSupplyId, "totalCountInSuplly" => $this->totalCountInSuplly);
        }

        //создание новой поставки
        public function createSupply():array
        {
            $url = "supplies";
            $headers = array("accept: application/json");
            $request  = (new WBRequestController($url, $headers, true))->sendRequest();
            
            //уже есть активная поставка
            if(isset($request['error']) && isset($request['errorText'])){
                throw new CustomException('SUPPLY_ERROR', 0, $request['errorText']);
            }

            //запрос пдф файла со штрихкодом поставки
            $url = "supplies/$request[supplyId]/barcode?type=svg";
            $subRequest  = (new WBRequestController($url, $headers))->sendRequest();
            $pdfString = isset($subRequest['file']) ? $subRequest['file'] : '';
            
            $supplyStatus = 1;
            $stmt = self::$db->prepare("INSERT INTO `supplies` (`supplyId`, `status`, `open_time`, `open_user_id`, `pdfString`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siiis", $request['supplyId'], $supplyStatus, self::$time, Authenticate::$userId , $pdfString);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
            return array("supplyId" => $request['supplyId']);
        }

        //закрытие поставки
        public function closeSupply():array
        {
            if(!$this->activeSupplyId){
                throw new CustomException('NO_ACTIVE_SUPPLY');
            }

            $url = "supplies/$this->activeSupplyId/close";
            $headers = array("accept: */*");
            $request  = (new WBRequestController($url, $headers, true))->sendRequest();

            if(isset($request['error']) && isset($request['errorText'])){
                throw new CustomException('SUPPLY_ERROR', 0, $request['errorText']);
            }

            $stmt = self::$db->prepare("UPDATE `supplies` SET `status`='2', `close_time`=?, `close_user_id`=? WHERE `supplyId`=?");
            $stmt->bind_param("iis", self::$time, Authenticate::$userId, $this->activeSupplyId);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
            return array('answer' => 'SUPPLY_CLOSED');
        }

        //создание листа подбора для поставки
        public function createPickList(string $selectedOrders):array
        {
            if(!$this->activeSupplyId){
                throw new CustomException('NO_ACTIVE_SUPPLY');
            }

            if(!$selectedOrders){
                throw new CustomException('WRONG_DATA');
            }

            $selectedOrders = explode(";", $selectedOrders);

            //передача заказов в поставку, им устанавливается статус 1
            $url = "supplies/$this->activeSupplyId";
            $sendJson = array("orders" => $selectedOrders);
            $sendJson = json_encode($sendJson);
            $headers  = array("Content-Type: application/json", "Content-Length: " . strlen($sendJson));
            $request  = (new WBRequestController($url, $headers, 0, $sendJson, 'PUT'))->sendRequest();

            if(isset($request['error']) && isset($request['errorText'])){
                if(isset($request['data']['failedOrders'])){
                    $request['errorText'] .= " Ошибка в заказах: ".implode(";", $request['data']['failedOrders']);
                }
                throw new CustomException('WB_ORDERS_ERROR', 0, $request['errorText']);
            }

            $stmt = self::$db->prepare("INSERT INTO `pick_lists` (`time`, `user_id`, `supplyId`) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", self::$time, Authenticate::$userId, $this->activeSupplyId);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
            $pickListId = self::$db->insert_id;
            
            $stickers = array();
            foreach($selectedOrders as $order){
                $stickers[$order] = array('orderId' => '', 'partA' => '', 'partB' => '', 'encoded' => '', 'svgBase64' => '', 'zpl' => '', 'wbId' => '', 'QR' => '');
            }
            
            //запрос стикеров для каждого заказа в листе подбора
            $url = "orders/stickers";
            $sendJson = array("orderIds" => $selectedOrders, "type" => "code128");
            $sendJson = json_encode($sendJson, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
            $headers = array("accept: application/json", "Content-Type: application/json", "Content-Length: " . strlen($sendJson));
            $request  = (new WBRequestController($url, $headers, 1, $sendJson))->sendRequest();
            if(isset($request['data']) && !empty($request['data'])){
                foreach($request['data'] as $data){
                    $orderId = $data['orderId'];
                    $stickers[$orderId] = array('partA' => $data['sticker']['wbStickerIdParts']['A'], 'partB' => $data['sticker']['wbStickerIdParts']['B'], 'encoded' => $data['sticker']['wbStickerEncoded'], 'svgBase64' => $data['sticker']['wbStickerSvgBase64'], 'zpl' => $data['sticker']['wbStickerZpl'], 'wbId' => $data['sticker']['wbStickerId'], 'QR' => '');
                }
            }

            //запрос стикеров c QR кодом для каждого заказа
            $url = "orders/stickers";
            $sendJson = array("orderIds" => $selectedOrders, "type" => "qr");
            $sendJson = json_encode($sendJson, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
            $headers = array("accept: application/json", "Content-Type: application/json", "Content-Length: " . strlen($sendJson));
            $request  = (new WBRequestController($url, $headers, 1, $sendJson))->sendRequest();
            if(isset($request['data']) && !empty($request['data'])){
                foreach($request['data'] as $data){
                    $orderId = $data['orderId'];
                    $stickers[$orderId]['QR'] = $data['sticker']['wbStickerSvgBase64'];
                }
            }

            foreach($selectedOrders as $order){
                //проверяем нет ли заказа в уже существующем листе подбора
                $result = self::$db->query("SELECT `id` FROM `pick_lists_strings` WHERE `orderId`='$order'");
                $result = $result->num_rows;
                if($result){
                    continue;
                }

                //обновление статуса заказа
                $stmt = self::$db->prepare("UPDATE `orders` SET `status`='1', `touched_time`=?, `user_id`=?, `supplyId`=? WHERE `orderId`=?");
                $stmt->bind_param("iisi", self::$time, Authenticate::$userId, $this->activeSupplyId, $order);
                $stmt->execute();

                //загрузка chrtId
                $result = self::$db->query("SELECT `chrtId` FROM `orders` WHERE `orderId`='$order'");
                $result = $result->fetch_assoc();
                $chrtId = $result['chrtId'];

                $stmt = self::$db->prepare("INSERT INTO `pick_lists_strings` 
                                        (`pickListId`, `time`, `userId`, `orderId`, `chrtId`, `supplyId`, `stickerPartA`, `stickerPartB`, `stickerEncoded`, `stickerSvgBase64`, `stickerWbId`, `stickerQR`) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiiisssssss", $pickListId, self::$time, Authenticate::$userId, $order, $chrtId, $this->activeSupplyId, $stickers[$order]['partA'], $stickers[$order]['partB'], $stickers[$order]['encoded'], $stickers[$order]['svgBase64'], $stickers[$order]['wbId'], $stickers[$order]['QR']);
                $stmt->execute();
                if($stmt->error) {
                    throw new CustomException('QUERY_ERROR', 0, $stmt->error);
                }
            }
            return array('pickListId' => $pickListId);
        }

        //очистка заказов
        public function clearOrders():void
        {
            self::$db->query("DELETE FROM `orders` WHERE `status`='0'");
        }

        //вывод закрытых поставок
        public function getSuppliesList(array $params):array
        {
            $query = '';
            $optionStr = '';

            if($params['startDate'] && $params['endDate']){
                $startTime = explode("-", $params['startDate']);
                $startTime = mktime(0, 0, 0, $startTime[1], $startTime[2], $startTime[0]);

                $endTime = explode("-", $params['endDate']);
                $endTime = mktime(23, 59, 59, $endTime[1], $endTime[2], $endTime[0]);

                $query = " AND (`open_time` >= $startTime AND `open_time` <= $endTime)";

                if($params['startDate'] != $params['endDate']){
                    $optionStr = "За период с ".date("d.m.Y", $startTime)." по ".date("d.m.Y", $endTime);
                }
                else{
                    $optionStr = "За ".date("d.m.Y", $startTime);
                }                
            }

            if($params['search']){
                $query = "AND `supplyId` LIKE '%$params[search]%'";
                $optionStr = "Поиск по фразе \"$params[search]\"";
            }

            $params['totalCount'] = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `supplies` WHERE `status`='2' $query");
            $pagination = new Pagination($params);

            $supplies = array();
            $result = self::$db->query("SELECT `id`, `supplyId`, `status`, `open_time`, `open_user_id`, `close_time`, `close_user_id` FROM `supplies` WHERE `status`='2' $query ORDER BY `open_time` DESC LIMIT $pagination->start, $pagination->onPage");
            while($supply = $result->fetch_assoc()){
                $user = new UserModel(['id' => $supply['open_user_id']]);
                $supply['openUserFio']  = $user->getFio();
                $supply['closeUserFio'] = $supply['openUserFio'];

                if($supply['close_user_id'] != $supply['open_user_id'] && $supply['close_user_id']){
                    $user = new UserModel(['id' => $supply['close_user_id']]);
                    $supply['closeUserFio'] = $user->getFio();
                }
                
                $supply['openTime']  = date("d.m.Y H:i", $supply['open_time']);
                $supply['closeTime'] = date("d.m.Y H:i", $supply['close_time']);
                $supply['ordersCount'] = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `orders` WHERE `supplyId`='$supply[supplyId]'");

                $supplies[] = $supply;
            }
            return array("supplies" => $supplies, "totalCount" => $pagination->totalCount, "totalPages" => $pagination->totalPages, "optionStr" => "$optionStr");
        }

        //получение новых заказов из ВБ
        private function getNewOrdersFromWB():void
        {
            $curDate = date("Y-m-d");

            //очищаем подвисшие заказы
            $result = self::$db->query("SELECT `id` FROM `options` WHERE `option`='clearOrders' AND `value`<>'$curDate'");
            $result = $result->num_rows;
            if($result){
                $stmt = self::$db->prepare("UPDATE `options` SET `value`=? WHERE `option`='clearOrders'");
                $stmt->bind_param("s", $curDate);
                $stmt->execute();
                $this->clearOrders();
            }

            //берём заказы за неделю
            $dateStart = date("Y-m-d\TH:i:s", mktime(0, 0, 0, date("n"), date("j") - 7, date("Y")))."Z";
            $dateStart = urlencode($dateStart);

            $url = "orders?date_start=$dateStart&status=0&take=1000&skip=0";
            $headers = array("accept: application/json");

            $request  = (new WBRequestController($url, $headers))->sendRequest();
            //помещаем новые заказы В БД
            if(isset($request['orders']) && !empty($request['orders'])){
                foreach($request['orders'] as $order){
                    //пропускаем заказы со статусом "Отмена клиента"
                    if($order['userStatus'] == 1){
                        continue;
                    }

                    //если заказ уже есть в БД, то пропускаем
                    $result = self::$db->query("SELECT id FROM `orders` WHERE `orderId`='$order[orderId]'");
                    $result = $result->num_rows;
                    if($result){
                        continue;
                    }

                    $order['dateCreated'] = strtotime($order['dateCreated']);

                    $barcodes = !empty($order['barcodes']) ? implode(";", $order['barcodes']) : $order['barcode'];
                    $scOfficesNames = !empty($order['scOfficesNames']) ? implode(";", $order['scOfficesNames']) : '';
                    $deliveryAddress = !empty($order['deliveryAddressDetails']) ? implode(", ", $order['deliveryAddressDetails']) : '';

                    $stmt = self::$db->prepare("INSERT INTO `orders` (`orderId`, `orderUID`, `chrtId`, `dateCreated`, `barcodes`, `convertedPrice`, `totalPrice`, `currencyCode`, `deliveryType`, `officeAddress`, `pid`, `rid`, `scOfficesNames`, `storeId`, `status`, `userStatus`, `wbWhId`, `deliveryAddress`, `time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isiisiiiisissiiiisi", $order['orderId'], $order['orderUID'], $order['chrtId'], $order['dateCreated'], $barcodes, $order['convertedPrice'], $order['totalPrice'], $order['currencyCode'], $order['deliveryType'], $order['officeAddress'], $order['pid'], $order['rid'], $scOfficesNames, $order['storeId'], $order['status'], $order['userStatus'], $order['wbWhId'], $deliveryAddress, self::$time);
                    $stmt->execute();
                    if($stmt->error) {
                        throw new CustomException('QUERY_ERROR', 0, $stmt->error);
                    }
                }
            } 
        }

        //подгрузка номера активной поставки и количество заказов в поставке
        private function getActiveSupply():void
        {
            $result = self::$db->query("SELECT supplyId FROM `supplies` WHERE `status`='1'");
            $result = $result->fetch_assoc();
            if($result['supplyId']){
                $this->activeSupplyId = $result['supplyId'];
                $this->totalCountInSuplly = Helpers::getCount("SELECT COUNT(id) as `count` FROM `orders` WHERE `status`='1' AND supplyID='$result[supplyId]'");
            }
        }

        //подсчет количества дней/часов/минут с момента времени
        private function outputTimeInterval(int $timePoint):string
        {
            $outputTimeInterval = '';
            $timeInterval = self::$time - $timePoint;
            $days = floor($timeInterval/(60*60*24));
            if($days){
                $timeInterval -= $days*60*60*24;
                $outputTimeInterval = "$days д. ";
            }
            $hours = floor($timeInterval/(60*60));
            if($hours){
                $timeInterval -= $hours*60*60;
                $outputTimeInterval .= "$hours ч. ";
            }
            $minutes = floor($timeInterval/(60));
            if($minutes){
                $outputTimeInterval .= "$minutes мин.";
            }
            return $outputTimeInterval;
        }

        //сопоставление сортировки
        private function switchSort(string $sort, string $order):string
        {
            switch($sort){
                case 'orderId':        $sort = "ord.orderId $order"; break;
                case 'dateCreated':    $sort = "ord.dateCreated $order"; break;
                case 'type':           $sort = "it.type $order"; break;
                case 'articul':        $sort = "it.articul $order, it.size ASC, ord.id ASC"; break;
                case 'brand':          $sort = "it.brand $order"; break;
                case 'size':           $sort = "it.size $order"; break;
                case 'title':          $sort = "it.title $order"; break;
                case 'convertedPrice': $sort = "ord.convertedPrice $order"; break;
                default:               $sort = 'it.articul ASC, it.size ASC, ord.id ASC';
            }
            return $sort;
        }
    }
?>
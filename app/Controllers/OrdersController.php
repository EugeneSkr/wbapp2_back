<?php
    namespace App\Controllers;

    use App\Helpers\Helpers;
    use App\Models\OrderModel;

    class OrdersController extends AbstractController
    {

        public function __construct()
        {
            parent::__construct();
            Helpers::checkIfOperator();
        }

        //список заказов
        public function getOrdersList():void
        {
            $pageParams = array();
            $pageParams['sort'] = 'articul';
            $pageParams['order'] = 'ASC';
            $pageParams['search'] = '';
            $pageParams['currentPage'] = 1;
            $pageParams['onPage'] = 100;
            $pageParams['viewType'] = 'new';
            $pageParams['checkNewOrders'] = 1;
            $pageParams['supplyId'] = '';

            $pageParams = (new Helpers())->getParams($pageParams);

            if($pageParams['viewType'] == 'new'){
                echo json_encode((new OrderModel())->getNewOrders($pageParams));
                exit;
            }
            echo json_encode((new OrderModel())->getOrdersInSupply());
            exit;
        }

        //новая поставка
        public function createSupply():void
        {
            echo json_encode((new OrderModel())->createSupply());
            exit;
        }

        //закрытие поставки
        public function closeSupply():void
        {
            echo json_encode((new OrderModel())->closeSupply());
            exit;
        }

        //создание листа подбора
        public function createPickList():void
        {
            $this->request->selectedOrders = Helpers::sanitize($this->request->selectedOrders);
            echo json_encode((new OrderModel())->createPickList($this->request->selectedOrders));
            exit;
        }

        //очистка заказов
        public function clearOrders():void
        {
            (new OrderModel())->clearOrders();
            echo json_encode(['answer' => 'CLEAR_OK']);
            exit;
        }

        //вывод закрытых поставок
        public function getSuppliesList():void
        {
            $pageParams = array();
            $pageParams['search'] = '';
            $pageParams['currentPage'] = 1;
            $pageParams['onPage'] = 50;
            $pageParams['startDate'] = '';
            $pageParams['endDate'] = '';
            
            $pageParams = (new Helpers())->getParams($pageParams);

            echo json_encode((new OrderModel())->getSuppliesList($pageParams));
            exit;
        }
    }
?>
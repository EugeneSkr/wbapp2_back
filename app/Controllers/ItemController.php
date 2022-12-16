<?php
    namespace App\Controllers;

    use App\Models\ItemModel;
    use App\Helpers\Helpers;
    use App\Structures\Item;

    class ItemController extends AbstractController
    {
        private array $params;
        public function __construct()
        {
            parent::__construct();

            $this->params = array();
            Helpers::checkIfAdmin();
        }

        //загрузка списка товаров
        public function getItemsList():void
        {
            $pageParams = array();
            $pageParams['sort'] = 'brand';
            $pageParams['order'] = 'ASC';
            $pageParams['search'] = '';
            $pageParams['currentPage'] = 1;
            $pageParams['onPage'] = 50;

            //проверяем, есть ли во входящих GET данных переменные с именем полей массива и берём их значения
            $pageParams = (new Helpers())->getParams($pageParams);

            $items = new ItemModel($this->params);
            echo json_encode($items->getList($pageParams));
            exit;
        }

        //загрузка карточки товара
        public function getItem(int $id):void
        {
            $this->params['id'] = intval(Helpers::sanitize($id));

            $item = new ItemModel($this->params);
            $item->getInfo();
            echo json_encode(['item' => $item->toArray()]);
            exit;
        }

        //сохранение нового/редактируемого товара
        public function saveItem():void
        {
            //проверяем, есть ли во входящих POST данных переменные с именем свойств класса Item и заносим их в массив
            $props = array_keys(get_class_vars(Item::class));
            foreach($props as $var){
                if(isset($this->request->$var)){
                    $this->params[$var] = $this->request->$var;
                }
            }

            if($this->params['costs']){
                $this->params['costs'] = str_replace(',', '.', $this->params['costs']);
                $this->params['costs'] = preg_replace("/[^[0-9]*[.]?[0-9]+$]/", '', $this->params['costs']);
                $this->params['costs'] *= 100;
            }

            $item = new ItemModel($this->params);
            $item->save();
            echo json_encode(["answer" => "ITEM_SAVE_SUCCESS"]);
            exit;
        }

        //удаление товара
        public function deleteItem(int $id):void
        {
            $this->params['id'] = intval(Helpers::sanitize($id));
            $item = new ItemModel($this->params);
            $item->delete();
            echo json_encode(["answer" => "ITEM_DELETE_SUCCESS"]);
            exit;
        }
    }

?>

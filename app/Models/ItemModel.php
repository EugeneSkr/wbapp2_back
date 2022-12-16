<?php
    namespace App\Models;

    use App\Controllers\AbstractController;
    use App\Controllers\FilesController;
    use App\Exceptions\CustomException;
    use App\Helpers\Helpers;
    use App\Helpers\Pagination;
    use App\Structures\Item;

    class ItemModel extends AbstractController
    {
        private $item;

        public function __construct($itemsParams)
        {
            $this->item = Item::fromArray($itemsParams);
            parent::__construct();
        }

        //загрузка списка товаров
        public function getList(array $params):array
        {
            $search = '';
            if($params['search']){
                $search = $params['search'];
                $costsSearch = $search;
                if(is_numeric($search)){
                    $costsSearch = $search * 100;
                }
                $search = "AND (`articul` LIKE '%$search%' OR  `articulWB` LIKE '%$search%' OR  `title` LIKE '%$search%' OR  `brand` LIKE '%$search%'  OR  `type` LIKE '%$search%'  OR  `color` LIKE '%$search%'  OR  `size` LIKE '%$search%' OR  `barcode` LIKE '%$search%' OR  `chrtId` LIKE '%$search%' OR  `costs` LIKE '%$costsSearch%')";
            }

            $params['totalCount'] = Helpers::getCount("SELECT COUNT(`id`) as `count` FROM `items` WHERE `deleted`='0' $search");
            $pagination = new Pagination($params);

            $items = array();
            $result = self::$db->query("SELECT * FROM `items` WHERE `deleted`='0' $search ORDER BY $params[sort] $params[order] LIMIT $pagination->start, $pagination->onPage");
            while ($item = $result->fetch_assoc()){
                $item['photo']      = Helpers::mainPhoto($item['photoList']);
                $item['shortTitle'] = Helpers::shrinkTitle($item['title']);
                $item['photoList']  = explode(';', $item['photoList']);
                $items[] = Item::fromArray($item);
            }
            return array("items" => $items, "totalPages" => $pagination->totalPages, "totalCount" => $pagination->totalCount);
        }

        //сохранение товара
        public function save():void
        {
            if(!($this->item->articul && $this->item->brand && $this->item->size)){
                throw new CustomException('WRONG_DATA');
            }
            
            $checkId = '';
            if($this->item->id){
                $checkId = "`id`<>'{$this->item->id} AND'";
            }
            $result = self::$db->query("SELECT id FROM `items` WHERE $checkId `articul`='{$this->item->articul}' AND `brand`='{$this->item->brand}' AND `size`='{$this->item->size}' AND `deleted`='0' LIMIT 0, 1");
            $result = $result->num_rows;
            if($result){
                throw new CustomException('ITEM_ALLREADY_EXIST');
            }
            if($this->item->id){
                $result = self::$db->query("SELECT `photoList` FROM `items` WHERE `id`='{$this->item->id}'");
                $result = $result->fetch_assoc();
                $result['photoList'] = explode(";", $result['photoList']);
                
                $this->item->photoList = FilesController::compareFileLists($this->item->photoList, $result['photoList']);
            }

            if(!empty($this->item->newPhotoList)){
                $this->item->newPhotoList = FilesController::copyFilesBeforeDelete($this->item->newPhotoList, TMPIMGPATH, IMGPATH);
                $this->item->photoList = array_merge($this->item->photoList, $this->item->newPhotoList);
            }

            $photoList = implode(';', $this->item->photoList);

            if($this->item->id){
                $stmt = self::$db->prepare("UPDATE `items` SET `articul`=?, `articulWb`=?, `title`=?, `brand`=?, `type`=?, `color`=?, `size`=?, `barcode`=?, `chrtId`=?, `costs`=?, `photoList`=?, `stickerSize`=?, `stickerContains`=?, `stickerCountry`=?, `stickerManufacturer`=?, `stickerAddress`=? WHERE id=?");
                $stmt->bind_param("sssssssssissssssi", $this->item->articul, $this->item->articulWb, $this->item->title, $this->item->brand, $this->item->type, $this->item->color, $this->item->size, $this->item->barcode, $this->item->chrtId, $this->item->costs, $photoList, $this->item->stickerSize, $this->item->stickerContains, $this->item->stickerCountry, $this->item->stickerManufacturer, $this->item->stickerAddress, $this->item->id);
            }
            else{
                $stmt = self::$db->prepare("INSERT INTO `items` (`articul`, `articulWb`, `title`, `brand`, `type`, `color`, `size`, `barcode`, `chrtId`, `costs`, `photoList`, `stickerSize`, `stickerContains`, `stickerCountry`, `stickerManufacturer`, `stickerAddress`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssissssss", $this->item->articul, $this->item->articulWb, $this->item->title, $this->item->brand, $this->item->type, $this->item->color, $this->item->size, $this->item->barcode, $this->item->chrtId, $this->item->costs, $photoList, $this->item->stickerSize, $this->item->stickerContains, $this->item->stickerCountry, $this->item->stickerManufacturer, $this->item->stickerAddress);
            }
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
        }

        //удаление товара
        public function delete():void
        {
            FilesController::copyFilesBeforeDelete((array)$this->item->photoList, IMGPATH, DELETEIMGPATH);

            $stmt = self::$db->prepare("UPDATE `items` SET `deleted`=1 WHERE id=?");
            $stmt->bind_param("i", $this->item->id);
            $stmt->execute();
            if($stmt->error) {
                throw new CustomException('QUERY_ERROR', 0, $stmt->error);
            }
        }

        //вывод свойств класса в массив
        public function toArray():array
        {
            return $this->item->toArray();
        }

        //загрузка карточки товара
        public function getInfo():void
        {
            $result = self::$db->query("SELECT * FROM `items` WHERE `id`='{$this->item->id}' AND `deleted`='0'");
            $item = $result->fetch_assoc();
            if(empty($item)){
                throw new CustomException('ITEM_DOESNT_EXIST');
            }
            $item['photoList'] = $item['photoList'] ? explode(";", $item['photoList']) : [];
            $this->item = Item::fromArray($item);
        }
    }
?>
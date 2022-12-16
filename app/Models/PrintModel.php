<?php
    namespace App\Models;

    use App\Controllers\AbstractController;
    use App\Exceptions\CustomException;
    use App\Helpers\Helpers;
    use App\Structures\Item;
    use App\Structures\Order;

    class PrintModel extends AbstractController
    {
        private $supplyId = '';
        private $pickListId = 0;

        public function __construct($source)
        {
            parent::__construct();
            if(is_numeric($source)){
                $this->pickListId = $source;
            }
            else if($source){
                $this->supplyId = $source;
            }
        }

        //печать ШК поставки
        public function printSupply():array
        {
            $supply = $this->checkSource();
            return array('print' => base64_decode($supply['result']['pdfString']));
        }

        //печать листа подбора
        public function printPickList():array
        {
            $check = $this->checkSource();

            $count = 0;
            $orders = array();
            $result = self::$db->query("SELECT * FROM `pick_lists_strings` WHERE $check[option] ORDER BY id ASC");
            while($order = $result->fetch_assoc()){
                $subResult = self::$db->query("SELECT * FROM `items` WHERE `chrtId`='$order[chrtId]'");
                $item = $subResult->fetch_assoc();
                if(empty($item)){
                    throw new CustomException('ITEM_DOESNT_EXIST');
                }
                $item['color'] = str_replace(";", " ", $item['color']);
                $item['shortTitle'] = Helpers::shrinkTitle($item['title']);
                $item['photo'] = Helpers::mainPhoto($item['photoList']);
                $item['photoList'] = explode(';', $item['photoList']);

                $order['item'] = Item::fromArray($item)->toArray();
                $order['sticker'] = base64_decode($order['stickerSvgBase64']);
                $orders[] = Order::fromArray($order)->toArray();
                //$orders[] = array('orderId' => $order['orderId'], 'brand' => $item['brand'], 'title' => $item['title'], 'shortTitle' => $shortTitle, 'size' => $item['size'], 'photo' => $photo, 'articul' => $item['articul'], 'color' => $item['color'], 'sticker' => $sticker);
                $count++;
            }
            return array('supplyId' => $this->supplyId, 'pickListId' => $this->pickListId, 'count' => $count, 'date' => $check['date'], 'orders' => $orders);
        }

        //печать информационных стикеров
        public function printStickers():array
        {
            $check = $this->checkSource();

            require_once('./vendor/setasign/tfpdf/tfpdf.php');
            $pdf = new \tFPDF('L','mm','sticker');

            $result = self::$db->query("SELECT * FROM `pick_lists_strings` WHERE $check[option] ORDER BY id ASC");
            while($sticker = $result->fetch_assoc()){
                $subResult = self::$db->query("SELECT * FROM `items` WHERE `chrtId`='$sticker[chrtId]'");
                $item = $subResult->fetch_assoc();
                if(empty($item)){
                    throw new CustomException('ITEM_DOESNT_EXIST');
                }
                $shortTitle = Helpers::shrinkTitle($item['title']);

                $str  = "\r\n";
                $str  .= "Наименование: $shortTitle\r\n";
                $str  .= "Артикул: $item[articul]\r\n";
                $str  .= "Производитель: $item[stickerManufacturer]\r\n";
                $str  .= "Адрес производства: $item[stickerAddress]\r\n";
                $str  .= "Состав: $item[stickerContains]\r\n";
                $str  .= "Размер: $item[stickerSize]\r\n";

                
                $pdf->AddPage();
                $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
                $pdf->SetFont('DejaVu','',6);
                $pdf->Image(BARCODEGEN."?barcode=$item[barcode]", 3 , 2, 54, 20, "PNG" );
                $pdf->SetXY(3, 22);
                $pdf->Write(3,$str);

            }

            $pdf->Output('F', STICKERSPDFPATH.'stickers.pdf');
            return array('stickers' => 'stickers.pdf');
        }

        //печать стикеров QR
        public function printStickersQR():array
        {
            $check = $this->checkSource();
            
            $stickers = array();
            $result = self::$db->query("SELECT * FROM `pick_lists_strings` WHERE $check[option] ORDER BY id ASC");
            while($sticker = $result->fetch_assoc()){
                $stickers[] = array('sticker' => base64_decode($sticker['stickerQR']));
            }

            return array('stickers' => $stickers);
        }

       //загрузка информации о поставке или листе подбора
       private function checkSource():array
       {
            if($this->supplyId){
                $result = self::$db->query("SELECT * FROM `supplies` WHERE `supplyId`='$this->supplyId'");
                $result = $result->fetch_assoc();
                if(empty($result)){
                    throw new CustomException('SUPPLY_DOESNT_EXIST');
                }
                return array('date' => date("d.m.Y", $result['open_time']), 'option' => "`supplyId`='$this->supplyId'", 'result' => $result);
            }
            if($this->pickListId){
                $result = self::$db->query("SELECT * FROM `pick_lists` WHERE `id`='$this->pickListId'");
                $result = $result->fetch_assoc();
                if(empty($result)){
                    throw new CustomException('PICKLIST_DOESNT_EXIST');
                }
                return array('date' => date("d.m.Y", $result['time']), 'option' => "`pickListId`='$this->pickListId'", 'result' => $result);
            }
            throw new CustomException('WRONG_DATA');
       }
    }

?>
<?php
    namespace App\Controllers;

    use App\Helpers\Helpers;
    use App\Models\PrintModel;

    class PrintController extends AbstractController
    {
        public function __construct()
        {
            parent::__construct();
            Helpers::checkIfOperator();
        }

        //печать ШК поставки
        public function printSupply():void
        {
            $supplyId = Helpers::sanitize($this->request->supplyId);
            echo json_encode((new PrintModel($supplyId))->printSupply());
            exit;
        }

        //печать листа подбора
        public function printPickList():void
        {
            $source = Helpers::sanitize($this->request->source);
            echo json_encode((new PrintModel($source))->printPickList());
            exit;
        }

        //печать информационных стикеров
        public function printStickers():void
        {
            $source = Helpers::sanitize($this->request->source);
            echo json_encode((new PrintModel($source))->printStickers());
            exit;
        }

        //печать стикеров QR кодов по всей поставке
        public function printStickersQR():void
        {
            $source = Helpers::sanitize($this->request->source);
            echo json_encode((new PrintModel($source))->printStickersQR());
            exit;
        }
    }

?>
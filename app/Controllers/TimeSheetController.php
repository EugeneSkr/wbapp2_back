<?php
    namespace App\Controllers;

    use App\Models\TimeSheetModel;
    use App\Helpers\Helpers;

    class TimeSheetController extends AbstractController
    {
        public function __construct()
        {
            parent::__construct();
            Helpers::checkIfAdmin();
        }
        public function getTimeSheet():void
        {
            $month = intval(Helpers::sanitize($this->request->month));
            $year  = intval(Helpers::sanitize($this->request->year));

            if(!$month){
                $month = date("n");
            }

            if(!$year){
                $year = date("Y");
            }

            echo json_encode((new TimeSheetModel($month, $year))->getTimeSheet());
            exit;
        }

    }

?>
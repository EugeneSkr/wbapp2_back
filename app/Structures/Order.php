<?php
    namespace App\Structures;

    use Spatie\DataTransferObject\DataTransferObject;

    class Order extends DataTransferObject
    {
         
        public int $id;
        public int $orderId;
        public string $orderUID;
        public string $dateCreated;
        public int $convertedPrice;
        public string $officeAddress;
        public string $scOfficesNames;
        public int $storeId;
        public int $status;
        public int $userStatus;
        public int $wbWhId;
        public int $touched_time;
        public int $time;
        public int $selected;
        public string $outputTimeInterval;
        public string $deliveryAddress;
        public string $sticker;
        public string $stickerPartA;
        public string $stickerPartB;

        public array $item;

        public static function fromArray(array $params):self
        {
            $params['id'] = $params['orderInnerId'] ?? $params['id'];
            $order = [
                'id' => (int) $params['id'] ?? 0,
                'orderId' => (int) $params['orderId'] ?? 0,
                'orderUID' => $params['orderUID'] ?? '',
                'dateCreated' => $params['dateCreated'] ?? '',
                'convertedPrice' => (int) $params['convertedPrice'] ?? 0,
                'officeAddress' => $params['officeAddress'] ?? '',
                'scOfficesNames' => $params['scOfficesNames'] ?? '',
                'storeId' => (int) $params['storeId'] ?? 0,
                'status' => (int) $params['status'] ?? 0,
                'userStatus' => (int) $params['userStatus'] ?? 0,
                'wbWhId' => (int) $params['wbWhId'] ?? 0,
                'touched_time' => (int) $params['touched_time'] ?? 0,
                'time' => (int) $params['time'] ?? 0,
                'selected' => (int) $params['selected'] ?? 0,
                'outputTimeInterval' => $params['outputTimeInterval'] ?? '',
                'deliveryAddress' => $params['deliveryAddress'] ?? '',
                'sticker' => $params['sticker'] ?? '',
                'stickerPartA' => $params['stickerPartA'] ?? '',
                'stickerPartB' => $params['stickerPartB'] ?? '',
                'item' => $params['item'] ?? []
            ];
            return new self($order);
        }




    }
?>
<?php
    namespace App\Structures;

    use Spatie\DataTransferObject\DataTransferObject;

    class Item extends DataTransferObject
    {
        public int $id;
        public string $articul;
        public string $articulWb;
        public string $title;
        public string $shortTitle;
        public string $brand;
        public string $type;
        public string $color;
        public string $size;
        public string $barcode;
        public int $chrtId;
        public int $costs;
        public string $photo;
        public array $photoList;
        public array $newPhotoList;
        public string $stickerSize;
        public string $stickerContains;
        public string $stickerCountry;
        public string $stickerManufacturer;
        public string $stickerAddress;

        public static function fromArray(array $params):self
        {
            $params['id'] = $params['itemId'] ?? $params['id'];

            $item = [
                'id' => (int) $params['id'] ?? 0,
                'articul' => $params['articul'] ?? '',
                'articulWb' => $params['articulWb'] ?? '',
                'title' => $params['title'] ?? '',
                'shortTitle' => $params['shortTitle'] ?? '',
                'brand' => $params['brand'] ?? '',
                'type' => $params['type'] ?? '',
                'color' =>  $params['color'] ?? '',
                'size' => $params['size'] ?? '',
                'barcode' => $params['barcode'] ?? '',
                'chrtId' => (int) $params['chrtId'] ?? 0,
                'costs' => (int) $params['costs'] ?? 0,
                'photo' => $params['photo'] ?? '',
                'photoList' => $params['photoList'] ?? [],
                'newPhotoList' => $params['newPhotoList'] ?? [],
                'stickerSize' => $params['stickerSize'] ?? '',
                'stickerContains' => $params['stickerContains'] ?? '',
                'stickerCountry' => $params['stickerCountry'] ?? '',
                'stickerManufacturer' => $params['stickerManufacturer'] ?? '',
                'stickerAddress' => $params['stickerAddress'] ?? ''
            ];
            return new self($item);
        }
    }

?>
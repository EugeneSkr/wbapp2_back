<?php
    require_once('config.php');

    //header('Access-Control-Allow-Origin: *');
    //header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    ini_set("display_errors","1");
    ini_set("display_startup_errors","1");
    ini_set('error_reporting', E_ALL);

    header('Content-type: text/html; charset="utf-8"',true);

    $site = "http://wb.malenkiy-muk.ru/";

    if(isset($_GET['supplyId'])){
        $supplyId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['supplyId'])));
        $sql = "SELECT * FROM `supplies` WHERE `supplyId`='$supplyId'";
        $r = mysqli_query($db, $sql);
        $v = mysqli_fetch_array($r);
        if(!empty($v)){
            $data = base64_decode($v['pdfString']);
            //header('Content-Type: application/pdf');
            //echo $data;
            header('Content-type: text/html; charset="utf-8"',true);
            $str = '';
            $str .= '
                
                <!doctype html>
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Wbapp</title>
                <base href="/">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
                <link rel="stylesheet" href="styles.css"></head>
                <body>
                <style>
                    @media print  {
                        h1 { page-break-before: always; }
                        .noprint{
                            display:none;
                        }
                }
                </style>
            ';
            $str .= "<div class=\"container\">";
                $str .= "<div class=\"my-3 text-center\">";
                    $str .= "<svg width=\"400\" height=\"300\"  fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">$data</svg>";
                    $str .= "<div class=\"text-center\"><h5>Поставка $v[supplyId]</h5></div>";
                $str .= "</div>";
                
                
            $str .= "</div>";
            $str .= '</body></html>';

            echo $str;
        }   
        else{
            header('Content-type: text/html; charset="utf-8"',true);
            echo "<p>Поставка с таким идентификатором не найдена</p>";
        }
    }

    if(isset($_GET['printPickList'])){

    }

    //UPDATE `orders` SET `status`='0', `supplyId`=''
    /*if(isset($_GET['printPickListPack'])){
        $pickListId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['printPickListPack'])));

        $sql ="SELECT * FROM `pick_lists` WHERE id='$pickListId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            $data = base64_decode($value['pdfString']);
            header('Content-Type: application/pdf');
            echo $data;
            


        }
        else{
            header('Content-type: text/html; charset="utf-8"',true);
            echo "<p>Лист подбора с таким идентификатором не найден</p>";
        }
    }*/


    //печать этикеток по всей поставке
    if(isset($_GET['pickListIdStickersBySupplyId'])){
        $supplyId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListIdStickersBySupplyId'])));
        $sql = "SELECT * FROM `supplies` WHERE `supplyId` = '$supplyId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            require('./fpdf/tfpdf.php');
            $pdf = new TFPDF('L','mm','sticker');
            $str = '';
            $sql = "SELECT * FROM `pick_lists_strings` WHERE `supplyId`='$supplyId' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $sql = "SELECT * FROM `items` WHERE `chrtId`='$val[chrtId]'";
                $r = mysqli_query($db, $sql);
                $v = mysqli_fetch_array($r);
                if(!empty($v)){
                    $item = $v;
                }

                $shortTitle = $item['title'];
                if(strlen($shortTitle) > 200){
                    $shortTitle = mb_substr($shortTitle, 0, 50, 'utf-8')."...";
                }

                $str  = '';
                $str  .= "Наименование: $shortTitle\r\n";
                $str  .= "Артикул: $item[articul]\r\n";
                $str  .= "Производитель: $item[sticker_manufacturer]\r\n";
                $str  .= "Адрес производства: $item[sticker_address]\r\n";
                $str  .= "Состав: $item[sticker_contains]\r\n";
                $str  .= "Размер: $item[sticker_size]\r\n";

                
                $pdf->AddPage();
                $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
                $pdf->SetFont('DejaVu','',6);
                $pdf->Image("{$site}barcodegen/drawBarcode2.php?barcode=$item[barcode]", 2 , 2, 54, 20, "PNG" );
                $pdf->SetXY(2, 22);
                $pdf->Write(4,$str);
                
            }
            $pdf->Output();

        }
        else{
            echo "Поставка с таким идентификатором не найдена";
        }
    }


    //печать этикеток
    if(isset($_GET['pickListIdStickers'])){
        $pickListId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListIdStickers'])));

        header('Content-type: text/html; charset="utf-8"',true);
        $sql ="SELECT * FROM `pick_lists` WHERE id='$pickListId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            require('./fpdf/tfpdf.php');

            $pdf = new TFPDF('L','mm','sticker');
                $str = '';
                $sql = "SELECT * FROM `pick_lists_strings` WHERE `pickListId`='$value[id]' ORDER BY id ASC";
                $res = mysqli_query($db, $sql);
                while($val = mysqli_fetch_array($res)){
                    $sql = "SELECT * FROM `items` WHERE `chrtId`='$val[chrtId]'";
                    $r = mysqli_query($db, $sql);
                    $v = mysqli_fetch_array($r);
                    if(!empty($v)){
                        $item = $v;
                    }

                    $shortTitle = $item['title'];
                    if(strlen($shortTitle) > 200){
                        $shortTitle = mb_substr($shortTitle, 0, 50, 'utf-8')."...";
                    }

                    $str  = '';
                    $str  .= "Наименование: $shortTitle\r\n";
                    $str  .= "Артикул: $item[articul]\r\n";
                    $str  .= "Производитель: $item[sticker_manufacturer]\r\n";
                    $str  .= "Адрес производства: $item[sticker_address]\r\n";
                    $str  .= "Состав: $item[sticker_contains]\r\n";
                    $str  .= "Размер: $item[sticker_size]\r\n";

                    
                    $pdf->AddPage();
                    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
                    $pdf->SetFont('DejaVu','',6);
                    $pdf->Image("{$site}barcodegen/drawBarcode2.php?barcode=$item[barcode]", 4, 2, 54, 20, "PNG" );
                    $pdf->SetXY(4, 22);
                    $pdf->Write(4,$str);
                   
                }
                $pdf->Output();



                
            
            /*$str = '';
            $str .= '
                
                <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
                "http://www.w3.org/TR/html4/strict.dtd">
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Wbapp</title>
                <body>
                <style>
                    @media print  {
                        @page {
                            size: 58mm 60mm;
                        }
                        .breakPage { page-break-before: always; }
                        .noprint{
                            display:none;
                        }
                }
                </style>
            ';
            $str .= "<div class=\"noprint my-1\"><a class=\"btn btn-warning btn-sm me-3\" target=\"_blank\" href=\"{$site}printPdf.php?pickListId=$pickListId\">Лист подбора</a><a class=\"btn btn-warning btn-sm me-3\" target=\"_blank\" href=\"{$site}printPdf.php?pickListIdPack=$pickListId\">СтикерыQR</a></div>";
            $str .= "<div style=\"width:60mm; height: 58mm;\" class=\"text-center\">";
            $str .= "<div style=\"margin:2mm\">";
            $sql = "SELECT * FROM `pick_lists_strings` WHERE `pickListId`='$value[id]' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $sql = "SELECT * FROM `items` WHERE `chrtId`='$val[chrtId]'";
                $r = mysqli_query($db, $sql);
                $v = mysqli_fetch_array($r);
                if(!empty($v)){
                    $item = $v;
                }

                $shortTitle = $item['title'];
                if(strlen($shortTitle) > 200){
                    $shortTitle = mb_substr($shortTitle, 0, 50, 'utf-8')."...";
                }

                $str .= "<div style=\"margin-top:2mm;\">";
                    
                $str .= "</div>";

                $str .= "<div class=\"text-start\" style=\"font-size:2mm; margin-top:2mm;\">";
                $str .= "<p style=\"margin:5px 0;\">Наименование: $shortTitle</p>";
                $str .= "<p style=\"margin:5px 0;\">Артикул: $item[articul]</p>";
                $str .= "<p style=\"margin:5px 0;\">Производитель: $item[sticker_manufacturer]</p>";
                $str .= "<p style=\"margin:5px 0;\">Адрес производства: $item[sticker_address]</p>";
                $str .= "<p style=\"margin:5px 0;\">Состав: $item[sticker_contains]</p>";
                $str .= "<p style=\"margin:5px 0;\">Размер: $item[sticker_size]</p>";

                $str .= "</div>";



                
                $str .= "<div class=\"breakPage\"></div>";
            }
            $str .= "</div>";
            $str .= "</div>";
            $str .= '</body></html>';

            echo $str;
            */


        }
        else{
            echo "<p>Лист подбора с таким идентификатором не найден</p>";
        }
        
    }

    //печать QR кодов по всей поставке
    if(isset($_GET['pickListIdPackBySupplyId'])){
        $supplyId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListIdPackBySupplyId'])));
        $sql = "SELECT * FROM `supplies` WHERE `supplyId` = '$supplyId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            $str = '';
            $str .= '
                
                <!doctype html>
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Wbapp</title>
                <base href="/">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
                <link rel="stylesheet" href="styles.css"></head>
                <body>
                <style>
                    @media print  {

                        h1 { page-break-before: always; }
                        .noprint{
                            display:none;
                        }
                        
                    }
                    
                </style>
            ';
            $sql = "SELECT * FROM `pick_lists_strings` WHERE `supplyId`='$supplyId' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $sticker =  base64_decode($val['stickerQR']);
                $str .= "<svg style=\"height:165px; width:260px; margin:0; padding:0px;\" viewBox=\"0 0 400 300\" xmlns=\"http://www.w3.org/2000/svg\"> $sticker</svg>";
                $str .= "<h1></h1>";
            }
            $str .= "</div>";
            $str .= '</body></html>';

            echo $str;
        }
        else{
            echo "Поставка с таким идентификатором не найдена";
        }

    }

    //вывод QR кодов заказов по листу подбора
    if(isset($_GET['pickListIdPack'])){
        $pickListId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListIdPack'])));

        header('Content-type: text/html; charset="utf-8"',true);
        $sql ="SELECT * FROM `pick_lists` WHERE id='$pickListId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            $str = '';
            $str .= '
                
                <!doctype html>
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Wbapp</title>
                <base href="/">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
                <link rel="stylesheet" href="styles.css"></head>
                <body>
                <style>
                    @media print  {

                        h1 { page-break-before: always; }
                        .noprint{
                            display:none;
                        }
                        
                    }
                    
                </style>
            ';
            $str .= "<div class=\"noprint my-1\"><a class=\"btn btn-warning btn-sm me-3\" target=\"_blank\" href=\"{$site}printPdf.php?pickListId=$pickListId\">Лист подбора</a><a class=\"btn btn-warning btn-sm me-3\" target=\"_blank\" href=\"{$site}printPdf.php?pickListIdStickers=$pickListId\">Этикетки</a></div>";
            $str .= "<div>";
            $sql = "SELECT * FROM `pick_lists_strings` WHERE `pickListId`='$value[id]' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $sticker =  base64_decode($val['stickerQR']);
                $str .= "<svg style=\"height:165px; width:260px; margin:0; padding:0px;\" viewBox=\"0 0 400 300\" xmlns=\"http://www.w3.org/2000/svg\"> $sticker</svg>";
                $str .= "<h1></h1>";
            }
            $str .= "</div>";
            $str .= '</body></html>';

            echo $str;


        }
        else{
            echo "<p>Лист подбора с таким идентификатором не найден</p>";
        }

    }

    //вывод QR кодов PDF заказов по листу подбора
   /* if(isset($_GET['pickListIdPackPdf'])){
        $pickListId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListIdPackPdf'])));

        header('Content-type: text/html; charset="utf-8"',true);
        $sql ="SELECT * FROM `pick_lists` WHERE id='$pickListId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){

            require('./fpdf/tfpdf.php');

            $pdf = new TFPDF('P','mm','sticker');
            $sql = "SELECT * FROM `pick_lists_strings` WHERE `pickListId`='$value[id]' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $sticker =  base64_decode($val['stickerQR']);
                //$str = "<svg style=\"height:165px; width:260px; margin:0; padding:0px;\" viewBox=\"0 0 400 300\" xmlns=\"http://www.w3.org/2000/svg\"> $sticker</svg>";
                $pdf->AddPage();
                $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
                $pdf->SetFont('DejaVu','',8);
                $pdf->SetXY(2, 2);
                //$pdf->ImageSVG($sticker, 2, 2, '', '', '', '', '', false);
            
            }
            $pdf->Output();
        }
        else{
            echo "<p>Лист подбора с таким идентификатором не найден</p>";
        }

    }*/


    // вывод всего листа подбора по номеру поставки
    if(isset($_GET['pickListBySupplyId'])){
        $supplyId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListBySupplyId'])));
        $sql = "SELECT * FROM `supplies` WHERE `supplyId` = '$supplyId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            header('Content-type: text/html; charset="utf-8"',true);
            $date = date("d.m.Y", $value['open_time']);
            $count = 0;
            $tStr = '';

            $str = '';
            $str .= '
                
                <!doctype html>
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Wbapp</title>
                <base href="/">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
                <link rel="stylesheet" href="styles.css"></head>
                <body>
                <style>
                    @media print  {
                        h1 { page-break-before: always; }
                        .noprint{
                            display:none;
                        }
                }
                </style>
                
            ';

            $sql = "SELECT * FROM `pick_lists_strings` WHERE `supplyId`='$supplyId' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $item = array();
                $sql = "SELECT * FROM `items` WHERE `chrtId`='$val[chrtId]'";
                $r = mysqli_query($db, $sql);
                $v = mysqli_fetch_array($r);
                if(!empty($v)){
                    $item = $v;
                }

                $shortTitle = $item['title'];
                if(strlen($shortTitle) > 200){
                    $shortTitle = mb_substr($shortTitle, 0, 50, 'utf-8')."...";
                }


                $tStr .= "<tr style=\"border-bottom:2px solid #555;\">";
                $tStr .= "<td>$val[orderId]</td>";
                $tStr .= "<td>";
                    if($item['photoList']){
                        $photo = explode(";", $item['photoList']);
                        $photo = $photo[0];
                        $tStr .= "<a href=\"{$site}public/images/$photo\" target=\"_blank\"><img src=\"{$site}cache/76-100-100/public/images/$photo\" /></a>";
                    }
                $tStr .= "</td>";
                $tStr .= "<td>$item[brand]</td>";
                $tStr .= "<td title=\"$item[title]\">$shortTitle</td>";
                $tStr .= "<td>$item[size]</td>";
                $tStr .= "<td>$item[color]</td>";
                $tStr .= "<td>$item[articul]</td>";
                $tStr .= "<td>";
                    $sticker = $val['stickerSvgBase64'];
                    $sticker =  base64_decode($sticker);
                    $tStr .= "<div style=\"height:120px;\">";
                        $tStr .= "<svg style=\"height:120px; width:250px;\" viewBox=\"0 0 400 300\" xmlns=\"http://www.w3.org/2000/svg\"> $sticker</svg>";
                        /*$tStr .= "<div class=\"\" style=\"background-color:#eee; color:#000; margin-left:27px;\">";
                            $tStr .= "<span class=\"fs-3 ms-1\">$val[stickerPartA]</span>";
                            $tStr .= "<span class=\"fs-2 float-end me-2\">$val[stickerPartB]</span>";
                        $tStr .= "</div>";*/
                    $tStr .= "</div>";
                $tStr .= "</td>";

                $tStr .= "</tr>";
                $count++;
            }

            $str .= "<div class=\"container\">";
            $str .= "<h5 class=\"mt-3\">Лист подбора по поставке <b>$supplyId</b></h5>";
            $str .= "<p class=\"my-3\">Дата: $date <span class=\"ms-5\">Количество товара: $count</span></p>";
            
            
            if($tStr){
                $str .= "<table class=\"table table-bordered table-sm mb-5\">";
                $str .= "<thead>";
                    $str .= "<tr>";
                        $str .= "<td>№ задания</td>";
                        $str .= "<td>Фото</td>";
                        $str .= "<td>Бренд</td>";
                        $str .= "<td>Название</td>";
                        $str .= "<td>Размер</td>";
                        $str .= "<td>Цвет</td>";
                        $str .= "<td>Артикул поставщика</td>";
                        $str .= "<td>Этикетка</td>";
                    $str .= "</tr>";
                $str .= "</thead>";
                $str .= "<tbody>";
                $str .= $tStr;
                $str .= "</tbody>";
                $str .= "</table>";
            }

            $str .= "</div>";
            $str .= '</body></html>';

            echo $str;


        }
        else{
            echo "<p>Поставка с таким идентификатором не найдена</p>";
        }


    }

    //печать листа подбора
    if(isset($_GET['pickListId'])){
        $pickListId = myhsc(strip_tags(mysqli_real_escape_string($db, $_GET['pickListId'])));

        $sql ="SELECT * FROM `pick_lists` WHERE id='$pickListId'";
        $result = mysqli_query($db, $sql);
        $value = mysqli_fetch_array($result);
        if(!empty($value)){
            header('Content-type: text/html; charset="utf-8"',true);
            $str = '';

            $str .= '
                
                <!doctype html>
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Wbapp</title>
                <base href="/">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
                <link rel="stylesheet" href="styles.css"></head>
                <body>
                <style>
                    @media print  {
                        h1 { page-break-before: always; }
                        .noprint{
                            display:none;
                        }
                }
                </style>
                
            ';

            $str .= "<div class=\"noprint container\"><a class=\"btn btn-warning btn-sm me-3\" target=\"_blank\" href=\"{$site}printPdf.php?pickListIdPack=$pickListId\">СтикерыQR</a><a class=\"btn btn-warning btn-sm me-3\" target=\"_blank\" href=\"{$site}printPdf.php?pickListIdStickers=$pickListId\">Этикетки</a></div>";
            $date = date("d.m.Y", $value['time']);
            $count = 0;
            $tStr = '';

            $sql = "SELECT * FROM `pick_lists_strings` WHERE `pickListId`='$value[id]' ORDER BY id ASC";
            $res = mysqli_query($db, $sql);
            while($val = mysqli_fetch_array($res)){
                $item = array();
                $sql = "SELECT * FROM `items` WHERE `chrtId`='$val[chrtId]'";
                $r = mysqli_query($db, $sql);
                $v = mysqli_fetch_array($r);
                if(!empty($v)){
                    $item = $v;
                }

                $shortTitle = $item['title'];
                if(strlen($shortTitle) > 200){
                    $shortTitle = mb_substr($shortTitle, 0, 50, 'utf-8')."...";
                }


                $tStr .= "<tr style=\"border-bottom:2px solid #555;\">";
                $tStr .= "<td>$val[orderId]</td>";
                $tStr .= "<td>";
                    if($item['photoList']){
                        $photo = explode(";", $item['photoList']);
                        $photo = $photo[0];
                        $tStr .= "<a href=\"{$site}public/images/$photo\" target=\"_blank\"><img src=\"{$site}cache/76-100-100/public/images/$photo\" /></a>";
                    }
                $tStr .= "</td>";
                $tStr .= "<td>$item[brand]</td>";
                $tStr .= "<td title=\"$item[title]\">$shortTitle</td>";
                $tStr .= "<td>$item[size]</td>";
                $tStr .= "<td>$item[color]</td>";
                $tStr .= "<td>$item[articul]</td>";
                $tStr .= "<td>";
                    $sticker = $val['stickerSvgBase64'];
                    $sticker =  base64_decode($sticker);
                    $tStr .= "<div style=\"height:120px;\">";
                        $tStr .= "<svg style=\"height:120px; width:250px;\" viewBox=\"0 0 400 300\" xmlns=\"http://www.w3.org/2000/svg\"> $sticker</svg>";
                        /*$tStr .= "<div class=\"\" style=\"background-color:#eee; color:#000; margin-left:27px;\">";
                            $tStr .= "<span class=\"fs-3 ms-1\">$val[stickerPartA]</span>";
                            $tStr .= "<span class=\"fs-2 float-end me-2\">$val[stickerPartB]</span>";
                        $tStr .= "</div>";*/
                    $tStr .= "</div>";
                $tStr .= "</td>";

                $tStr .= "</tr>";
                $count++;
            }

            $str .= "<div class=\"container\">";
            $str .= "<h5 class=\"mt-3\">Лист подбора</h5>";
            $str .= "<p class=\"my-3\">Дата: $date <span class=\"ms-5\">Количество товара: $count</span></p>";
            
            
            if($tStr){
                $str .= "<table class=\"table table-bordered table-sm mb-5\">";
                $str .= "<thead>";
                    $str .= "<tr>";
                        $str .= "<td>№ задания</td>";
                        $str .= "<td>Фото</td>";
                        $str .= "<td>Бренд</td>";
                        $str .= "<td>Название</td>";
                        $str .= "<td>Размер</td>";
                        $str .= "<td>Цвет</td>";
                        $str .= "<td>Артикул поставщика</td>";
                        $str .= "<td>Этикетка</td>";
                    $str .= "</tr>";
                $str .= "</thead>";
                $str .= "<tbody>";
                $str .= $tStr;
                $str .= "</tbody>";
                $str .= "</table>";
            }

            $str .= "</div>";
            $str .= '</body></html>';

            echo $str;
        }
        else{
            header('Content-type: text/html; charset="utf-8"',true);
            echo "<p>Лист подбора с таким идентификатором не найден</p>";
        }

    }

    if(isset($_GET['test'])){
        $secretKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3NJRCI6IjJmODRlYjE4LTZjZTQtNDA5ZS05YWJhLWZmMTEyZjdhYTZjOCJ9.m01VDJpQ7aC3bBdxblr7J3Rv_-ieD7L8r1bhFmMC2ow';

        $orders = "365157871;365161276";
        $orders = explode(";", $orders);

        $supplyId = "WB-GI-10920309";

        //$sendJson = array("orders" => $orders);
        //$sendJson = json_encode($sendJson);

        $url = "https://suppliers-api.wildberries.ru/api/v2/orders/stickers/pdf";

        $sendJson = array("orderIds" => $orders, "type" => "code128");
        $sendJson = json_encode($sendJson, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "accept: application/json",
            "Content-Type: application/json",
            "Content-Length: " . strlen($sendJson),
            "Authorization: $secretKey",
            );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sendJson);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $j = json_decode($resp, true);

        print_r($j);

        $pdfString = '';
        if(isset($j['data']['file'])){
            $pdfString = $j['data']['file'];
        }
        echo "$url<br />";
        echo "pdfString:$pdfString<br />";
        print_r($sendJson);
        
        /*$url = "https://suppliers-api.wildberries.ru/api/v2/supplies/$supplyId";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen($sendJson),
            "Authorization: $secretKey",
            );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS,$sendJson);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        
        $jsn = json_decode($resp, true);

        print_r($jsn);*/
    }
?>
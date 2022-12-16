<?php
	ini_set("display_errors","1");
	ini_set("display_startup_errors","1");
	ini_set('error_reporting', E_ALL);
	
	require_once('class/BCGFontFile.php');
	require_once('class/BCGColor.php');
	require_once('class/BCGDrawing.php');
	
	// Including the barcode technology
	require_once('class/BCGcode128.barcode.php');
	
	if(isset($_GET['barcode'])){
		$bar = htmlspecialchars(strip_tags($_GET['barcode']));
		$font = new BCGFontFile('./font/Arial.ttf', 14);
		$color_black = new BCGColor(0, 0, 0);
		$color_white = new BCGColor(255, 255, 255);
		// Barcode Part
		$code = new BCGcode128();
		$code->setScale(2);
		$code->setThickness(30);
		$code->setForegroundColor($color_black);
		$code->setBackgroundColor($color_white);
		$code->setFont($font);
		$code->setStart(NULL);
		$code->setTilde(true);
		$code->parse($bar);
		// Drawing Part
		$drawing = new BCGDrawing('', $color_white);
		$drawing->setBarcode($code);
		$drawing->draw();
		header('Content-Type: image/png');
		$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
	}

?>
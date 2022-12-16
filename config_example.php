<?php
    define('DBHOSTNAME', 'localhost');
    define('DBUSERNAME', 'user');
    define('DBPASSWORD', 'password');
    define('DBNAME',     'dbname');
    define('SECRETWORD', 'secretword');
    define('SITENAME',   'https://sitename.ru');

    define('MAILSENDADDRESS',  'email@email.ru');
    define('MAILSENDPASSWORD', 'password');
    define('MAILSENDHOST',     'smtp.email.ru');
    define('MAILSENDPORT',     '465');

    define('IMGPATH',         $_SERVER['DOCUMENT_ROOT'].'/public/images/');
    define('TMPIMGPATH',      $_SERVER['DOCUMENT_ROOT'].'/tmpfiles/');
    define('DELETEIMGPATH',   $_SERVER['DOCUMENT_ROOT'].'/deletedFiles/');
    define('STICKERSPDFPATH', $_SERVER['DOCUMENT_ROOT'].'/pdf/');
    define('BARCODEGEN',      'https://sitename.ru/drawBarcode.php');
  

    define('WBAPIPATH', 'https://suppliers-api.wildberries.ru/api/v2/');
    define('SECRETKEY', 'your_secretkey');
?>
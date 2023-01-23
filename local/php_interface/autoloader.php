<?php
if (file_exists($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php'))
{
    require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
}

// Подключение стороних классов (дублируем composer)
Bitrix\Main\Loader::registerAutoLoadClasses(null, array(
    'CGift\CGiftManager' => '/local/lib/CGift/CGiftManager.php',
    'Clever\CleverWorker' => '/local/lib/Clever/CleverWorker.php',
    'Omnic' => '/local/lib/omnic.php',
    'YAManager' => '/local/lib/YAManager.php',
    'oFile' => '/local/lib/oFile.php',
    'BodyPost' => '/local/lib/BodyPost.php',
    'FilesСlass' => '/local/lib/files.php',
    'HlClass' => '/local/lib/hl.php'
));

?>

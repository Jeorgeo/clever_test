<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

//use YAManager;

//$code = '8730646';

//$code = $_GET['code'];

//echo $code;

$segmentID = '28681570';

$fileName = 'user_admin_100.csv';

$url = __DIR__.'/'.$fileName;

$fileParams = [
    'file_path' => $url,
    'file_name'=> $fileName
];

$saveParams = [
    'segment'=>[
        'id'=> $segmentID,
        'name'=> 'сохранённый сегмент',
        'hashed'=> 0,
        'content_type'=>crm
    ]
];

//YAManager::getYAToken($code);

$res = YAManager::getSegments();

//YAManager::addDataForSegment($fileParams, $segmentID);

YAManager::createNewSegment($fileParams);

//YAManager::saveNewSegment($saveParams, $segmentID);

echo '<pre>';
echo print_r($res);
echo '</pre>';


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");


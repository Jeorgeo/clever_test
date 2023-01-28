<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

//use YAManager;

//$code = '8730646';

//$code = $_GET['code'];

//echo $code;

$segmentID = '28719903';

$fileName = 'user_admin_200.csv';

$url = __DIR__.'/'.$fileName;

$fileParams = [
    'file_path' => $url,
    'file_name'=> $fileName
];

$saveParams = [
        'id'            => $segmentID,
        'name'          => 'сохранённый сегмент',
        'status'        => 'uploaded',
        'hashed'        => 0,
        'content_type'  =>crm
];

//[segment] => Array
//(
//    [id] => 28719903
//            [type] => uploading
//[status] => uploaded
//[has_guests] =>
//            [guest_quantity] => 0
//            [can_create_dependent] =>
//            [has_derivatives] =>
//            [hashed] =>
//            [item_quantity] => 100
//            [guest] =>
//        )

//YAManager::getYAToken($code);

$res = YAManager::getSegments();

//YAManager::addDataForSegment($fileParams, $segmentID);

//$res1 = YAManager::createNewSegment($fileParams);

//YAManager::saveNewSegment($saveParams, $segmentID, 'сохранённый сегмент');

//$res1 = YAManager::addNewSegment($fileParams, 'фрагмент2');

echo '<pre>';
echo print_r($res);
echo print_r($res1);
echo '</pre>';


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");


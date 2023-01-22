<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

//use YAManager;

//$code = '8730646';

//$code = $_GET['code'];

//echo $code;

$segmentID = '28681570';

$fileName = 'contacts_100.csv';

$url = __DIR__.'/'.$fileName;

$content =  file_get_contents($url);

// Генерируем уникальную строку для разделения частей POST запроса
$delimiter = '-------------'.uniqid();

// Формируем объект oFile содержащий файл
$file = new oFile($filePath, 'text/plain', $content);

//echo '<pre>';
//echo print_r($file);
//echo '</pre>';

// Формируем тело POST запроса
//$postParams = BodyPost::Get(array('field'=>'text', 'file'=>$file), $delimiter);

//$postParams = [
//    'file'  => new \CurlFile(
//        $url,
//        'application/octet-stream',
//        $filePath
//    ),
//    'name'  => $filePath
//    ];

$postParams = [
    'url' => $url,
    'file'=> $fileName
];

$saveParams = [
    'segment'=>[
        'id'=> $segmentID,
        'name'=> 'сохранённый сегмент',
        'hashed'=> 0,
        'content_type'=>crm
    ]
];



//{
//    "segment" : {
//        "id" :  < int > ,
//        "name" :  < string > ,
//        "hashed" :  < boolean > ,
//        "content_type" :  < segment_content_type >
//    }
//}

//echo $post;

//YAManager::getYAToken($code);

$res = YAManager::getSegments();

//YAManager::addDataForSegment($postParams, $segmentID);

YAManager::addNewSegment($postParams);

//YAManager::saveNewSegment($saveParams, $segmentID);

echo '<pre>';
echo print_r($res);
echo '</pre>';


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");


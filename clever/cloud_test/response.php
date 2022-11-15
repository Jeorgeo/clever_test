<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$req = $_REQUEST;

$path = $_SERVER['DOCUMENT_ROOT'] . '/clever/cloud_test/logs/response.txt';

foreach ($req as $key => $value) {
    $text .= $key;
    $text .= ' : ';
    $text .= $value;
    $text .= "\r\n";
}

// $text = date("H:i:s") . "\n" .  print_r($req, true) . "\n";

$res = file_put_contents(
    $path,
    $text,
    FILE_APPEND
);

if ($res) {
    // message_to_telegram($text);
    // echo '<pre>';
    // echo print_r($orderModel);
    // echo '</pre>';
    echo json_encode(['code' => 0]);
}
else {
    echo 'нет запроса';
}

// function message_to_telegram($text, $reply_markup = '')
// {
//     $bot_token = '5666799054:AAEWJSdXm21rxT2o3SrvQuNGtrNkkJXQK3c';
//     $chatID = '-879444774';
//     $ch = curl_init();
//     $ch_post = [
//         CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/sendMessage',
//         CURLOPT_POST => TRUE,
//         CURLOPT_RETURNTRANSFER => TRUE,
//         CURLOPT_TIMEOUT => 10,
//         CURLOPT_POSTFIELDS => [
//             'chat_id' => $chatID,
//             'parse_mode' => 'HTML',
//             'text' => $text,
//             'reply_markup' => $reply_markup,
//         ]
//     ];
//
//     curl_setopt_array($ch, $ch_post);
//     curl_exec($ch);
// }

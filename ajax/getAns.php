<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Clever\CleverWorker;

$hightBlockBarCode = new CleverWorker('barcode');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$arRequest = json_decode($request->getInput(), true);

$ans = $arRequest['ans'];

function prepareResponse($text)
{
    $string = str_replace(['a', 'а'], '@', $text);
    return $string.date("Y-m-d H:i:s");
}

$stringForHlBlock = prepareResponse($ans['text']);

if ($hightBlockBarCode->updateDescription( $ans['code'], $stringForHlBlock)) {
    echo json_encode(
        [
            'status' => 1,
            'response'    => $stringForHlBlock,
        ],
        JSON_UNESCAPED_UNICODE
    );
};

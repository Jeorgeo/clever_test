<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Clever\CleverWorker;


$hightBlockProduct = new CleverWorker('products');
$hightBlockBarCode = new CleverWorker('barcode');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$arRequest = json_decode($request->getInput(), true);

$params = $arRequest['params'];

if (is_int($params['id']) && is_float($params['price'])) {
    $id = $params['id'];
    $price = $params['price'];
    if ($hightBlockProduct->updateProduts($id, $price)) {
        $code = $hightBlockBarCode->getBarCode($id);
        echo json_encode(
            [
                'status' => 1,
                'BarCode'    => $code,

            ],
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        [
            'status' => 0,
            'BarCode'    => '',
        ],
        JSON_UNESCAPED_UNICODE
    );
}

<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult['ID'] = mt_rand(1, 2);
$priceRandomId = mt_rand(1, 3);

$priceList = [
    456.89,
    589.56,
    586.45,
    456.88
];

$arResult['PRICE'] = $priceList[$priceRandomId];

$this->IncludeComponentTemplate();
?>

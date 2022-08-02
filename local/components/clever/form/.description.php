<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
	"NAME" => Loc::getMessage('СOMPONENT_NAME'),
	"DESCRIPTION" => Loc::getMessage('СOMPONENT_DESCRIPTION'),
	"ICON" => "",
	"COMPLEX" => "N",
	"PATH" => array(
		"ID" => "my_components",
		"SORT" => 1000,
		"NAME" => Loc::getMessage('СOMPONENT_PATH_NAME'),
	),
);

?>

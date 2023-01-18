<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

//use YAManager;

$code = '8730646';

YAManager::getYAToken($code);

YAManager::testClass();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");


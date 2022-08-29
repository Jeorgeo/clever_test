<?php

namespace CGift;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

Loader::includeModule('highloadblock');

/**
 * Класс для работы с подарочным сертификатом в заказе
 */
class CGiftManager
{

    function __construct()
    {
        // code...
    }


    public static function test()
    {
        echo 'Hello Word!';
    }
}




?>

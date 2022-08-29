<?php
\Bitrix\Main\EventManager::getInstance()->addEventHandler("main", "OnAdminSaleOrderEdit", array("MyTab", "onInit"));

class MyTab
{
    public static function onInit()
    {
        return array(
            "TABSET" => "MyTab",
            "GetTabs" => array("MyTab", "mygetTabs"),
            "ShowTab" => array("MyTab", "myshowTab"),
            "Action" => array("MyTab", "myaction"),
            "Check" => array("MyTab", "mycheck"),
        );
    }

    public static function myaction($arArgs)
    {
        // Действие после сохранения заказа. Возвращаем true / false
        // Сообщение $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");
        return true;
    }
    public static function mycheck($arArgs)
    {
        // Проверки перед сохранением. Возвращаем true / false
        return true;
    }

    public static function mygetTabs($arArgs)
    {
            return array(array("DIV" => "edit1", "TAB" => "Сертификат",
            "ICON" => "sale", "TITLE" => "Оплата подарочным сертификатом",
 	    "SORT" => 1));
    }

    public static function myshowTab($divName, $arArgs, $bVarsFromForm)
    {
        if ($divName == "edit1")
        {
            ?><tr>
                <td width="40%">Сумма оплаты:</td>
                <td width="40%">
                    <button type="button" name="button">Пересчитать заказ</button>
                </td>
            <td width="60%"><input type="text" name="myfield"></td></tr><?
        }
    }
}
 ?>

<?php
\Bitrix\Main\EventManager::getInstance()->addEventHandler("main", "OnAdminSaleOrderViewDraggable", array("MyClass1", "onInit"));

class MyClass1
{
    public static function onInit()
        {
            return array("BLOCKSET" => "MyClass1",
                "getScripts"  => array("MyClass1", "mygetScripts"),
                "getBlocksBrief" => array("MyClass1", "mygetBlocksBrief"),
                "getBlockContent" => array("MyClass1", "mygetBlockContent"),
                );
        }

    public static function mygetBlocksBrief($args)
        {
            $id = !empty($args['ORDER']) ? $args['ORDER']->getId() : 0;
            return array(
                'custom1' => array("TITLE" => "Пользовательский блок для заказа №".$id),
                'custom2' => array("TITLE" => "Еще один блок для заказа №".$id),
                );
        }

    public static function mygetScripts($args)
        {
            return '<script type="text/javascript">... </script>';
        }

    public static function mygetBlockContent($blockCode, $selectedTab, $args)
        {
        $result = '';
        $id = !empty($args['ORDER']) ? $args['ORDER']->getId() : 0;

        if ($selectedTab == 'tab_order')
            {
            if ($blockCode == 'custom1')
                $result = 'Содержимое блока custom1<br> Номер заказа: '.$id;
            if ($blockCode == 'custom2')
                $result = 'Содержимое блока custom2<br> Номер заказа: '.$id;
            }

        return $result;
        }
}
 ?>

<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/elementpropertytable.php");

class HistoryUpdate
{
    private $key = '676f5690-2e4f-4900-872a-7b75a8e2c8f7';
    private $url = 'https://api1.imshop.io/v1/clients/clever/statuses/sync/676f5690-2e4f-4900-872a-7b75a8e2c8f7';

    /**
     * @param int $orderNumber
     * @param int $userId
     */
    public function run($orderNumber = 0, $userId = 0)
    {
        $result = $this->getOrderList($orderNumber, $userId);

        if (!empty($result)) {
        	// потому что многомерные массивы это АПИ не воспринимает, убрать когда ни будь
        	$result = $result[0];
        	
            $curl = curl_init($this->url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($result));

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array(
                    "Content-Type: application/json",
                    "Authorization: Bearer {$this->key}",
                    "x-api-key: {$this->key}"
                )
            );

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response,1);

//            if ($result['success'] !== true) {
//				$this->createLog('History_update: ', print_r($result,1));
//			}
			$this->createLog('History_update: ', print_r($result,1));
            return true;
        }

        $this->createLog('History_update: ', "Нет информации: orderNumber - {$orderNumber}, userId - {$userId}");
        
        return true;
    }

    /**
     * @param $userId
     * @param $orderNumber
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     *
     *  Можно получить список заказов пользователя или один по номеру заказа
     */
    private function getOrderList($orderNumber, $userId)
    {
        $result = [];

        if ($userId == 0 && $orderNumber == 0) {
            return $result;
        }

        CModule::IncludeModule("sale"); // подключение модуля продаж
        if ($userId > 0) {
            $arFilter = array(
                "USER_ID" => $userId
            );
        }

        if ($orderNumber > 0) {
            $arFilter = array(
                "ACCOUNT_NUMBER" => $orderNumber
            );
        }

        $dbOrders = CSaleOrder::GetList(array("DATE_INSERT" => "DESC"), $arFilter, false, false, array());
        while ($arOrder = $dbOrders->Fetch()) {
            $orders[$arOrder['ID']] = $arOrder;
        }

        if (!empty($orders)) {
            $statusResult = \Bitrix\Sale\Internals\StatusLangTable::getList(
                array(
                    'order' => array('STATUS.SORT' => 'ASC'),
                    'filter' => array('STATUS.TYPE' => 'O', 'LID' => 'ru'),
                    'select' => array('STATUS_ID', 'NAME', 'DESCRIPTION'),
                )
            );

            while ($stat = $statusResult->fetch()) {
                $status[$stat['STATUS_ID']] = $stat['NAME'];
            }

            $i = 0;
            $result = [];
            foreach ($orders as $ordId => $order) {
                $result[$i] = [
                    "id" => $order['ACCOUNT_NUMBER'],
                    "code" => $order['STATUS_ID'],
                    "message" => $status[$order['STATUS_ID']],
                    "key" => $this->key
                ];

                $i++;
            }
        }

        return $result;
    }

    public function createLog($text, $body = [])
    {
        file_put_contents(
            $_SERVER["DOCUMENT_ROOT"] . "/logs/webhook/" . date("Ymd") . "_history_error.txt",
            date("Y.m.d H:i:s") . "\n" . get_class($this) . ": " . $text . "\n" . print_r($body, 1) . "\n",
            FILE_APPEND
        );
    }
}

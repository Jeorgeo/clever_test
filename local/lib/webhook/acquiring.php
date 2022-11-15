<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
$dir = '/local/lib/webhook/cloudpayments';
$classesCloudPayments = [
    'CloudPayments\Manager' => $dir.'/Manager.php',
    'CloudPayments\Model\Transaction' => $dir.'/Model/Transaction.php',
    'CloudPayments\Model\Required3DS' => $dir.'/Model/Required3DS.php',
    'CloudPayments\Exception\BaseException' => $dir.'/Exception/BaseException.php',
    'CloudPayments\Exception\PaymentException' => $dir.'/Exception/PaymentException.php',
    'CloudPayments\Exception\RequestException' => $dir.'/Exception/RequestException.php',
];

Bitrix\Main\Loader::registerAutoLoadClasses(null, $classesCloudPayments);
use \Bitrix\Sale\Order;

class Acquiring extends ApiAbstract
{
	public $apiName = 'acquiring';
	private $orderId;
	private $command;

	protected function createAction()
	{
        global $DB;
        $this->getJsonData();
		$publicKey = 'pk_185eed9f2e1920099901cc3d90519';
		$privateKey = 'fc32861be1a5a1c397020881ba8cd60c';

        if ($this->command === 'create' && !empty($this->orderId)) {
        	
            $orderModel = [];
            $order = Order::loadByAccountNumber($this->orderId);
            if (!empty($order) && $order->getField('STATUS_ID') === 'PL') {
	
				$basket = (\Bitrix\Sale\Order::load($order->getId()))->getBasket();
				foreach ($basket as $basketItem) {
					$items[] =
						[
							"label" => $basketItem->getField("NAME"),
							"price"  => round($basketItem->getField("PRICE"),2),
							"quantity" => $basketItem->getQuantity(),
							"amount" => $basketItem->getField("PRICE") * $basketItem->getQuantity(),
							"vat" => 10, //ставка НДС
							"measurementUnit" => "шт" //единица измерения
						];
				}
	
				$shipmentCollection = $order->getShipmentCollection();
				$shipment = $shipmentCollection[0];
				$delivePrice = (int)$shipment->getField('PRICE_DELIVERY');
				if ($delivePrice > 0) {
					$items[] =
						[
							"label" => "Доставка",
							"price"  => round($delivePrice,2),
							"quantity"=> 1,
							"amount" => round($delivePrice,2),
							"vat" => 20, //ставка НДС
						];
				}
	
				$dataForCheck = [
					"cloudPayments" => [
						"CustomerReceipt" => [
							"Items" => $items,
							"isBso" => false,
						]
					]
				];
				
                $amount = $order->getField("PRICE");
                $currency = 'RUB';
                $descr = 'Оплата заказа №' . $order->getField("ACCOUNT_NUMBER");
                $email = '';
                $params = [
                    'InvoiceId' => $order->getId(), // указывать ID заказа,  а не номер !!!
                    'SuccessRedirectUrl' => 'https://www.clever-media.ru/pay/success/',
                    'FailRedirectUrl' => 'https://www.clever-media.ru/pay/failure/',
					'JsonData' => $dataForCheck
                ];

                $client = new \CloudPayments\Manager($publicKey, $privateKey);
                $orderModel = $client->orderCreate($amount, $currency, $descr, $email, $params);
                
                if (!empty($orderModel['Id'])) {
                    $res = $this->findLinkForPay($order->getField("ACCOUNT_NUMBER"));
                    if (!$res) {
                        $saveStatusStr = "INSERT INTO `hl_orders_payment` (`UF_ORDER_NUM`, `UF_ALREADY_SHOW`, `UF_DATE_INSERT`, `UF_PAYMENT_ID`) VALUES ('" . $order->getField(
                                "ACCOUNT_NUMBER"
                            ) . "', '1', '"
                            . date("d/m/Y H:i:s") . "', '" . $orderModel['Id'] . "');";
                        $DB->Query($saveStatusStr);
                    } else {
                        $updateStatusStr = "UPDATE `hl_orders_payment` SET `UF_DATE_INSERT` = '" . date('d/m/Y H:i:s', time())  . "', `UF_PAYMENT_ID`= '" . $orderModel['Id'] ."' WHERE `ID` = ". $res['ID'] .";";
                        $DB->Query($updateStatusStr);
                    }

                    $this->createLog('Оплата онлайн', $orderModel);

                    $link =
                        [
                            "success" => true,
                            "paymentId" => $order->getField("ACCOUNT_NUMBER"),
                            "paymentUrl" => $orderModel['Url'],
                            "successRegex" => "(.+)/pay/success/",
                            "failureRegex" => "(.+)/pay/failure/"
                        ];

                    $this->createLog('Оплата ответ', $link);

                    return $this->response($link, 200);
                }
            } else {
                $link =
                    [
                        "success" => false,
                        "paymentId" => '',
                        "paymentUrl" => '',
                        "successRegex" => '',
                        "failureRegex" => '',
                        "error" => "Заказ нельзя оплатить онлайн."
                    ];

                $this->createLog('Оплата нельзя оплатить заказ', $link);

                return $this->response($link, 200);
            }
            return;
        }
		
		if ($this->command === 'applepay' && !empty($this->orderId)) {
			
			$orderModel = [];
			$order = Order::loadByAccountNumber($this->orderId);
			if (!empty($order) && $order->getField('STATUS_ID') === 'PL') {
				
				$basket = (\Bitrix\Sale\Order::load($order->getId()))->getBasket();
				foreach ($basket as $basketItem) {
					/**
					 * @var $basketItem \Bitrix\Sale\BasketItem
					 */
					$items[] =
						[
							"label" => $basketItem->getField("NAME"),
							"price"  => round($basketItem->getField("PRICE"),2),
							"quantity" => $basketItem->getQuantity(),
							"amount" => $basketItem->getField("PRICE") * $basketItem->getQuantity(),
							"vat" => 10, //ставка НДС
							//"method" => 0, // тег-1214 признак способа расчета - признак способа расчета
							//"object" => 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
							"measurementUnit" => "шт" //единица измерения
						];
				}
				
				$shipmentCollection = $order->getShipmentCollection();
				$shipment = $shipmentCollection[0];
				$delivePrice = (int)$shipment->getField('PRICE_DELIVERY');
				if ($delivePrice > 0) {
					$items[] =
						[
							"label" => "Доставка",
							"price"  => round($delivePrice,2),
							"quantity"=> 1,
							"amount" => round($delivePrice,2),
							"vat" => 20, //ставка НДС
						];
				}
				
				$dataForCheck = [
					"cloudPayments" => [
						"CustomerReceipt" => [
							"Items" => $items,
							"isBso" => false,
						]
					]
				];
				
				$amount = $order->getField("PRICE");
				$currency = 'RUB';
				$descr = 'Оплата заказа №' . $order->getField("ACCOUNT_NUMBER");
				$ip = $_SERVER['SERVER_ADDR']; // пока прописываю IP сервера, т.к. IP пользователей приложений не известны
				$crypto = $this->requestParams['applePayData'];
				$params = [
					'InvoiceId' => $order->getId(), // указывать ID заказа,  а не номер !!!
					'JsonData' => $dataForCheck
				];
				
				$client = new \CloudPayments\Manager($publicKey, $privateKey);
				$orderModel = $client->ApplePayCreate($amount, $currency, $descr, $ip, $crypto, $params);
				$this->createLog('Оплата ответ Cloudpayments ', $orderModel);
/*				if (!empty($orderModel['Id'])) {
					$statusStr = 'SELECT * FROM `hl_orders_payment` WHERE `UF_ORDER_NUM`="' . $order->getField(
							"ACCOUNT_NUMBER"
						) . '" ORDER BY id DESC LIMIT 1';
					$res = $DB->Query($statusStr)->Fetch();
					if (!$res) {
						$saveStatusStr = "INSERT INTO `hl_orders_payment` (`UF_ORDER_NUM`, `UF_ALREADY_SHOW`, `UF_DATE_INSERT`, `UF_PAYMENT_ID`) VALUES ('" . $order->getField(
								"ACCOUNT_NUMBER"
							) . "', '1', '"
							. date("d/m/Y H:i:s") . "', '" . $orderModel['Id'] . "');";
						$DB->Query($saveStatusStr);
					} else {
						$updateStatusStr = "UPDATE `hl_orders_payment` SET `UF_DATE_INSERT` = '" . date('d/m/Y H:i:s', time())  . "', `UF_PAYMENT_ID`= '" . $orderModel['Id'] ."' WHERE `ID` = ". $res['ID'] .";";
						$DB->Query($updateStatusStr);
					}
					
					$this->createLog('Оплата онлайн', $orderModel);
					
					$link =
						[
							"success" => true,
							"paymentId" => $orderModel['Id'],
							"paymentUrl" => $orderModel['Url'],
							"successRegex" => "(.+)/pay/success/",
							"failureRegex" => "(.+)/pay/failure/"
						];
					
					$this->createLog('Оплата ответ', $link);
					
					return $this->response($link, 200);
				}*/
				return;
			} else {
				$link =
					[
						"success" => false,
						"paymentId" => '',
						"paymentUrl" => '',
						"successRegex" => '',
						"failureRegex" => '',
						"error" => "Заказ нельзя оплатить онайн."
					];
				
				$this->createLog('Оплата нельзя оплатить заказ', $link);
				
				return $this->response($link, 200);
			}
			return;
		}

        if ($this->command === 'capture' && !empty($this->requestParams['paymentId'])) {
			if (is_numeric($this->requestParams['paymentId'])) {
				// нам отдали № заказа по нашей новой технологии
				$res = $this->findLinkForPay($this->requestParams['paymentId']);
			} else {
				// старый метод, нам передали ключ для оплаты сформированный ранее
				$res = $this->findLinkForPayOld($this->requestParams['paymentId']);
			}

            if ($res['UF_ORDER_NUM'] && !empty($res['UF_PAYMENT_ID'])) {
                $statusPay = 'SELECT `ORDER_ID`, `PAID`, `PAY_VOUCHER_NUM` FROM `b_sale_order_payment` WHERE `ACCOUNT_NUMBER`="' . $res['UF_ORDER_NUM'] . '/1" ORDER BY id DESC LIMIT 1';
                $resPay = $DB->Query($statusPay)->Fetch();

                if ($resPay['PAID'] === 'Y') {
                    $return =                     [
                        "success" => true,
                        "paymentId" => $this->requestParams['paymentId'],
                        "paymentCaptured" => true
                    ];
                    $this->createLog('Оплата ответ статуса 1', $return);
                    return $this->response($return, 200);
                }
            }

            $return = [
                "success" => true,
                "paymentId" => $this->requestParams['paymentId'],
                "paymentCaptured" => false
            ];

            $this->createLog('Оплата ответ статуса 3', $return);
            return $this->response($return, 200);
        }

        $this->createLog('Оплата ответ 2', $this->requestParams);
        return ;
	}

	private function getJsonData()
	{
	    $this->command = $this->requestParams['command'];
	    $this->orderId = $this->requestParams['orderId'] ?: 0;
	}



	private function findLinkForPay(string $orderNomber) {
		global $DB;
		$statusStr = 'SELECT * FROM `hl_orders_payment` WHERE `UF_ORDER_NUM`="' . $orderNomber . '" ORDER BY id DESC LIMIT 1';
		$res = $DB->Query($statusStr)->Fetch();
		return $res;
	}
	
	private function findLinkForPayOld(string $key) {
		global $DB;
		$statusStr = 'SELECT * FROM `hl_orders_payment` WHERE `UF_PAYMENT_ID`="' . $key . '" ORDER BY id DESC LIMIT 1';
		$res = $DB->Query($statusStr)->Fetch();
		return $res;
	}
	

	protected function indexAction()
	{
		// TODO: Implement indexAction() method.
	}

	protected function viewAction()
	{
		// TODO: Implement viewAction() method.
	}

	protected function updateAction()
	{
		// TODO: Implement updateAction() method.
	}

	protected function deleteAction()
	{
		// TODO: Implement deleteAction() method.
	}
	
}

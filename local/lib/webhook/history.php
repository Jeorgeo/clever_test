<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/elementpropertytable.php");


class History extends ApiAbstract
{
	public $apiName = 'history_order';
	
	private $userIdentifier;
	private $user;
	private $externalUserId;
	
	protected function createAction()
	{
//        return $this->response([]);
		
		$this->getJsonData();
		$orders = $this->getOrderList();
		
		if (!empty($orders)) {
			$this->createLog('History_order ответ: ', 'Есть список заказов');
			//$this->createLog('History_order ответ: ', $orders);
			return $this->response(
				['orders' => $orders],
				200
			);
		}
		$this->createLog('History_order ответ: ', 'Нет заказов!');
		return $this->response([]);
	}
	
	private function getJsonData()
	{
		$this->userIdentifier = $this->requestParams['userIdentifier'] ? (int)$this->requestParams['userIdentifier'] : 0;
		$this->externalUserId = 0; //  пользователя нет
		
		return true;
	}
	
	protected function checkUser($login = '', $password = '', $UID = '')
	{
		GLOBAL $USER;
		$user = CUser::GetByID($this->userIdentifier)->Fetch();
		
		if ($user) {
			if ($USER->Authorize($user['ID']) === true) { // нет ошибок и пользователь существует
				$this->externalUserId = $user['ID'];
				$this->user = $user;
				
				return true;
			}
		}
		
		return false;
	}
	
	private function getOrderList() {
		$result = [];
		$orders = [];
		if ($this->userIdentifier > 0) {
			CModule::IncludeModule("sale"); // подключение модуля продаж
			$arFilter = array(
				"USER_ID" => $this->userIdentifier
			);
			$dbOrders = CSaleOrder::GetList(array("DATE_INSERT" => "DESC"), $arFilter, false, false, array());
			while ($arOrder = $dbOrders->Fetch()) {
				$orders[$arOrder['ID']] = $arOrder;
			}

			$whereProps = [
				"=ORDER_ID" => array_keys($orders),
				"=ORDER_PROPS_ID" => [101,1,3,20]
			];

			$orderProp = \Bitrix\Sale\Internals\OrderPropsValueTable::getList(
				[
					'select' => ['ORDER_ID', 'ORDER_PROPS_ID', 'VALUE'],
					'filter' => $whereProps,
					'limit' => 1000,
					'order' => ['ID' => 'DESC'],
				]
			);
			while ($op = $orderProp->fetch()) {
				//имя
				if ($op['ORDER_PROPS_ID'] == 1) {
					$orders[$op['ORDER_ID']]['buyerName'] = $op['VALUE'];
				}
				//email
				if ($op['ORDER_PROPS_ID'] == 20) {
					$orders[$op['ORDER_ID']]['buyerEmail'] = $op['VALUE'];
				}
				//email
				if ($op['ORDER_PROPS_ID'] == 3) {
					$orders[$op['ORDER_ID']]['buyerPhone'] = $op['VALUE'];
				}
				if ($op['VALUE'] == 'BOX' && $op['ORDER_PROPS_ID'] == 101) {
					unset($orders[$op['ORDER_ID']]);
				}
			}
			
			
			if (!empty($orders)) {
				$dbBasketItems = CSaleBasket::GetList(
					array(),
					array("@ORDER_ID" => array_keys($orders)),
					false,
					false,
					array()
				);
				while ($arItems = $dbBasketItems->Fetch()) {
					$baskets[$arItems['ORDER_ID']][$arItems['PRODUCT_ID']] = $arItems;
				}
				
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
					$list = [];
					foreach ($baskets[$ordId] as $basket) {
						$list[] = [
							"privateId" => $basket['PRODUCT_ID'],
							"name" => $basket['NAME'],
							"price" => (int)$basket['PRICE'],
							"quantity" => (int)$basket['QUANTITY'],
							//            "discount" => $basket[''],
							"subtotal" => $basket['PRICE'] * $basket['QUANTITY'],
							"image" => " "
						];
					}
					$orderPrice = (int)$order['PRICE'];
					$deliveryPrice = (int)$order['PRICE_DELIVERY'];
					$basketPrice = $orderPrice - $deliveryPrice;
					$result[$i] = [
						"appliedDiscount" => $deliveryPrice,
						"id" => $order['ACCOUNT_NUMBER'],
						"createdOn" => strtotime($order['DATE_INSERT']),
						"status" => $status[$order['STATUS_ID']],
						"price" => (int)$order['PRICE'],
						"onlinePaymentRequired" => $order['STATUS_ID'] == 'PL' ? true : false,
						'retryPaymentMethod' => $order['STATUS_ID'] == 'PL' ? '10' : null,
						"items" => $list,
						"updatedOn" => strtotime($order['DATE_UPDATE']),
						"usedBonuses" => 0
					];
					if($order['STATUS_ID'] == 'PL'){
						$result[$i]['publicPaymentDetails'] = ['paid'=> false,'paymentComment'=>"Оплата картой"];
					}
					$result[$i]['publicDeliveryDetails'] = [
															"buyerName" => $order['buyerName'],
															"buyerEmail" => $order['buyerEmail'],
															"buyerPhone" => '+'.Strclass::pphone($order['buyerPhone']),
															"deliveryDate" => null,
															"details" => in_array($order['DELIVERY_ID'],[58,57]) ? "Доставка в пункт самовывоза" : "Доставка курьером или почтой",
															"title" => in_array($order['DELIVERY_ID'],[58,57]) ? "Доставка в пункт самовывоза" : "Доставка курьером или почтой"
															]
					;
					
					$i++;
				}
			}
		}
		//$this->createLog(print_r($result,true), date("H:i:s"));
		return $result;
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

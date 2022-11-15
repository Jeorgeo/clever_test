<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/orderActions.php");

use \Bitrix\Sale\Order;


class SaveOrder extends ApiAbstract
{
	public $apiName = 'save_order';
	
	
	public const COURIER_PRIORITY = [64, 62, 59]; //  по важности, впереди доставка приоритетная
	private $device;
	private $installId;
	private $order;
	private $items;
	private $externalUserId;
	private $delivery;
	private $payment;
	private $currentOrder;
	private $userData;
	private $weight;
	private $adressFull;
	private $jsonAddress;
	private $zip;
	
	protected function createAction()
	{
		GLOBAL $USER, $DB;
		
		$jsonData = $this->getJsonData();
		
		// есть ошибки в полученном json прекращаем и отдаем результат
		if (is_array($jsonData)) {
			$this->createLog('Ошибка в полученном json. ', $this->requestParams);
			return $this->response($jsonData);
		}
//		$this->createLog('Time: ', date("H:i:s"));
		if (empty($this->items)) {
			$text = "Отсутвует корзина в заказе. ";
			$this->createTelegram($this->order['email'] . ' - ' . $text . ' - ' . $this->order['uuid']);
			$this->createLog($text, $this->requestParams);
			
			return $this->response(["message" => $text]);
		}
		
		// ищем зареганого пользователя по данным если есть
		$flagAuth = false;
		if (!empty($this->externalUserId)) {
			$userId = $this->externalUserId;
			$flagAuth = true;
		} else {
			$login = !empty($this->userData['email']) ? strtolower(trim($this->userData['email'])) : $this->userData['phone'];
			$userId = $this->checkUser($login, '', $this->installId);
		}
		
		$fuserId = $this->getMobileFuserId($this->installId, $userId);
		
		if (!empty($userId)) {
			$USER->Authorize($userId);
		}
		
		if (empty($fuserId) || $fuserId === 0) {
			$fuserId = Bitrix\Sale\Fuser::getId(true);
		}
		
		$this->createLog("cookie SAVEORDER: " . print_r($_COOKIE, 1) . "  UID: " . $this->installId . "  FUSER: " . $fuserId);
		$this->createLog('Time3: ', date("H:i:s"));
		
		// очищаем корзину пользователя
		// если товаров в корзине не больше 20, а то вешается сервер
		if (!empty($fuserId) && count($this->items) <= 20) {
//		if (!empty($fuserId)) {
			$sql = "delete from b_sale_basket WHERE FUSER_ID = " . $fuserId . " AND ORDER_ID IS NULL";
			$delBasket = $DB->Query($sql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			$this->createLog('DELETE: ', $sql);
		}
		
		$basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, \Bitrix\Main\Context::getCurrent()->getSite());
		// проверяем пустую корзину (если повторный заказ) заполняем корзину из данных что пришли.
		if (count($basket->getQuantityList()) === 0) {
			$itemList = $this->items;
			$aaa = count($itemList) ;
			if (!empty($itemList)) {
				foreach ($itemList as $item) {
					$params['ID'] = $item['privateId'];
					$params['fuserId'] = $fuserId;
					$params['QUANTITY'] = $item['quantity'];
					CartAction::addItem($params);
				}
			}
			$this->createLog('Time3-1 добавили корзину товаром: ' . $aaa, date("H:i:s"));
		}
		
		$basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, \Bitrix\Main\Context::getCurrent()->getSite());
		
		$this->weight = $basket->getWeight();
		unset($basket);
		
		
		// определяем какой службой лучше отправить.
		// пока только курьерка и ПР
		// применяем промокод чтобы расчитать доставку
		if (CCatalogDiscountCoupon::IsExistCoupon((string) $this->order['promocode'])) {
			$resCupon = CCatalogDiscountCoupon::SetCoupon((string) $this->order['promocode']);
		}
		$checkDelivery = $this->getBestDelivery();
		$realPrice = DiliveryMap::getDeliveryPrice($checkDelivery, $checkDelivery['price_real'], (int) $this->order['price']);
		$checkDelivery = array_merge($checkDelivery, $realPrice);
		$this->delivery[1] = $checkDelivery['deliveryId'];
		
		
		$dataInOrder = array_merge($checkDelivery, ['fuserId' => $fuserId, 'flagAuth' => $flagAuth]);
		$order = $this->createOrder(['chekBestDelivery' => $dataInOrder]);
		
		// нет ошибок и вернулся объект заказа
		if (is_object($order)) {
			$this->currentOrder = $order;
			$this->createTelegram('Создания заказа. ' . $this->currentOrder->getField('ACCOUNT_NUMBER'));
			$this->createLog('Time 999: ', date("H:i:s"));
			$this->createLog(
				"Заказ # {$this->currentOrder->getField(
						'ACCOUNT_NUMBER'
					)} сохранен ",
				$this->orderResponce(
					"Ваш заказ #" . $this->currentOrder->getField('ACCOUNT_NUMBER') . " оформлен.",
					true,
					[]
				)
			);
			
			return $this->response(
				$this->orderResponce(
					"Ваш заказ #" . $this->currentOrder->getField('ACCOUNT_NUMBER') . " оформлен.",
					true,
					[]
				),
				200
			);
		} else {
			$this->createTelegram(
				'Ошибка создания заказа. ' . $order['message'] . ' - UID ЗАКАЗА: ' . $this->order['uuid']
			);
			
			$this->createLog(
				"Ошибка создания заказа",
				$this->orderResponce(
					'Возникла ошибка при создания заказа.',
					false,
					['1', $order['message']]
				)
			);
			
			return $this->response(
				$this->orderResponce(
					'Возникла ошибка при создания заказа.',
					false,
					['1', $order['message']]
				),
				200
			);
		}
	}
	
	private function getBestDelivery() {
		// выбираем доставку по адресу, сначала просто курьерку, потом ПР
		// ПВЗ вызываем здесь же
		
		if ($this->delivery[1] === 'courier' || $this->delivery[1] === 'regular') {
			return $this->getCourier();
		}
		
		return $this->getPVZ();
	}
	
	
	private function getCourier()
	{
		foreach (self::COURIER_PRIORITY as $id) {
			$arr =[
				'adress' => $this->adressFull,
				'deliveryService' => $id,
				'amount' => (int) $this->order['price'], //стоимость заказа
				'weight' => $this->weight
			];
			
			$result = DiliveryMap::getAllDataAdress($arr, 3);
			
			if ($result['status'] == 'ok') {
				$result['deliveryId'] = $id;
				$result['deliverymethod_id'] = $id;
				$result['system-comment'] = $id == 59 ? 'ВАЖНО!!! ДОСТАВКА КУРЬЕРОМ НЕ ВОЗМОЖНА!!! Сообщение сгенерировано приложением.' :'' ;
				break;
			} else {
				$result = [];
			}
		}
		
		if (empty($result)) {
			$this->createLog('НЕТ ДОСТАВКИ ПРИ ОФОРМЛЕНИЯ КУРЬЕРОМ. ПЕРЕНОСИТЬ ЗАКАЗ НА САЙТ РУКАМИ ИЗ АДМИНКИ ПРИЛОЖЕНИЯ.', $this->requestParams);
			$this->createTelegram('НЕТ ДОСТАВКИ (КУРЬЕРОМ) ПРИ СОХРАНЕНИИ ЗАКАЗА. ОБРАБАТЫВАТЬ РУКАМИ ИЗ АДМИНКИ ПРИЛОЖЕНИЯ. ID приложения - ' . $this->installId);
		}
		
		return $result;
	}
	
	private function getPVZ()
	{
		$pointId = explode('/', $this->order['pickupLocationId']);
		$params = [
			'id' => $pointId[1],
			'service' => $pointId[0], // $this->delivery[1], Пяторочка пока, как передавать
			'price' => $this->order['price']
		];
		$return = json_decode(DiliveryMap::getPointData($params), true);
		
		if ($return['status'] == 'error') {
			$this->createLog('НЕТ ДОСТАВКИ ПРИ ОФОРМЛЕНИЯ ПВЗ. ПЕРЕНОСИТЬ ЗАКАЗ НА САЙТ РУКАМИ ИЗ АДМИНКИ ПРИЛОЖЕНИЯ.', $this->requestParams);
			$this->createTelegram('НЕТ ДОСТАВКИ (ПВЗ) ПРИ СОХРАНЕНИИ ЗАКАЗА. ОБРАБАТЫВАТЬ РУКАМИ ИЗ АДМИНКИ ПРИЛОЖЕНИЯ. ID приложения - ' . $this->installId);
		} else {
			$maxDays = $return['result']['max_days'];
			$return = $return['result']['priceData'];
			$return['deliveryId'] =  $pointId[0];
			$return['deliverymethod_id'] = $pointId[0];
			$return['locality'] = $pointId[1];
			$return['maxDayPost'] = $maxDays;
			$return['system-comment'] = '';
		}
		
		return $return;
	}
	
	private function getJsonData()
	{
		$this->device = $this->requestParams['device'] ? $this->requestParams['device']['platform'] : '';
		$this->installId = $this->requestParams['installId'] ? $this->requestParams['installId'] : '';
		$this->order = $this->requestParams['orders'] ? $this->requestParams['orders'] : '';
		
		//	if (empty($this->device) || empty($this->installId) || empty($this->order)) {
		if (empty($this->installId) || empty($this->order)) {
			
			$this->createLog('Отсутсвуют входные данные', $this->requestParams);
			$this->createTelegram('Отсутсвуют входные данные' . $this->order['uuid']);
			
			return [
				'messages' => 'Отсутсвуют данные для заказа',
				'orders' => [
					"success" => false,
					"errorCode" => 1,
					"errorMessage" => 'Отсутсвуют данные для заказа'
				]
			];
		}
		
		if (count($this->order) > 1) {
			$this->createTelegram('Количество заказаов больше 1' . $this->order[0]['uuid']);
			$this->createLog('Количество заказаов больше 1', $this->requestParams);
		}
		
		$this->externalUserId = 0; // новый пользователь
		foreach ($this->order as $order) {
			if (!empty($order['externalUserId'])) {
				$this->externalUserId = (int)$order['externalUserId'];
			}
			
			if (!empty($order['items'])) {
				$this->items = $order['items'];
			} else {
				return [
					'messages' => 'Отсутсвует корзина в заказе',
					'orders' => [
						"success" => false,
						"errorCode" => 1,
						"errorMessage" => 'Отсутсвует корзина в заказе'
					]
				];
			}
			
			$this->delivery = explode('/', $order['delivery']);
			if (isset($this->delivery[1])) {
				$cur = explode('-', $this->delivery[1]);
				if (count($cur) > 1) {
					$this->delivery[1] = $cur[0];
					$this->delivery[2] = (int) $cur[1];
				}
			}
			
			$this->payment = explode('/', $order['payment']);
			if ($this->payment[1] == 111) {
				$this->payment[1] = 10;
			}
			
			$this->order = $order;
			$this->order['promocode'] = !empty($order['promocode']) ? mb_strtoupper(trim(strip_tags(stripslashes($order['promocode'])))) : '';
			
			// проверяем промокд в таблице с купонами если пустой промокод
			if (empty($this->order['promocode'])) {
				$filter = ['=UF_UID' => $this->installId];
				$row = HlClass::getOrderSource(self::MOBILE_USSER_PROMO, $filter);
				if (!empty($row['UF_PROMOCODE'])) {
					$this->order['promocode'] = $row['UF_PROMOCODE'];
				}
			}
			
			break;
		}
		
		$this->userData = ['email' => $this->order['email'], 'phone' => $this->order['phone']];
		
		$adrComponent = (!empty($this->order['addressComponents']['postalCode']) ? $this->order['addressComponents']['postalCode'] . ', ' : '') . trim($this->order['addressComponents']['valueAddressFull']);
		$adrData = (!empty($this->order['addressData']['zip']) ? $this->order['addressData']['zip'] . ', ' : '') . trim($this->order['addressData']['value']);
		$adrStr = trim($this->order['address']);
		
		$this->adressFull = strlen($adrComponent) > strlen($adrData) ? $adrComponent : (strlen($adrData) > strlen($adrStr) ? $adrData : $adrStr);
		$this->zip = strlen($adrComponent) > strlen($adrData) ? $this->order['addressComponents']['postalCode'] : (strlen($adrData) > strlen($adrStr) ? $this->order['addressData']['zip'] : '');
		
		return true;
	}
	
	private function createOrder($data = [])
	{
		$result = [];
		
		$fioArray = explode(' ', trim($this->order['name']));
		$fio = trim($this->order['name']);
		
		$this->createLog('Time7: ', date("H:i:s"));
		$fioUpdateData = ['FIO' => $fioArray, 'EMAIL' => trim($this->order['email'])];
		// временно посмотреть на время задержки
//		$this->updateFIO($fioUpdateData);
		
		$deliveriId = $this->delivery[1];
		$locality = $this->order['addressData']['zip'] ?: $this->order['addressComponents']['postalCode'];
		$city = $this->order['city'] != 'null' ? $this->order['city'] : $this->order['addressData']['settlementWithType'];
		
		if ($this->delivery[1] == 58 || $this->delivery[1] == 57) {
			if ($this->delivery[1] == 58) {
				$deliveriId = 1000; // на сайте так сделано
			}
			
			$locality = $data['chekBestDelivery']['locality'];
			$deliveryMapInfo = DiliveryMap::deliveryService()[$this->delivery[1]];
			$deliveryInfo = ($deliveryMapInfo["method"])::getInfoByDeliveryPointId($data['chekBestDelivery']['locality']);
			$this->createLog('Time8: ', date("H:i:s"));
			if ($this->delivery[1] == 57) {
				// вытаскиваем код ПП точки
				$locality = Pickpoint::getNumberByDeliveryPointId($data['chekBestDelivery']['locality']);
			}
			$this->createLog('Time9: ', date("H:i:s"));
			if (!empty($deliveryInfo['fullAddress'])) {
				$this->jsonAddress = Geo::getJsonOrderAdr(Geo::address($deliveryInfo['address_city'] .', '. $deliveryInfo['fullAddress'], 'pr1'));
				$this->adressFull = $deliveryInfo['address_city'] .','. $deliveryInfo['fullAddress'] . ', ' . $deliveryInfo['name'];
				$city = $deliveryInfo['address_city'];
			}
		} else {
			$this->jsonAddress = Geo::getJsonOrderAdr(Geo::address($this->adressFull, 'pr'));
			if ($this->jsonAddress['status'] == 'Ok') {
				if (empty($this->zip)) {
					$locality = $this->jsonAddress['index'];
					$this->adressFull = $locality . ' ' . $this->adressFull;
				}
			}
		}
		$this->createLog('Time10: ', date("H:i:s"));
		$params = [
			'mobile' => true,
			'coupon' => $this->order['promocode'] ?: '',
			'fio-form' => $fio,
			'shipment' => $this->delivery[1],
			'locality' => $locality,
			'locality_city' => $city,
			'addressInputAddress' => $this->adressFull,
			'paymentMethod' => $this->payment[1],
			'service_id' => $this->delivery[1],
			'delivery_id' => $deliveriId,
			'cardspro_service_info' => 'clever0', // TODO программа лояльности
			'user-data-email' => $this->order['email'],
			'user-data-phone' => $this->order['phone'],
			'ymclientid' => '',
			'gifts' => '',
			'user-comment' => trim($this->order['deliveryComment']),
			'delivery3k_price' => $data['chekBestDelivery']['price'],
			'locality_price' => $data['chekBestDelivery']['price'] ?: 0,
			'mobileUID' => $data['chekBestDelivery']['fuserId'],
			'source_order' => 'MOBI',
			'jsonAddress' => $this->jsonAddress,
			'post_day' => $data['chekBestDelivery']['maxDayPost'],
			'system-comment' => $data['chekBestDelivery']['system-comment']
		];
		
		$params = array_merge($params, $data);
		OrderAction::$chatId = 5;
		
		if ($data['fuserId']) {
			$obBasket = CSaleBasket::GetList(
				array(),
				array(
					'FUSER_ID' 	=> $data['fuserId'],
					'LID' 		=> 's1',
					'ORDER_ID' 	=> 'NULL',
					'MODULE' 	=> 'catalog'
				),
				false,
				false,
				array("ID")
			);
			
			if ($obBasket->SelectedRowsCount() > 0) {
				$this->createTelegram(
					'НЕТ КОРЗИНЫ В ЗАКАЗЕ! ' . 'FUSER: ' . $data['fuserId'] . ' UID: ' . $this->installId . ' ORDER_UID: ' . $this->order['uuid']
				);
			}
		}
		$this->createLog('Time11: ', date("H:i:s"));
		$newOrder = OrderAction::createOrder($params);
		
		$this->createLog('Time 888: ', date("H:i:s"));
		if ($newOrder['orderid']) {
			return Order::load($newOrder['orderid']);
		}
		
		/*		if ($newOrder['orderid']) {
					$order = Order::load($newOrder['orderid']);
					
					if ($this->payment[1] == 10) {
						$order->setField("STATUS_ID", "PL"); // если оплата картой пишем что Платёж не прошёл
						$order->save();
						$order = Order::load($newOrder['orderid']);
					}
					
					$this->createLog('Time 12: ', date("H:i:s"));
					// если курьерка и более 5000р налом, то оплата картой и опертаторы звонят
					if ($newOrder['price'] >= 5000 && $this->payment[1] == 1 && $this->delivery[1] == 60) {
						$order->setField("STATUS_ID", "PL"); // Платёж не прошёл !!!!!
						
						$paymentCollection = $order->getPaymentCollection();
						$paySystemService = Bitrix\Sale\PaySystem\Manager::getObjectById(10);
						$payment = $paymentCollection[0];
						$payment->setFields(
							array(
								'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
								'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
								"SUM" => $newOrder['price'],
								"CURRENCY" => $order->getCurrency(),
							)
						);
						
						$shipmentCollection = $order->getShipmentCollection();
						$shipment = $shipmentCollection[0];
						$shipment->setFields(
							array(
								'PRICE_DELIVERY' => 0,
								'CUSTOM_PRICE_DELIVERY' => 'Y'
							)
						);
						
						$this->createTelegram(
							"ПРИЛОЖЕНИЕ: ЗАКАЗ БОЛЬШЕ 5000р. - ID заказа " . $newOrder['ordernum'] . ' связаться с клиентом для оплаты. '
						);
						
						$this->payment[1] = 10;
						$order->save();
						$order = Order::load($newOrder['orderid']);
						
					}
					$this->createLog('Time 13: ', date("H:i:s"));
					// если наличка и курьер, то доставку оплачивает клиент полностью
					if ($this->payment[1] == 1 && $this->delivery[1] == 60 && (int)$order->getPrice() - (int)$order->getDeliveryPrice() < 2000) {
						$shipmentCollection = $order->getShipmentCollection();
						$shipment = $shipmentCollection[0];
						$shipment->setFields(
							array(
								//			'PRICE_DELIVERY' => $data['chekBestDelivery']['price_real'],
								'PRICE_DELIVERY' => $data['chekBestDelivery']['price'],
								'CUSTOM_PRICE_DELIVERY' => 'Y'
							)
						);
						
						$order->save();
						$order = Order::load($newOrder['orderid']);
					}
					
					$this->createLog('Time 14: ', date("H:i:s"));
					// если Онлайн оплата и больще 2000р заказ, доставка бесплатно
					// Кроме КЗ и Беларуси курьерки - там всегда 1900,при любой сумме заказа
					if (($this->payment[1] == 10 && (int)$order->getPrice() - (int)$order->getDeliveryPrice() >= 2000)
						&& ($this->delivery[1] != 54 || $this->delivery[1] != 55)
					) {
						$shipmentCollection = $order->getShipmentCollection();
						$shipment = $shipmentCollection[0];
						$shipment->setFields(
							array(
								'PRICE_DELIVERY' => 0,
								'CUSTOM_PRICE_DELIVERY' => 'Y'
							)
						);
						
						$order->save();
						$order = Order::load($newOrder['orderid']);
					}
					$this->createLog('Time 15: ', date("H:i:s"));
					
					
					// если больще 2000р заказ, доставка бесплатно
					if ((int)$order->getPrice() - (int)$order->getDeliveryPrice() >= 2000) {
						$shipmentCollection = $order->getShipmentCollection();
						$shipment = $shipmentCollection[0];
						$shipment->setFields(
							array(
								'PRICE_DELIVERY' => 0,
								'CUSTOM_PRICE_DELIVERY' => 'Y'
							)
						);
						
						$order->save();
						$order = Order::load($newOrder['orderid']);
					}
					$this->createLog('Time 16: ', date("H:i:s"));
		
		//			$ch = curl_init();
		//			curl_setopt($ch, CURLOPT_URL, "https://www.clever-media.ru/ajax/sendOrder.php");
		//			curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		//			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 550);
		//			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		//			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		//			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['order'=>$newOrder['orderid']]));
		//			curl_exec($ch);
		//			// не ждём ответа. точнее, не более 50 мс
		//			curl_close($ch);
					
					
					return $order;
				}*/
		
		// какие то ошибки при офорлении
		$this->createLog('Ошибки при создании заказа: ', $newOrder);
		return $newOrder;
	}
	
	private function orderResponce($message = '', $success = false, $error = [])
	{
		$errorOut = [];
		if (!empty($error) && !$success) {
			$errorOut = [
				'errorCode' => $error[0],
				'errorMessage' => $error[1]
			];
		}
		$orderPrice = !empty($this->currentOrder) && $success ? $this->currentOrder->getField('PRICE') : 0;
		$deliveryPrice = !empty($this->currentOrder) && $success ? $this->currentOrder->getField('PRICE_DELIVERY') : 0;
		$basketPrice = $orderPrice - $deliveryPrice;
		return
			[
				"message" => $message,
				"orders" => [array_merge(
					[
						"success" => $success,
						"id" => !empty($this->currentOrder) && $success ? $this->currentOrder->getField(
							'ACCOUNT_NUMBER'
						) : '',
						'price' => $basketPrice,
						'deliveryPrice' => $deliveryPrice,
						"publicId" => !empty($this->currentOrder) && $success ? $this->currentOrder->getField(
							'ACCOUNT_NUMBER'
						) : '',
						"uuid" => $this->order['uuid'],
					],
					$errorOut
				)]
			];
	}
	
	//Добавляеи или обновлять свойство ENUN в заказе
	private function addOrderPropertyEnum($code, $value, $order)
	{
		if (!strlen($code)) {
			return false;
		}
		if (CModule::IncludeModule('sale')) {
			$arProp = CSaleOrderProps::GetList(array(), array('CODE' => $code))->Fetch();
			$arOrderSourceValue = CSaleOrderPropsValue::GetList(
				array(),
				array(
					"ORDER_ID" => $order,
					"ORDER_PROPS_ID" => $arProp['ID']
				)
			)->Fetch();
			if ($arOrderSourceValue["ID"]) {
				CSaleOrderPropsValue::Update($arOrderSourceValue["ID"], array("VALUE" => $value));
			} else {
				CSaleOrderPropsValue::Add(
					array(
						'NAME' => $arProp['NAME'],
						'CODE' => $arProp['CODE'],
						'ORDER_PROPS_ID' => $arProp['ID'],
						'ORDER_ID' => $order,
						'VALUE' => $value,
					)
				);
			}
		}
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
	
	private function updateFIO($data)
	{
		$login = $data['EMAIL'];
		$user = CUser::GetByLogin($login)->Fetch();
		if (empty($user['NAME'])) {
			if (!empty($data['FIO'])) {
				$update['NAME'] = $data['FIO'][0];
				if (isset($data['FIO'][1])) {
					$update['LAST_NAME'] = $data['FIO'][1];
				}
				if (isset($data['FIO'][2])) {
					$update['SECOND_NAME'] = $data['FIO'][2];
				}
				
				$u = new CUser;
				$u->Update($user['ID'], $update);
				if(empty($u->LAST_ERROR)) {
					return true;
				}
			}
		}
		
		return false;
	}
}
<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/elementpropertytable.php");

class RenumOrder extends ApiAbstract
{
	public $apiName = 'renum_order';
	private $installId;
	private $userData;
	private $items;
	private $externalUserId;
	
	
	protected function createAction()
	{
		global $USER;
		$this->getJsonData();
		
		// ищем зареганого пользователя по данным если есть
		if (!empty($this->externalUserId)) {
			$userId = $this->externalUserId;
		} else {
			if (!empty($this->userData)) {
				$login = !empty($this->userData['email']) ? strtolower(trim($this->userData['email'])) : $this->userData['phone'];
				$userId = $this->checkUser($login, '', $this->installId);
			} else {
				$userId = 0;
			}
		}
		
		$fuserId = $this->getMobileFuserId($this->installId, $userId);
		
		if (!empty($userId)) {
			$USER->Authorize($userId);
		}
		
		if (empty($fuserId) || $fuserId === 0) {
			$fuserId = Bitrix\Sale\Fuser::getId(true);
		}
		
		$this->createLog("cookie: " . "  UID: " . $this->installId . "  FUSER: " . $fuserId);
		
		// сравниваем корзину с тем что пришло,
		// обновляем корзину исходя из текущего состояния
		$basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, \Bitrix\Main\Context::getCurrent()->getSite());
		$basketItems = $basket->getBasketItems();
		
		$itemList = $this->items;
		
		/*        // проверяем количество доступных товаров ,если 1 или меньше то удаляем из корзины
				$itemsCountIsNull = [];
		
				if (!empty($itemList)) {
					$propIdsCount = [
						477 => 'AMOUNT5',
					];
					$tmpCount = ElementPropertyTable::getList(
						[
							'select' => [
								'IB_PROP_ID' => 'IBLOCK_PROPERTY_ID',
								'IB_ID' => 'IBLOCK_ELEMENT_ID',
								'VALUE',
							],
							'filter' => [
								'=IBLOCK_ELEMENT_ID' => array_keys($itemList),
								'=IBLOCK_PROPERTY_ID' => array_keys($propIdsCount)
							],
						]
					);
		
					while ($propCount = $tmpCount->fetch()) {
						if ((int)$propCount['VALUE'] < 2) {
							$itemsCountIsNull[$propCount['IB_ID']] = $propCount['IB_ID'];
						}
					}
				}
				// удалим которые нельзя заказать
				$itemList = array_diff_key($itemList, $itemsCountIsNull);
				$this->items = $itemList;*/
		
		foreach($basketItems as $basketItem) {
			$productId = (int) $basketItem->getProductId();
			if (array_key_exists($productId, $itemList)) {
				if ((int) $basketItem->getQuantity() !== (int) $itemList[$productId]['quantity']) {
					$basketItem->setField('QUANTITY', $itemList[$productId]['quantity']);
				}
				unset($itemList[$productId]);
			} else {
				$basketItem->delete();
			}
		}
		
		$basket->save();
		// добавляем новые товары если их небыло в корзине, остались элементы в $itemList
		$errItems = [];
		if (!empty($itemList)) {
			foreach ($itemList as $item) {
				$params['ID'] = $item['privateId'];
				$params['fuserId'] = $fuserId;
				$params['QUANTITY'] = $item['quantity'];
				$result = CartAction::addItem($params);
				
				if (!empty($result['fail'])) {
					$errItems[$item['privateId']] = $item['privateId'];
				}
			}
		}
		
		$return['promo'] = OrderAction::setCoupon(
			[
				'coupon' => $this->requestParams['promocode'],
				'fuserId' => $fuserId,
				'mobile' => 1
			]
		);
		$return['promo']['cart_data'] = Basket::getCardData();
		
		$result = $this->cleanOutData($return, $errItems);
		
		$this->createLog('Renum_order ответ: ', print_r($result,1));
		
		return $this->response(
			$result,
			200
		);
	}
	
	private function getJsonData()
	{
		$this->installId = $this->requestParams['installId'];
		$this->userData = $this->requestParams['user'];

//        $login = !empty($this->userData['email']) ? $this->userData['email'] : $this->userData['phone'];
		
		if (trim($this->userData['email']) == 'none' && trim($this->userData['phone']) == 'none') {
			$this->userData = [];
		}
		
		$this->externalUserId = 0;
		if (!empty($this->requestParams['externalUserId'])) {
			$this->externalUserId = (int)$this->requestParams['externalUserId'];
		}
		
		if (!empty($this->requestParams['items'])) {
			$this->items = $this->requestParams['items'];
		}
		
		if (!empty($this->requestParams['items'])) {
			$this->items = array_column($this->requestParams['items'], null, 'privateId');
		}
		
		$this->requestParams['promocode'] = !empty($this->requestParams['promocode']) ? mb_strtoupper(trim(strip_tags(stripslashes($this->requestParams['promocode'])))) : '';
	}
	
	private function cleanOutData($data, $quantityNull) {
		$promo = $data['promo']['applied'] === 'Y' ? $this->requestParams['promocode'] : '';
		
		$basketOut = [];
		$basket = $data['promo']['cart_data'];
		
		foreach ($basket['ITEMS'] as $key => $item) {
			$basketOut[$item['PRODUCT_ID']] = [
				"id" => $item['PRODUCT_ID'],
				'name' => $item['NAME'],
				"price" => (int) $item['BASE_PRICE'],
				"discount" => $item['DISCOUNT'] * $item['QUANTITY'],
				"quantity" => $item['QUANTITY'],
				"subtotal" => (int) $item['BASE_PRICE']  * (int)$item['QUANTITY'] - (int)$item['DISCOUNT'] * (int)$item['QUANTITY'],
			];
		}
		
		foreach ($this->items as $val) {
			if (array_key_exists($val['privateId'], $quantityNull)) {
				if (!isset($basketOut[$val['privateId']])) {
					$basketOut[$val['privateId']] = $val;
				}
				$basketOut[$val['privateId']]['error'] = 'Товар не доступен для заказа';
			}
		}
		
		$total = 0;
		$discount = 0;
		foreach ($basketOut as $key => $res) {
			if (empty($res['error'])) {
				$total += (int) $res['subtotal'];
				$discount += (int) $res['discount'];
			}
		}
		
		$result = [
			"totalPrice" => $total,
			"appliedPromocode" => $promo ?? null,
			"discount" => $discount,
			"items" => array_values($basketOut)
		];
		
		// записываем промокод если применился в HL таблицу чтобы потом применить при оформлении
		if (!empty($promo)) {
			$filter = ['=UF_UID' => $this->installId];
			$row = HlClass::getOrderSource(self::MOBILE_USSER_PROMO, $filter);
			
			if (empty($row)) {
				$data = ['UF_UID' => $this->installId, 'UF_PROMOCODE' => trim($promo), 'UF_UPDATE' => new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s')];
				$row = HlClass::setOrderSource(self::MOBILE_USSER_PROMO, $data, 1);
			} else {
				$data = ['ID' => $row['ID'], 'UF_UID' => $this->installId, 'UF_PROMOCODE' => trim($promo), 'UF_UPDATE' => new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s')];
				$row = HlClass::setOrderSource(self::MOBILE_USSER_PROMO, $data, 2);
			}
		}
		
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
	
	private function def()
	{
		return '
	    {
            "totalPrice": 95000,
            "appliedPromocode": null,
            "discount": 0,
            "items": [
                {
                    "name": "Apple iPad (2018) 64Gb Wi-Fi, серебристый",
                    "id": "12345",
                    "price": 29990,
                    "discount": 0,
                    "quantity": 1,
                    "subtotal": 29990,
                    "bonuses": { "canSpend": 1990 }
                },
                {
                    "name": "Apple Mac Pro 2020",
                    "id": "80008",
                    "price": 5000000,
                    "discount": 1000,
                    "quantity": 1,
                    "bonuses": { "canSpend": 0 },
                    "deliveryGroups": ["express"]
                }
            ],
            "dynamicDiscounts": [
                { "id": "dd76656711", "name": "Скидка 1000 рублей на Apple Pencil при покупе iPad" },
                { "id": "dd1218888", "name": "Чехол Baseus в подарок при покупке iPad и Apple Pencil" },
                { "id": "dd771086", "name": "Чехол в подарок", "gifts": [{ "id": "42345", "quantity": 2 }] },        
                { "id": "dd771099", "name": "Подарок на выбор", "giftOptions": [ { "id": "87787", "quantity": 1 }, { "id": "1234567801", "quantity": 3} ] }
            ],
            "bonuses": {
                "canSpend": 1990,
                "willEarn": 1600
            }
        }
	    ';
	}
	
}

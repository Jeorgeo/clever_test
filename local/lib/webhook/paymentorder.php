<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/cart.php");

class PaymentOrder extends ApiAbstract
{
	public $apiName = 'payment';
    private $installId;
	private $delivery;
    private $weight;
    private $zip;
	private $adress;
	private $PaymentAvailable;

	protected function createAction()
	{
		$this->getJsonData();
		
        $fuserId = $this->getMobileFuserId($this->installId, 0);
        $fuserId = $fuserId ?: Bitrix\Sale\Fuser::getId(true);
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, \Bitrix\Main\Context::getCurrent()->getSite());
        $this->weight = $basket->getWeight();

        $this->createLog('ответ оплаты', print_r($this->def(),1));
        return $this->response(['payments' => $this->def()], 200);
	}

	private function getJsonData()
	{
        $this->installId = $this->requestParams['installId'];
		
		if (!empty($this->requestParams['items'])) {
			$this->items = $this->requestParams['items'];
		}

        $this->delivery = explode('/', $this->requestParams['deliveryId']);
		
        if ($this->delivery[1] === 'courier' || $this->delivery[1] === 'courier-63'  || $this->delivery[1] === 'courier-62') {
            $filter = ['=UF_UID' => $this->installId];
            $row = HlClass::getOrderSource(self::MOBILE_USER, $filter);
			
            if (!empty($row)) {
                $data = json_decode($row['UF_JSON'], true);
                $this->delivery = ['webhook', $data['id']];
                $this->zip = $data['index'] ?: 0;
				$this->adress = $data['adress'] ? $data['adress'] : 0;
				$this->PaymentAvailable = $data['PaymentAvailable'] ? $data['PaymentAvailable'] : 0;
				
            }
        }

		if (isset($this->delivery[1])) {
		    $cur = explode('-', $this->delivery[1]);
		    if (count($cur) > 1) {
                $this->delivery[1] = $cur[0];
                $this->delivery[2] = (int) $cur[1];
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

	function def()
	{
//        $result[] = [
//            'id' => "10",
//            'title' => "Оплата через оператора сайта",
//            'description' => "Оплата онлайн по ссылке присланой на электронную почту",
//            'type' => "card_on_delivery"
//        ];

        $result[] = [
            'id' => "10",
            'title' => "Оплата онлайн",
            'description' => "Бесплатная доставка от 2000 руб. при онлайн оплате.",
            'type' => "card"
        ];

        if ($this->delivery[1] === 'courier') {
            $fuserId = $this->getMobileFuserId($this->requestParams['installId'], 0);
            $total = 0;
//            if ($fuserId) {
//                $basket = Bitrix\Sale\Basket::loadItemsForFUser($fuserId, 's1');
//                $fuser = new \Bitrix\Sale\Discount\Context\Fuser($fuserId);
//                $discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, $fuser);
//                if ($discounts) {
//                    $discounts->calculate();
//                    $basketDiscont = $discounts->getApplyResult(true);
//
//					//$this->createLog('ответ оплаты', print_r($basketDiscont,1));
//
//                    $prices = $basketDiscont['PRICES']['BASKET']; // цены товаров с учетом скидки
//
//                    foreach ($prices as $price) {
//                        $sum[] = $price['PRICE'];
//                    }
//
//                    $total = array_sum($sum);
//                }
//            } else {
                foreach ($this->items as $item) {
                    $total += (int) $item['price'] *  (int) $item['quantity'];
//            }
            }
	
			if (isset($this->delivery[2]) && ($this->delivery[2] === 63 || $this->delivery[2] === 64)) { 
				// если есть индекс города для проверки возможности оплаты
		
			/*	if (!empty($this->adress)) {
					//$result_IML = IML::calculateCourier(['index' => $this->zip], $this->weight, $total)["0"];
					$VisualAttributes = DiliveryMap::deliveryService();
					$result = $VisualAttributes[$this->delivery[2]]['method']::calculateDelivery(
						[
							"dimension" => $dimension,
							'declared-value' => $total,
							"mass" => 1,
							"index-to" => $this->zip,
							"adress" => $this->adress
						],
						2
					);
					
					Strclass::telegram('АДРЕС11 '.print_r($result,1), 0);*/
					//$payAva = !empty($result['PaymentAvailable']) ? 1 : 0;
					
					// отключаем оплату наличнкой временно
					//$payAva = 0;
					if ($this->PaymentAvailable) {
						if ($total <= 5000 && $this->weight < 15) {
							$result[] = [
								'id' => "1",
								'title' => "Оплата курьеру",
								'description' => "Оплата курьеру при получении наличными или картой.",
								'type' => "cash"
							];
						}
					}
				//}
			}

            // для меня проверка оплаты
            if ($this->requestParams['installId'] == '6aa44be5-15df-4ef4-b79a-4c426286683c') {
                $result[] = [
                    'id' => "111",
                    'title' => "Оплата Apple Pay",
                    'description' => "Оплата Apple Pay",
                    'type' => "ios"
                ];
            }
        }
		return $result;
	}
}

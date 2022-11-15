<?php

// возвращаем список доставок всех доступных
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/saveorder.php");


class DeliveryOrder extends ApiAbstract
{
	public $apiName = 'delivery';

	private $apikey = '2fe43336-d9af-4407-8508-f4e911f3e526';
    private $installId;
	private $addressData;
	private $address;
	private $externalUserId;
	private $items;
	private $weight;
	private $price;
	
	const PRICE_DEVIVERY_FREE = 200000000;


	protected function createAction()
	{
        $inParams = $this->getJsonData();

        if (!$inParams) {
            $this->createLog('ответ доставки. Нет адреса доставки ', print_r($this->def(),1));
            return $this->response($this->def(), 200);
        }

        $fuserId = $this->getMobileFuserId($this->installId, 0);
        $fuserId = $fuserId ?: Bitrix\Sale\Fuser::getId(true);
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, \Bitrix\Main\Context::getCurrent()->getSite());
        $this->weight = $basket->getWeight();
		
		$this->price = 0;
		foreach ($this->items as $item) {
			$this->price += (int) $item['price'] *  (int) $item['quantity'];
		}

/*		if ($fuserId) {
			$fuser = new \Bitrix\Sale\Discount\Context\Fuser($fuserId);
			$discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, $fuser);
			
			$r = $discounts->calculate();
			if (!$r->isSuccess())
			{
			//	var_dump($r->getErrorMessages());
				$this->createLog('ответ доставки. ERROR 1 ', print_r($r->getErrorMessages(), 1));
			}
			
			$result = $r->getData();
			if (isset($result['BASKET_ITEMS']))
			{
				$r = $basket->applyDiscount($result['BASKET_ITEMS']);
				if (!$r->isSuccess())
				{
					//var_dump($r->getErrorMessages());
					$this->createLog('ответ доставки. ERROR 2 ', print_r($r->getErrorMessages(), 1));
				}
			}
			
			$this->createLog('ответ доставки. RRRRRRR ', print_r($r, 1));
			
			
			if ($discounts) {
				$discounts->calculate();
				$basketDiscont = $discounts->getApplyResult(true);
				$prices = $basketDiscont['PRICES']['BASKET']; // цены товаров с учетом скидки
				
				foreach ($prices as $price) {
					$sum[] = $price['PRICE'];
				}
				
				$this->price = array_sum($sum);
				$this->createLog('ответ доставки. PRICE 0 ', $this->price);
			}
		} else {
			foreach ($this->items as $item) {
				$this->price += (int) $item['price'] *  (int) $item['quantity'];
				$this->createLog('ответ доставки. PRICE 1', $this->price);
			}
		}
		$this->createLog('ответ доставки. PRICE 2', $this->price);
		$this->createLog('ответ доставки. PRICE 3', $basket->getPrice());
		$this->createLog('ответ доставки. PRICE 4', $basket->getBasePrice());
*/
		
		
		
        if ($this->weight < 15) {
            $pvz = $this->getPoinsLocations();
        }
        $return[] = $this->defCourier();

        if (!empty($pvz)) {
            $pricePvz = isset($pvz[0]['price']) ? (int)$pvz[0]['price'] : 199;
            $return[] =
                [
                    "id" => "pickup",
                    "title" => "Пункты самовывоза",
                    "description" => "Бесплатная доставка от 2000 руб.",
                    "type" => "pickup",
                    "price" => $pricePvz,
                    "min" => 3,
                    "timeLabel" => "От 3-х дней",
                    "locations" => $pvz
                ];

            if (!empty($pvz)) {
                $returnLog  = [$return[0], ['Тут точки ПВЗ. УБрал список ибо большой объем']];
            }
            $this->createLog('ответ доставки. ', print_r(["deliveries" => $returnLog],1));
            
            return $this->response(["deliveries" => $return], 200);
        }

        // пока просто отдаем курьерка / пвз, при оформлении будем делать проверки
        $this->createLog('ответ доставки по умолчанию', print_r($this->def(),1));
        return $this->response($this->def(), 200);
	}

	private function getJsonData()
	{
        $this->installId = $this->requestParams['installId'];

		$this->addressData = $this->requestParams['addressData'] ?: '';
		$this->externalUserId = $this->requestParams['externalUserId'] ?: '';

		$this->address = $this->requestParams['addressData']['value'] ?: $this->requestParams['addressData']['city'];

		if (!empty($this->requestParams['items'])) {
			$this->items = $this->requestParams['items'];
		}

        if (empty($this->addressData)) {
            $this->createLog('Отсутсвуют данные адреса доставки', $this->requestParams);
            return false;
        }

        return true;
	}

    private function getPoinsLocations() {
        $cityCoord = [
            round($this->requestParams['addressData']['lat'], 4),
            round($this->requestParams['addressData']['lon'], 4)
        ];

        if (empty($this->requestParams['addressData']['lat']) && empty($this->requestParams['addressData']['lon'])) {
            $requestQueryUrl = 'https://geocode-maps.yandex.ru/1.x/?apikey=' . $this->apikey . '&format=json&geocode=' . urlencode(
                    mb_strtolower(trim($this->address))
                ) . '&results=1';
            if (!empty($requestQueryUrl)) {
                $response = json_decode($this->getCity($requestQueryUrl));
                if ($response->response->GeoObjectCollection->metaDataProperty->GeocoderResponseMetaData->found > 0) {
                    $xy = explode(
                        ' ',
                        $response->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos
                    );

                    $cityCoord = [round($xy[0],4), round($xy[1],4)];
                }
            }
        }

        $cityRange = Geo::getDBTailCenter($cityCoord[0], $cityCoord[1]);

        if ($cityRange['status'] !== 1) {
            return [];
        }
		
		//

        $locations = [];
        $points = $this->DBpointsT($cityRange['result']);
		
		//зоны для ПВЗ с ценами
		$arrPrice =  $this->DBzonesT();
		//расчет веса
		$weightPlus = ceil($this->weight)-3;
		
        while ($row = $points->Fetch()) {
			if($row['UF_ID_SERVICE'] == 58 && !empty($arrPrice[58][$row['ZONE']]['plus']) && $weightPlus>0){$row['UF_PRICE'] = $row['UF_PRICE'] + $arrPrice[58][$row['ZONE']]['plus']*$weightPlus;}
			$row['UF_PRICE'] = DiliveryMap::ExDeliveryPrice($row['UF_PRICE'],$this->price);
/*			$priceCouponCheck =  DiliveryMap::getDeliveryCupon();
			if(array_key_exists($row['UF_ID_SERVICE'], $priceCouponCheck['deliveryDiscount'])) {
				$row['UF_PRICE'] = $priceCouponCheck['deliveryDiscount'][$row['UF_ID_SERVICE']];
			}*/
			
            $locations[] = [
                'id' => $row['UF_ID_SERVICE'] . '/' . $row['ID'],
                'title' => $row['UF_NAME'],
                'address' => $row['UF_ADRESS'],
                'city' => $this->requestParams['addressData']['city'],
                'time' => '',
                'subway' => '',
                'mall' => '',
                'lat' => round($row['UF_LAT'], 4),
                'lon' => round($row['UF_LON'], 4),
                'price' => $this->price >= self::PRICE_DEVIVERY_FREE ? 0 : $row['UF_PRICE'],
                'min' => 3,
                'timeLabel' => '',
            ];
        }

        return $locations;
    }
	
	private function DBzonesT(){
		 global $DB; $x=[];
		 $Sql = "SELECT zone as id, tarif_3kg as price, tarif_over_3kg as plus  FROM `fivepost_zone_price`";
		 $result = $DB->Query($Sql, false);
		  while ($row = $result->Fetch()){
			$x[58][$row['id']]=['price'=>$row['price'],'plus'=>$row['plus']];
			}
			return $x;
	}
	
    private function DBpointsT($arrPoints)
    {
        $obCache = new CPHPCache();
        $id = implode('_', $arrPoints);
        # проверяет если файл с кешем и не просит ли администратор принудительно сбросить кеш
        # при выполнении всех условий - берет данные из кеша
        if ($obCache->InitCache(86400, 'cacheLocations_' . $id, "/locations/")) {
            $vars = $obCache->GetVars();
            $result = $vars['result'];
        } # иначе делает новый
        else {
            global $DB;
            $hide = ' UF_HIDE <> 1 ';
            $service_id = '58, 57';
//            $hide = ' UF_HIDE = 0 ';
//            $service_id = '58';
            $weight = ' and UF_WEIGHT>' . DiliveryMap::getDeliveryBruttoWeight($this->weight);

            $pointsSql = 'SELECT * FROM `' . DiliveryMap::Table . '`  WHERE  ' .
                $hide .
                ($service_id ? ' and UF_ID_SERVICE IN(' . $service_id . ')' : '') .
                ' and UF_TAILX>' . $arrPoints['x1'] . ' and UF_TAILX<' . $arrPoints['x2'] .
                ' and UF_TAILY>' . $arrPoints['y1'] . ' and UF_TAILY<' . $arrPoints['y2'] .
                $weight .
                ' limit 10000';

            $result = $DB->Query($pointsSql, false);
            $obCache->EndDataCache(array('result' => $result));
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


	function defCourier() {
        if (!empty($this->requestParams['address'])) {
            foreach (SaveOrder::COURIER_PRIORITY as $id) {
                $arr = [
                    'adress' => $this->requestParams['addressData']['value'] ?: $this->requestParams['address'],
                    'deliveryService' => $id,
    //                'amount' => array_sum(array_column($this->items, 'subtotal')), //стоимость заказа
                    'amount' => $this->price, //стоимость заказа
                    'weight' => $this->weight
                ];

                if($id==59){$result = DiliveryMap::getAllDataAdress($arr, 3);}
				else{
				 $result = DiliveryMap::getMinPriceCourier($arr, 2);
				}

                if ($result['status'] == 'ok') {
					if($id==59){
					$result['deliveryId'] = $id;
								$result['deliverymethod_id'] = $id;
								$result['system-comment'] = $id == 59 ? 'ВАЖНО!!! ДОСТАВКА КУРЬЕРОМ НЕ ВОЗМОЖНА!!! Сообщение сгенерировано приложением.' :'' ;
					}else{
					 $result['deliverymethod_id'] = $result['deliveryId'];
					}
                    break;
                } else {
                    $result = [];
                }
            }

            if (!empty($result)) {
				
                $price = $result['price'] ?: $result['price_real'];
				if($result['deliveryId']!=59 && $price<300 && $price>300){$price=312;}
				//$price =  DiliveryMap::ExDeliveryPrice($price,$this->price);
/*				$priceCouponCheck =  DiliveryMap::getDeliveryCupon();
				if(array_key_exists($result['deliveryId'], $priceCouponCheck['deliveryDiscount'])) {
					$price = $priceCouponCheck['deliveryDiscount'][$result['deliveryId']];
				}*/
				
                $return = [
                    "id" => "courier-" . $result['deliveryId'],
                    "title" => "Доставка курьером",
                    "description" => "Бесплатная доставка от 2000 руб. при оплате онлайн",
                    "type" => "delivery",
                    "price" => $this->price >= self::PRICE_DEVIVERY_FREE ? 0 : (int)$price,
                    "min" => 3,
                    "timeLabel" => "От 3-х дней",
                    "index" => $this->requestParams['addressData']['zip'],
					"adress" =>  $this->requestParams['addressData']['value'],
					"PaymentAvailable" =>  $result['PaymentAvailable'],
                ];

                $filter = ['=UF_UID' => $this->installId];
                $row = HlClass::getOrderSource(self::MOBILE_USER, $filter);

                if (empty($row)) {
                    $data = ['UF_UID' => $this->installId, 'UF_JSON' => json_encode($return), 'UF_UPDATE' => new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s')];
                    $row = HlClass::setOrderSource(self::MOBILE_USER, $data, 1);
                } else {
                    $data = ['ID' => $row['ID'], 'UF_UID' => $this->installId, 'UF_JSON' => json_encode($return), 'UF_UPDATE' => new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s')];
                    $row = HlClass::setOrderSource(self::MOBILE_USER, $data, 2);
                }

                return $return;
            }
        }

        return [
            "id" => "courier",
            "title" => "Доставка курьером",
            "description" => "Бесплатная доставка от 2000 руб. при оплате онлайн",
            "type" => "delivery",
            "price" => $this->price >= self::PRICE_DEVIVERY_FREE ? 0 : 350,
            "min" => 3,
            "timeLabel" => "От 3-х дней"
        ];
    }

    function def()
    {
        return [
            "deliveries" => [
                [
                    "id" => "courier",
                    "title" => "Доставка курьером",
                    "description" => "Бесплатная доставка от 2000 руб. при оплате онлайн",
                    "type" => "delivery",
                    "price" => 350,
                    "min" => 3,
                    "timeLabel" => "От 3-х дней"
                ],
//                [
//                    "id" => "pickup",
//                    "title" => "Пункты самовывоза",
//                    "description" => "Доставка в пункты самовывоза",
//                    "type" => "pickup",
//                    "price" => 199,
//                    "min" => 3,
//                    "timeLabel" => "От 3-х дней",
//                    "locations" => []
//                ]
            ]
        ];
	}
}

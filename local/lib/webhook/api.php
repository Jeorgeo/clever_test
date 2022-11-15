<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/geo.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/cart.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/basket_dev.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/orders/orders.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/deliveryMap.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/orderActions.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/cartActions.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/pickpoint.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/hl.php");


abstract class ApiAbstract
{
	const  MOBILE_USER = 'MobileUserDelivery';
    const  MOBILE_USSER_PROMO = 'MobileUserPromo'; // промокода
	
	protected $prod = true;  // true режим боевого сайта
	public $apiName = ''; //users
	
	protected $method = ''; //GET|POST|PUT|DELETE
	
	public $requestUri = [];
	public $requestParams = [];
	
	protected $action = ''; //Название метод для выполнения
	
	public function __construct() {
		header("Access-Control-Allow-Orgin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");
		
		//Массив GET параметров разделенных слешем
		// webhook / action / ID объекта если нужен и запуститься метод viewAction
		$this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
		$this->requestParams = $_REQUEST;
		$jsonInput = json_decode(file_get_contents('php://input'), true);
		if (is_array($jsonInput) && count($jsonInput) > 0) {
			$this->requestParams = $jsonInput;
		}
		
		// SET COOKIE
		if ($this->requestParams['installId']) {
			setcookie("BITRIX_SM_SALE_UID", $this->replaceUID($this->requestParams['installId']), time() + 360000, "/", "clever-media.ru", 1);
		}
		
		//Определение метода запроса
		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				$this->createLog('Unexpected Header',  $this->method);
			}
		}
	}
	
	public function run() {
		$this->createLog('Начало запроса ', $this->requestParams);
//		$this->createLogSession('run');
		//Первые 2 элемента массива URI должны быть "webhook" и название объекта действия
		// /webhook/order/  /webhook/cart/
		if(array_shift($this->requestUri) !== 'webhook' || array_shift($this->requestUri) !== $this->apiName){
			return $this->response('API Not Found', 404);
		}
		//Определение действия для обработки
		$this->action = $this->getAction();
		
		//Если метод(действие) определен в дочернем классе API
		if (method_exists($this, $this->action)) {
			return $this->{$this->action}();
		} else {
			$this->createLog('Invalid Method',  $this->action);
			return $this->response('Invalid Method', 500);
		}
	}
	
	protected function response($data, $status = 200) {
		header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
		return json_encode($data);
	}
	
	private function requestStatus($code) {
		$status = array(
			200 => 'OK',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error',
		);
		return ($status[$code])?$status[$code]:$status[500];
	}
	
	protected function getAction()
	{
//		$this->createLogSession('getAction');
		$method = $this->method;
		switch ($method) {
			case 'GET':
				if($this->requestUri){
					return 'viewAction';
				} else {
					return 'indexAction';
				}
				break;
			case 'POST':
				return 'createAction';
				break;
			case 'PUT':
				return 'updateAction';
				break;
			case 'DELETE':
				return 'deleteAction';
				break;
			default:
				return null;
		}
	}
	
	public function getMobileFuserId($UID, $userId = 0) {
		global $DB;
		
		$UID = $this->replaceUID($UID);
		
		$fUser = CSaleUser::GetList(["CODE" => $UID]);
//		$this->createLogSession('getMobileFuserId');
		if ($fUser['ID']) {
			$this->createLog("getMobileFuserId: " . "  UID: " . $UID . "  FUSER: " . $fUser['ID'] . "  USER ID " . $fUser['USER_ID']);

			// привяжем к пользователю
			if (!empty($userId)) {
			    // пробуем склеить разные корзины
                $this->updateMobileFuserUserId($fUser['ID'], $userId, $UID);

			}
			
			$_COOKIE['BITRIX_SM_SALE_UID'] = $UID;
//			$this->createLogSession('getMobileFuserId-1');
			return $fUser['ID'];
		} else {
            if (!empty($userId)) {
                $fUserOld = CSaleUser::GetList(["USER_ID" => $userId]);
                if (!empty($fUserOld['ID'])) {
                    $strSql = "update b_sale_fuser set DATE_UPDATE = " . $DB->GetNowFunction(
                        ) . ", CODE = " . $UID . " where ID = " . $fUserOld['ID'];
                    if (!$DB->Query($strSql, true)) {
                        return false;
                    }

                    $this->createTelegram('ОБновоили КОД FUSER: ' . $UID . ' - ' . $fUserOld['ID'] . ' - ' . get_class($this));
                    $this->createLog('ОБновоили КОД FUSER: ' . $UID . ' - ' . $fUserOld['ID'] . ' - ' . get_class($this));

                    $_COOKIE['BITRIX_SM_SALE_UID'] = $UID;
                    return $fUserOld['ID'];
                }
            }
        }
		
		$arFields = array(
			"=DATE_INSERT" => $DB->GetNowFunction(),
			"=DATE_UPDATE" => $DB->GetNowFunction(),
			//        "USER_ID" => (int)$userId > 0 ? (int)$userId : False,
			"USER_ID" => False,
			"CODE" => $UID,
		);
		
		$id = CSaleUser::_Add($arFields);
		//    $this->createTelegram('Создан FUSER: ' . $UID . ' - ' . $id . ' - ' . get_class($this));
		$this->createLog('Создан FUSER: ' . $UID . ' - ' . $id . ' - ' . get_class($this));
		
		$_COOKIE['BITRIX_SM_SALE_UID'] = $UID;
//		$this->createLogSession('getMobileFuserId-2');
		if ((int) $id > 0) {
			return $id;
		}
		
		$this->createTelegram('Не смог создать FUSER: ' . $UID);
		$this->createLog('Не смог создать FUSER: ' . $UID);
		
		return false;
	}

	public function updateMobileFuserUserId($fUserId, $userId, $UID) {
		global $DB;

//        $fUser = CSaleUser::GetList(["USER_ID" => $userId]);
//        if(!empty($fUser['ID']) && !empty($fUserId) && CModule::IncludeModule("sale")){
//            if ($fUser['ID'] !== $fUserId) {
//                CSaleBasket::TransferBasket($fUser['ID'], $fUserId);
//                CSaleUser::Update(CSaleUser::getFUserCode());
//                $this->createTelegram('СКЛЕЙКА КОРЗИН: ' . $UID . ' - ' . $fUser['ID'] .' => '. $fUserId . ' - ' . get_class($this));
//                $this->createLog('СКЛЕЙКА КОРЗИН: ' . $UID . ' - ' . $fUser['ID'] .' => '. $fUserId . ' - ' . get_class($this));
//            }
//        }

		if (!empty($userId)) {
		//	$strSql = "update b_sale_fuser set DATE_UPDATE = ".$DB->GetNowFunction().", USER_ID = ". $userId . ", CODE = ". $UID ." where ID = " . $fUserId;
			$strSql = "update b_sale_fuser set DATE_UPDATE = ".$DB->GetNowFunction().", USER_ID = ". $userId . " where ID = " . $fUserId;
			if(!$DB->Query($strSql, true)) {
				return false;
			}
		}
		
		return true;
	}
	
	protected function replaceUID($UID) {
		$UID = str_replace('-', '', $UID);
		
		return strlen($UID) <= 32 ? $UID : false;
	}
	
	abstract protected function indexAction();
	abstract protected function viewAction();
	abstract protected function createAction();
	abstract protected function updateAction();
	abstract protected function deleteAction();
	
	protected function checkUser($login = '', $password = '', $UID = '')
	{
		// Проверяем по externalIds
		// Проверяем по логину и паролю
		// Проверяем по логину и Fuser
		GLOBAL $USER;
		$UID = $this->replaceUID($UID);
		
		if (!empty($this->externalUserId)) {
			if ($USER->Authorize($this->externalUserId)) {
				return $this->externalUserId;
			}
		}
		
		$user = CUser::GetByLogin($login)->Fetch();
//        if (empty($user)) {
//            $user = CUser::GetList(
//                ($by = "LAST_LOGIN"),
//                ($order = "DESC"),
//                array(
//                    'PERSONAL_PHONE' => $login,
//                    'ACTIVE' => 'Y'
//                )
//            )->Fetch();
//        }
		
		// нашли пользователя по мылу или телефону
		if ($user) {
			// проверяем изменился ли код в fuser
			if (!empty($UID)) {
				$fuser = CSaleUser::GetList(["USER_ID" => $user['ID']]);
				if ($fuser['CODE'] !== $UID) {
					CSaleUser::_Update($fuser['ID'], ['CODE' => $UID]);
				}
			}
			
			return $user['ID'];
		}
		
		if (!empty($UID)) {
			return (int) CSaleUser::GetList(["CODE" => $UID])['USER_ID'];
		}
		
		return 0;
	}
	
	public function createLog($text, $body = []) {
		file_put_contents(
			$_SERVER["DOCUMENT_ROOT"] . "/logs/webhook/" . date("Ymd") . "_data.txt",
			date("Y.m.d H:i:s") . "\n" . get_class($this) . ": ". $text . "\n" . print_r($body,1) . "\n",
			FILE_APPEND
		);
	}
	
	public function createLogSession($methodName = '') {
		if (!isset($this->requestParams['installId']) || $this->requestParams['installId'] == 'c8be6675-9ef0-4b06-8d34-2ded5c8ec55b') {
			file_put_contents(
				$_SERVER["DOCUMENT_ROOT"] . "/logs/webhook/" . date("Ymd") . "_ses.txt",
				date("Y.m.d H:i:s") . "\n" . get_class($this) . ":  " . $this->requestParams['installId'] . print_r(
					$_COOKIE,
					1
				) . $methodName . " \n",
				FILE_APPEND
			);
		}
	}
	
	public function createTelegram($body = '', $chatId = 5) {
		Strclass::telegram($body, $chatId);
	}
}

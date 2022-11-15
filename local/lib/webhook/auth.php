<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");


class Auth extends ApiAbstract
{
    public $apiName = 'auth';

    private $login;
    private $password;
    private $externalUserId;
    private $user;

    protected function createAction()
    {
        $this->getJsonData();

        $this->checkUser($this->login, $this->password, '');

        if (!$this->externalUserId) {
            $this->createLog('Логин или пароль не существует в системе. ', $this->requestParams);

            return '
                {
                    "error": {
                        "message": "Логин или пароль не существует в системе"
                    }
                }
            ';
        }

        if (is_array($this->user) && isset($this->user['ID'])) {
            $this->createLog(
                'Ответ Auth. ',
                [
                    "user" => [
                        "id" => $this->user['ID'],
                        "name" => $this->user['LAST_NAME'] . ' ' . $this->user['NAME'],
                        "phone" => $this->user['PERSONAL_PHONE'],
                        "email" => $this->user['EMAIL'],
                        "bonuses" => 0,
                        "segments" => [],
                        "age" => 0,
                        "gender" => "",
                        "cardNumber" => ""
                    ]
                ]
            );
            return $this->response(
                [
                    "user" => [
                        "id" => $this->user['ID'],
                        "name" => $this->user['LAST_NAME'] . ' ' . $this->user['NAME'],
                        "phone" => $this->user['PERSONAL_PHONE'],
                        "email" => $this->user['EMAIL'],
                        "bonuses" => 0,
                        "segments" => [],
                        "age" => 0,
                        "gender" => "",
                        "cardNumber" => ""
                    ]
                ],
                200
            );
        }
    }

    private function getJsonData()
    {
        $this->login = $this->requestParams['login'] ? strtolower(trim($this->requestParams['login'])) : '';
        $this->password = $this->requestParams['password'] ? $this->requestParams['password'] : '';


        if (empty($this->password) || empty($this->login)) {
            $this->createTelegram('Ошибка авторизации, нет переданы (логин / пароль)');
            $this->createLog('Отсутсвуют входные данные', $this->requestParams);
        }

        $this->externalUserId = 0; // новый пользователь

        return true;
    }

    protected function checkUser($login = '', $password = '', $UID = '')
    {
		$domainMailDenide = ['clever-media.ru', 'clevercorp.ru'];
		$adminGroupsDenide = [1,9,5,7,8,32,36, 37,38,40,41];
		
        if (!empty($this->password) && !empty($this->login)) {
            global $USER;

            $user = CUser::GetByLogin($login)->Fetch();
            if (empty($user)) {
                $user = CUser::GetList(
                    ($by = "LAST_LOGIN"),
                    ($order = "DESC"),
                    array(
                        'PERSONAL_PHONE' => $login,
                        'ACTIVE' => 'Y'
                    )
                )->Fetch();
            }

            if ($user) {
                $login = $user['LOGIN'];
                $U = new CUser();
                $userAuth = $U->Login($login, $password);

                if (!is_array($userAuth)) { // нет ошибок и пользователь существует
                    $this->externalUserId = $user['ID'];
                    $USER->Authorize($user['ID']);
                    $this->user = $user;
                    
                    return true;
                } else {
                    $this->createLog('Ошибка при авторизации логин и пароль. ' . $this->login . ' : ' . $this->password);
                    //$this->createTelegram('Ошибка при авторизации логин и пароль. ' . $this->login);
                    //return false;
                }

                // мы нашли пользователя по логину, но пароль не подошел
                // возвращаем его id
				list($emailName, $emailDomain) = explode('@', trim($login));
				if (in_array($emailDomain, $domainMailDenide)) {
					$this->createTelegram('Ошибка при авторизации, домен Клевер Медиа. ' . $this->login);
					return false;
				}
	
				$arGroups = CUser::GetUserGroup($user['ID']); // массив групп, в которых состоит пользователь
				$groupIntersect = array_intersect($adminGroupsDenide, $arGroups); // ищем пересечение групп с админскими
				if (!empty($groupIntersect)) {
					$this->createTelegram('Ошибка при авторизации, пользователь с правами. ' . $this->login);
					return false;
				}
				
				$this->createTelegram('Ошибка при авторизации логин и пароль. Сделали тихую авторизацию.  ' . $this->login);
                $this->externalUserId = $user['ID'];
				$USER->Authorize($user['ID']);
				$this->user = $user;
				Analitics::analiticsBI(48, ['ID' => $user['ID']]);
				
                return true;
            } else {
                // создаем пользователя если нет такой почты
                // у нас ничего нет кроме логина и пароля
                $params['user-data-name'] = '';
                $params['user-data-last-name'] = '';
                $params['user-data-second-name'] = '';
                $params['user-data-phone'] = '';
                $params['user-data-email'] = $this->login;
                $params['password'] = $this->password;
                $params['new_user_group'] = [6, 35];
                $params['reg_type'] = 101;

                $ID = (int) OrderAction::createUser($params);
				$this->createLog('$ID - ', $ID);
                if ($ID > 0) {
                    if ($USER->Authorize($ID) === true) { // нет ошибок и пользователь существует
                        $this->externalUserId = $ID;
                        $this->user = CUser::GetByID($ID);

                        return true;
                    }
                }

                $this->createTelegram('Ошибка при создании пользователя. ' . $this->login);
            }
        }

        return false;
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
            "user": {
                "id": "71234567890",
                "name": "Иванов Иван",
                "phone": "71234567890",
                "email": "ivanov@mail.com",
                "bonuses": 10000,
                "segments": ["registered", "loyal"],
                "age": 35,
                "gender": "male",
                "cardNumber": "456123789"
            }
        }
	    ';
    }
}

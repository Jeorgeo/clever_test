<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");


class Authorize extends ApiAbstract
{
    public $apiName = 'authorize';

    private $userIdentifier;
    private $externalUserId;
    private $user;

    protected function createAction()
    {
        $this->getJsonData();

        $this->checkUser('', '', '');

        if (!$this->externalUserId) {
            $this->createLog('Пользователя с таким идентификатором не существует. ID - ' . $this->userIdentifier);
            $this->createTelegram('Пользователя с таким идентификатором не существует. ID - ' . $this->userIdentifier);
            return '
                {
                    "error": {
                        "message": "Пользователя с таким идентификатором не существует"
                    }
                }
            ';
        }

        $this->createLog(
            'Ответ авторизации. ',
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

    private function getJsonData()
    {
        $this->userIdentifier = $this->requestParams['userIdentifier'] ? $this->requestParams['userIdentifier'] : 0;

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

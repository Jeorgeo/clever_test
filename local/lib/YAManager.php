<?php

class YAManager
{

    const CLIENT_ID = '19953c19978b44f68727de0a0f8d6a8a'; // Идентификатор приложения
    const CLIENT_SECRET = 'a4a20032bc134a7db0a5ad6d6d32c2cf'; // Пароль приложения
    const OAUTH_URL = 'https://oauth.yandex.ru/token';

    const TOKEN = y0_AgAAAAAVTqcaAAjLfgAAAADWQeFc_meqvInNS6aOqy49bIY_Mc_aDxU; // токен


    /**
     * @param $params
     * @return mixed|void
     */
    private static function sendRequest($params)
    {

        $getParams = self::getParamsForRequest($params);

        $url = $params['url'];
        $method = $params['method'];

        $header = $getParams['header'];
        $body = $getParams['body'];

        if (isset($header) && isset($body))
        {
            // Инициализация cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            if ($method == 'post')
            {
                curl_setopt($curl, CURLOPT_POST, true);
            }
            else
            {
                curl_setopt($curl, CURLOPT_POST, false);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

            // Выполнение запроса, получение результата
            $result = curl_exec($curl);

            return json_decode($result, true);

        }

    }

    /**
     * @param $params
     * @return array
     */
    private static function getParamsForRequest($params): array
    {
        $token = self::TOKEN;
        $result = array();

        switch ($params['type_data'])
        {
            // Установка HTTP-заголовков запроса
            case 'file':
                $result['header'] = [
                    "Authorization: Bearer $token", // OAuth-токен. Использование слова Bearer обязательно
                    "Content-Type: multipart/form-data",
                ];
                $result['body'] = [
                    'file'  => new \CurlFile(
                        $params['file_path'],
                        'application/octet-stream',
                        $params['file_name']
                    ),
                    'name'  => $params['file_name']
                ];
                break;
            case 'json':
                $result['header'] = [
                    "Authorization: Bearer $token",                   // OAuth-токен. Использование слова Bearer обязательно
                    "Accept-Language: ru",                            // Язык ответных сообщений
                    "Content-Type: application/json; charset=utf-8"   // Тип данных и кодировка запроса
                ];
                // Преобразование входных параметров запроса в формат JSON
                $result['body'] = json_encode($params['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
        }

        return $result;
    }

    /**
     * @return array
     */
    public static function getSegments(): array
    {

        // Параметры запроса к серверу API
        $params = [
            'method' => 'get',
            'params' => array(),
            'url' => 'https://api-audience.yandex.ru/v1/management/segments',
            'type_data' => 'json',
        ];

        $result = array();

        $response = self::sendRequest($params);

        if (isset($response['segments']))
        {
            foreach ($response['segments'] as $key => $item)
            {
                $result[$key]['id'] = $item['id'];
                $result[$key]['name'] = $item['name'];
                $result[$key]['type'] = $item['type'];
                $result[$key]['status'] = $item['status'];
                $result[$key]['create_time'] = $item['create_time'];
                $result[$key]['content_type'] = $item['content_type'];
            }
        }

        return $result;

    }

    /**
     * @param $fileParams
     * @param $segmentID
     * @return array
     */
    public static function addDataForSegment($fileParams, $segmentID): array
    {

        $result = array();

        if (isset($segmentID))
        {
            $url = 'https://api-audience.yandex.ru/v1/management/segment/';
            $url .= $segmentID;
            $url .= '/modify_data?modification_type=addition'; // метод изменение данных сегмента, тип изменения данных: добавление

            // Параметры запроса к серверу API
            $params = [
                'method'    => 'post',
                'url'       => $url,
                'type_data' => 'file',
                'file_name' => $fileParams['file_name'],
                'file_path' => $fileParams['file_path']
            ];

            $result = self::getArr($params, $result);

        }

        return $result;
    }

    /**
     * @param $fileParams
     * @return array
     */
    public static function createNewSegment($fileParams): array
    {
        $result = array();
        $url = 'https://api-audience.yandex.ru/v1/management/segments/upload_csv_file'; //upload_csv_file / upload_file
        // Параметры запроса к серверу API
        $params = [
            'method'    => 'post',
            'url'       => $url,
            'type_data' => 'file',
            'file_name' => $fileParams['file_name'],
            'file_path' => $fileParams['file_path']
        ];

        $response = self::sendRequest($params);

        if (isset($response['segment']))
        {
            $result['id'] = $response['segment']['id'];
            $result['hashed'] = $response['segment']['hashed'];
            $result['status'] = $response['segment']['status'];
        }

        return $result;

    }

    /**
     * @param $params
     * @return array
     */
    public static function saveNewSegment($params): array
    {
        $result = array();

        $segmentID = $params['id'];

        if (isset($segmentID)) {

            $url = "https://api-audience.yandex.ru/v1/management/segment/$segmentID/confirm";

            $sendParams = [
                'method'    => 'post',
                'url'       => $url,
                'type_data' => 'json',
                'params' => [
                    'segment'   => [
                        'id'            => $segmentID,
                        'name'          => $params['name'],
                        'hashed'        => $params['hashed'] ? : 0,
                        'content_type'  => 'crm'
                    ]
                ]
            ];

            $result = self::getArr($sendParams, $result);
        }

        return $result;

    }

    /**
     * @param array $sendParams
     * @param array $result
     * @return array
     */
    public static function getArr(array $sendParams, array $result): array
    {
        $response = self::sendRequest($sendParams);

        if ($response['segment']) {
            $result['id'] = $response['segment']['id'];
            $result['name'] = $response['segment']['name'];
            $result['type'] = $response['segment']['type'];
            $result['status'] = $response['segment']['status'];
            $result['create_time'] = $response['segment']['create_time'];
            $result['content_type'] = $response['segment']['content_type'];
            $result['hashed'] = $response['segment']['hashed'];
        }
        return $result;
    }

    /**
     * @param $fileParams
     * @param $name
     * @return array
     */
    public static function addNewSegment($fileParams, $name): array
    {
        $result = array();
        $createSegmentParams = self::createNewSegment($fileParams);

        if ($createSegmentParams['id'] && $createSegmentParams['status'] == 'uploaded')
        {
            $createSegmentParams['name'] = $name;
            $result = self::saveNewSegment($createSegmentParams);
        }

        return $result;

    }

}
<?php

class YAManager
{

    const CLIENT_ID = '19953c19978b44f68727de0a0f8d6a8a'; // Идентификатор приложения
    const CLIENT_SECRET = 'a4a20032bc134a7db0a5ad6d6d32c2cf'; // Пароль приложения
    const OAUTH_URL = 'https://oauth.yandex.ru/token';

    const TOKEN = y0_AgAAAAAVTqcaAAjLfgAAAADWQeFc_meqvInNS6aOqy49bIY_Mc_aDxU; // токен

    /**
     * @param $params
     * @param $url
     * @param string $type
     * @param $post
     * @return mixed
     */
    private static function sendCurl($params, $url, string $type)
    {
        $token = self::TOKEN;

        switch ($type)
        {
            // Установка HTTP-заголовков запроса
            case 'file':
                $headers = array(
                    "Authorization: Bearer $token", // OAuth-токен. Использование слова Bearer обязательно
                    "Content-Type: multipart/form-data",
                );
//                $body = $params;
                $body = [
                    'file'  => new \CurlFile(
                        $params['url'],
                        'application/octet-stream',
                        $params['file']
                    ),
                    'name'  => $params['file']
                ];
                $post = true;
                break;
            case 'json':
                $headers = array(
                    "Authorization: Bearer $token",                   // OAuth-токен. Использование слова Bearer обязательно
                    "Accept-Language: ru",                            // Язык ответных сообщений
                    "Content-Type: application/json; charset=utf-8"   // Тип данных и кодировка запроса
                );
                // Преобразование входных параметров запроса в формат JSON
                $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $post = false;
                break;
        }

        if (isset($headers) && isset($body))
        {
            // Инициализация cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            if ($post)
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

//            $response = json_decode($result, true);
//
//            echo '<pre>';
//            echo print_r($response);
//            echo print_r($headers);
//            echo print_r($body);
//            echo '</pre>';
        }

    }

    public static function getYAToken($code = '')
    {
        // Если скрипт был вызван с указанием параметра "code" в URL,
        if ($code)
        {
            // Формирование параметров (тела) POST-запроса с указанием кода подтверждения
//            $query = array(
//                'grant_type' => 'authorization_code',
//                'code' => $code,
//                'client_id' => self::CLIENT_ID,
//                'client_secret' => self::CLIENT_SECRET
//            );
            $query = array(
                'response_type' => 'token',
//                'code' => $code,
                'client_id' => self::CLIENT_ID,
//                'client_secret' => self::CLIENT_SECRET
            );
            $query = http_build_query($query);

            // Формирование заголовков POST-запроса
            $header = "Content-type: application/x-www-form-urlencoded";

            // Выполнение POST-запроса и вывод результата
            $opts = array('http' =>
                array(
                    'method' => 'POST',
                    'header' => $header,
                    'content' => $query
                )
            );
            $context = stream_context_create($opts);
            $result = file_get_contents(self::OAUTH_URL, false, $context);
//            $result = json_decode($result);

            // Токен необходимо сохранить для использования в запросах к API Директа
            echo '<pre>';
            echo print_r($result);
            echo '</pre>';
//            echo $result->access_token;
        }
        // Если скрипт был вызван без указания параметра "code",
        // пользователю отображается ссылка на страницу запроса доступа
        else
        {
            echo '<a href="https://oauth.yandex.ru/authorize?response_type=code&client_id=' . self::CLIENT_ID . '">Страница запроса доступа</a>';
        }
    }

    public static function getSegments()
    {
        $url = 'https://api-audience.yandex.ru/v1/management/segments';

        /** @var TYPE_NAME $result */
        $result = array();

        // Параметры запроса к серверу API
        $params = [
            'method' => 'get',
            'params' => array()
        ];

        $response = self::sendCurl($params, $url, 'json', 0);

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

    public static function addDataForSegment($params, $segmentID)
    {

        if (isset($segmentID))
        {
            $url = 'https://api-audience.yandex.ru/v1/management/segment/';
            $url .= $segmentID;
            $url .= '/modify_data?modification_type=addition'; // метод изменение данных сегмента, тип изменения данных: добавление

            $token = self::TOKEN;

            // Установка HTTP-заголовков запроса
            $headers = array(
                "Authorization: Bearer $token", // OAuth-токен. Использование слова Bearer обязательно
                "Content-Type: multipart/form-data"
            );

//            $body = [
//                'file'  => new \CurlFile(
//                    $params['url'],
//            'application/octet-stream',
//                    $params['file']
//                    ),
//                'name'  => $params['file'],
//            ];

            // Инициализация cURL
//            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, $url);
//            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($curl, CURLOPT_POST, true);
//            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
//
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

            // Выполнение запроса, получение результата
//            $result = curl_exec($curl);
//            $result = json_decode($result, true);
            $response = self::sendCurl($params, $url, 'file', 1);


            echo '<pre>';
            echo print_r($response);
            echo print_r($params);
//            echo print_r($body);
            echo '</pre>';
        }

    }

    public static function addNewSegment($params)
    {

//            $token = self::TOKEN;
            $url = 'https://api-audience.yandex.ru/v1/management/segments/upload_csv_file'; //upload_csv_file / upload_file

            // Установка HTTP-заголовков запроса
//            $headers = array(
//                "Authorization: Bearer $token", // OAuth-токен. Использование слова Bearer обязательно
//                "Content-Type: multipart/form-data",
//            );

            // Инициализация cURL
//            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, $url);
//            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($curl, CURLOPT_POST, true);
//            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
//
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);


            // Выполнение запроса, получение результата
//            $result = curl_exec($curl);
//            $result = json_decode($result, true);

        $response = self::sendCurl($params, $url, 'file', 1);


            echo '<pre>';
            echo print_r($response);
            echo print_r($params);
//            echo print_r($result);
            echo '</pre>';

    }

    public static function saveNewSegment($params, $segmentID)
    {
        if (isset($segmentID)) {
//            $token = self::TOKEN;
            $url = "https://api-audience.yandex.ru/v1/management/segment/$segmentID/confirm";

            // Установка HTTP-заголовков запроса
//            $headers = array(
//                "Authorization: Bearer $token", // OAuth-токен. Использование слова Bearer обязательно
//                "Content-Type: application/json; charset=utf-8",
//            );

            // Преобразование входных параметров запроса в формат JSON
            $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Инициализация cURL
//            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, $url);
//            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($curl, CURLOPT_POST, true);
//            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
//
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);


            // Выполнение запроса, получение результата
//            $result = curl_exec($curl);
//            $result = json_decode($result, true);

            $response = self::sendCurl($params, $url, 'file', 1);

            echo '<pre>';
            echo print_r($headers);
            echo print_r($params);
            echo print_r($result);
            echo '</pre>';
        }

    }

}
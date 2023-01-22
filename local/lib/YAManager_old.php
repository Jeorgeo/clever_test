<?php

class YAManager
{

    const CLIENT_ID = '19953c19978b44f68727de0a0f8d6a8a'; // Идентификатор приложения
    const CLIENT_SECRET = 'a4a20032bc134a7db0a5ad6d6d32c2cf'; // Пароль приложения
    const OAUTH_URL = 'https://oauth.yandex.ru/token';

    const TOKEN_TEST = y0_AgAAAAAVTqcaAAjLfgAAAADWQeFc_meqvInNS6aOqy49bIY_Mc_aDxU; // токен

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
    {}

}
<?php
//--- Входные данные -------------------------------------------------------------------------//
// Адрес сервиса Campaigns для отправки JSON-запросов (регистрозависимый)
$url = 'https://api.direct.yandex.com/json/v5/campaigns';
// OAuth-токен пользователя, от имени которого будут выполняться запросы
$token = 'ТОКЕН';
// Логин клиента рекламного агентства
// Обязательный параметр, если запросы выполняются от имени рекламного агентства
$clientLogin = 'ЛОГИН_КЛИЕНТА';

//--- Подготовка и выполнение запроса -----------------------------------//
// Установка HTTP-заголовков запроса
$headers = array(
    "Authorization: Bearer $token",                   // OAuth-токен. Использование слова Bearer обязательно
    "Client-Login: $clientLogin",                     // Логин клиента рекламного агентства
    "Accept-Language: ru",                            // Язык ответных сообщений
    "Content-Type: application/json; charset=utf-8"   // Тип данных и кодировка запроса
);

// Параметры запроса к серверу API Директа
$params = array(
    'method' => 'get',                                // Используемый метод сервиса Campaigns
    'params' => array(
        'SelectionCriteria' => (object) array(),      // Критерий отбора кампаний. Для получения всех кампаний должен быть пустым
        'FieldNames' => array('Id', 'Name')           // Названия параметров, которые требуется получить
    )
);
// Преобразование входных параметров запроса в формат JSON
$body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Инициализация cURL
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

/*
Для полноценного использования протокола HTTPS можно включить проверку SSL-сертификата сервера API Директа.
Чтобы включить проверку, установите опцию CURLOPT_SSL_VERIFYPEER в true, а также раскомментируйте строку с опцией CURLOPT_CAINFO и укажите путь к локальной копии корневого SSL-сертификата.
*/
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//curl_setopt($curl, CURLOPT_CAINFO, getcwd().'\CA.pem');

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLINFO_HEADER_OUT, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

// Выполнение запроса, получение результата
$result = curl_exec($curl);

//--- Обработка результата выполнения запроса ---------------------------//
if(!$result) { echo ('Ошибка cURL: '.curl_errno($curl).' - '.curl_error($curl)); }
else {
    // Разделение HTTP-заголовков и тела ответа
    $responseHeadersSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($result, 0, $responseHeadersSize);
    $responseBody = substr($result, $responseHeadersSize);

    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) { echo "HTTP-ошибка: ".curl_getinfo($curl, CURLINFO_HTTP_CODE); }
    else {
        // Преобразование ответа из формата JSON
        $responseBody = json_decode($responseBody);

        if (isset($responseBody->error)) {
            $apiErr = $responseBody->error;
            echo "Ошибка API {$apiErr->error_code}: {$apiErr->error_string} - {$apiErr->error_detail} (RequestId: {$apiErr->request_id})";
        }
        else {
            // Извлечение HTTP-заголовков ответа: RequestId (Id запроса) и Units (информация о баллах)
            $responseHeadersArr = explode("\r\n", $responseHeaders);
            foreach ($responseHeadersArr as $header) {
                if (preg_match('/(RequestId|Units):/', $header)) { echo "$header <br>"; }
            }

            // Вывод списка рекламных кампаний
            foreach ($responseBody->result->Campaigns as $campaign) {
                echo "Рекламная кампания: {$campaign->Name} ({$campaign->Id})<br>";
            }
        }
    }
}

//--- Отладочная информация ---------------------------------------------//
//echo "<hr>Заголовки запроса: <pre>".curl_getinfo($curl, CURLINFO_HEADER_OUT)."</pre>";
//echo "Запрос: <pre>".json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."</pre>";
//echo "Заголовки ответа: <pre>".$responseHeaders."</pre>";
//echo "Ответ: <pre>".json_encode($responseBody, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."</pre>";

curl_close($curl);
?>

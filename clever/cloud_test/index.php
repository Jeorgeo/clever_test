<?
header('Content-Type: text/html; charset=utf-8'); // на всякий случай досообщим PHP, что все в кодировке UTF-8
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$dir = '/local/lib/webhook/cloudpayments';

$classesCloudPayments = [
    'CloudPayments\Manager' => $dir.'/Manager.php',
    'CloudPayments\Model\Transaction' => $dir.'/Model/Transaction.php',
    'CloudPayments\Model\Required3DS' => $dir.'/Model/Required3DS.php',
    'CloudPayments\Model\Subscription' => $dir.'/Model/Subscription.php',
    'CloudPayments\Exception\BaseException' => $dir.'/Exception/BaseException.php',
    'CloudPayments\Exception\PaymentException' => $dir.'/Exception/PaymentException.php',
    'CloudPayments\Exception\RequestException' => $dir.'/Exception/RequestException.php',
];

Bitrix\Main\Loader::registerAutoLoadClasses(null, $classesCloudPayments);

use CloudPayments\Manager;
use CloudPayments\Model\Subscription;
use CloudPayments\Model\Transaction;

// Ключи для тестирования
$publicKey = 'pk_36a1cb66c9796c88159d353007986';
$privateKey = '260af1d4c837b7de9f28c2b1e939e286';

$client = new \CloudPayments\Manager($publicKey, $privateKey);

try {
            $tildaID='1643038052135';
            $email = "jeorgeo@list.ru";
            $descr = 'тест чека';
            $price = 300;
            $currency = 'RUB';
            $items[] =
                    [
                        "label" => $descr,
                        "price"  => $price,
                        "quantity" => 1,
                        "amount" => $price,
                        "vat" => 20, //ставка НДС
                        //"method" => 0, // тег-1214 признак способа расчета - признак способа расчета
                        //"object" => 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                        "measurementUnit" => "шт" //единица измерения
                    ];
            $dataForCheck = [
                "cloudPayments" => [
                    "CustomerReceipt" => [
                        "Items" => $items,
                        "isBso" => false,
                        "amounts" => [
                            "electronic" => 150,
                            "provision" => 150
                        ]
                    ]
                ]
            ];
			$params = [
				'InvoiceId' => $tildaID, // указывать ID заказа,  а не номер !!!
				// 'SuccessRedirectUrl' => 'https://clever-box.ru/success?order='.$tildaID,
				// 'FailRedirectUrl' => 'https://clever-box.ru/order-fail',
				'AccountId' => $email,
				'Email' => $email,
                'JsonData' => $dataForCheck,
                // 'JsonData' => json_encode($dataForCheck),
			];
			$orderModel = $client->orderCreate($price, $currency, $descr, '', $params);

            foreach ($orderModel as $key => $value) {
                $text .= $key;
                $text .= ' : ';
                $text .= $value;
                $text .= "\r\n";
            }

            // $text = 'Новый тест чека '.date('Y-m-d H:i:s').' '.$_SERVER["HTTP_REFERER"];
            // file_get_contents ('https://api.telegram.org/bot917372058:AAGbg3BUgnVRoQGotuqLt4MSLjURRgJ4CDA/sendMessage?chat_id=-1001846533724&text='.$text);
            // message_to_telegram($text);

            echo '<pre>';
			echo print_r($orderModel);
            echo '</pre>';
        } catch (Exception $e) {
            print $e->getMessage();
        }

        // функция отправки сообщени в от бота в диалог с юзером
// function message_to_telegram($text, $reply_markup = '')
// {
//     $bot_token = '5666799054:AAEWJSdXm21rxT2o3SrvQuNGtrNkkJXQK3c';
//     $chatID = '-879444774';
//     $ch = curl_init();
//     $ch_post = [
//         CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/sendMessage',
//         CURLOPT_POST => TRUE,
//         CURLOPT_RETURNTRANSFER => TRUE,
//         CURLOPT_TIMEOUT => 10,
//         CURLOPT_POSTFIELDS => [
//             'chat_id' => $chatID,
//             'parse_mode' => 'HTML',
//             'text' => $text,
//             'reply_markup' => $reply_markup,
//         ]
//     ];
//
//     curl_setopt_array($ch, $ch_post);
//     curl_exec($ch);
// }

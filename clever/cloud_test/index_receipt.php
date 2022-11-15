<?
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
            $price = 150;
            $currency = 'RUB';
            $items = [
                0 => [
                    "label" => $descr,
                    "price"  => $price,
                    "quantity" => 1,
                    "amount" => $price,
                    "vat" => 20, //ставка НДС
                    "method" => 1, // тег-1214 признак способа расчета - признак способа расчета
                    "object" => 1, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                    "measurementUnit" => "шт" //единица измерения
                ],
                1 => [
                    "label" => "Наименование товара 1",
                    "price"  => 100.00,
                    "quantity" => 1,
                    "amount" => 100.00,
                    "vat" => 20, //ставка НДС
                    "method" => 1, // тег-1214 признак способа расчета - признак способа расчета
                    "object" => 1, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                    "measurementUnit" => "шт" //единица измерения
                ]
            ];
            $inn = '7717567452';
            $invoiceId = $tildaID;
            $accountId = $email;
            $taxationSystem = 0;
            $phone = '';
            $paramsRecepient = [
                        "amounts" => [
                        "electronic" => 50,
                        "provision" => 200
                    ]
                ];

            $receipt = $client->sendReceipt(
                $inn,
                $invoiceId,
                $accountId,
                $items,
                $taxationSystem,
                $email,
                $phone,
                true,
                $paramsRecepient
            );
            echo '<pre>';
			echo print_r($receipt);
            echo '</pre>';
        } catch (Exception $e) {
            print $e->getMessage();
        }

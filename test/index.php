<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use CGift\CGiftManager;
use Clever\CleverWorker;


$hightBlockProduct = new CleverWorker('products');

// echo $hightBlockProduct;


CGiftManager::test();

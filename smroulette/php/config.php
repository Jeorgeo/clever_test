<?php
    require_once($_SERVER['DOCUMENT_ROOT'] . '/smroulette/php/phpmailer/phpmailer.php');

		// *** SMTP *** //

		 // require_once($_SERVER['DOCUMENT_ROOT'] . '/smartbasket/php/phpmailer/smtp.php');
		 // const HOST = '';
		 // const LOGIN = '';
		 // const PASS = '';
		 // const PORT = '';

		// *** /SMTP *** //

    const PROBABILITY = 10;
    const SENDER = 'sender@yandex.ru'; // отправитьель
    const CATCHER = 'catcher@ya.ru';   // получатель
    const SUBJECT = 'Заявка с сайта smartlanding.biz';
    const CHARSET = 'UTF-8';
    
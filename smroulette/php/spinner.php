<?php

use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once($_SERVER['DOCUMENT_ROOT'] . '/smroulette/php/phpmailer/phpmailer.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/smroulette/php/config.php');


    if (isset($_POST['section']) && !empty($_POST['section'])) {

        $tempSectionArray = explode(",", $_POST['section'][0]);


        $randNumber = random_int(0, count($tempSectionArray) - 1);


        $section = $tempSectionArray[$randNumber];

        $countSection = count($tempSectionArray);

        $widthSection = 360 / $countSection;

        $correction = $widthSection / 2;

        $correctionDeg = 45 + $correction - $widthSection;
       
        $maxDeg = $randNumber * $widthSection - 5;

        $minDeg = $randNumber * $widthSection - $widthSection + 5;

        $deg = $correctionDeg - rand($minDeg, $maxDeg) - 1800;


    } else {
        $resArr = [
            "error" => "NOTSECTION"
        ];
        echo json_encode($resArr);
    }


    if (
        ((isset($_POST['tel']) && !empty($_POST['tel'])) || (isset($_POST['email']) && !empty($_POST['email'])) || (isset($_POST['customText']) && !empty($_POST['customText'])))
        &&
        (isset($_POST['section']) && !empty($_POST['section']))
    ) {

        if (!empty($_POST['tel'])) {
            $tel = "<b>Телефон: </b> " . trim(strip_tags($_POST['tel'])) . "<br>";
        }

        if (!empty($_POST['email'])) {
            if (preg_match("/@/", $_POST['email'])) {
                $userMail = "<b>Почта: </b> " . trim(strip_tags($_POST['email'])) . "<br>";
            } else {
                $resArr = [
                    "error" => "NOTCORRECTMAIL"
                ];
                echo json_encode($resArr);
                exit;
            }
        }

        if(!empty($_POST['customText'])) {
            $customField = "<b>Контакт для связи: </b> " . trim(strip_tags($_POST['customText'])) . "<br>";
        }

        $prize = "<b>Приз: </b> " . trim(strip_tags($section)) . "<br>";

        if (defined('HOST') && HOST != '') {
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = HOST;
            $mail->SMTPAuth = true;
            $mail->Username = LOGIN;
            $mail->Password = PASS;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = PORT;

        } else {
            $mail = new PHPMailer;
        }
        $mail->AddReplyTo(SENDER);
        $mail->setFrom(SENDER);
        $mail->addAddress(CATCHER);
        $mail->CharSet = CHARSET;
        $mail->isHTML(true);
        $mail->Subject = SUBJECT;
        $mail->Body = "$tel $userMail $customField $prize";
        if (!$mail->send()) {
            echo json_encode("NOTSEND");
        } else {
            $resArr = [
                "deg" => $deg,
                "title" => trim(strip_tags($section)),
            ];
            echo json_encode($resArr);
        }

    } else {
        $resArr = [
            "error" => "NOTCONTACT"
        ];
        echo  json_encode($resArr);
    }


} else {
    header("Location: /");
}
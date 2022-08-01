<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
    $id = mt_rand(1, 10);
    $priceRandomId = mt_rand(1, 3);

    $priceList = [
        456.89,
        589.56,
        586.45,
        456.88
    ];

    $currentPrice = $price[$priceRandomId];
?>


<div class="wrap">

</div>

<form class="js-request" action="<?=POST_FORM_ACTION_URI?>" method="post">
    <!-- Hidden Required Fields -->
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="price" value="<?=$currentPrice?>">
    <!-- END Hidden Required Fields -->
    <button type="button" class="btn js-request" name="button">нажми сюда</button>
</form>

<section id="window" class="popup js-popup">
    <button class="popup__btn-close js-close-popup" type="button">×</button>
    <form id="js_form" method="post" class="cloud-form order-form">
    <fieldset class="cloud-form__section">
    <h3 class="cloud-form__title">Ваш трек</h3>
    <p class="cloud-form__track"></p>
    </fieldset>
    <button id="submit" class="btn cloud-form__submit disabled js-getResponse" type="submit" name="submit">Отправить</button>
    <fieldset class="cloud-form__section js-response">
    </fieldset>
    </form>
</section>

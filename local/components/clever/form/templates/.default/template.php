<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="wrap">

</div>

<form class="js-request" action="<?=POST_FORM_ACTION_URI?>">
    <!-- Hidden Required Fields -->
    <input type="hidden" name="ID" value="<?=$arResult['ID']?>">
    <input type="hidden" name="PRICE" value="<?=$arResult['PRICE']?>">
    <!-- END Hidden Required Fields -->
    <button type="button" class="btn js-request" name="button">нажми сюда</button>
</form>

<section id="window" action="<?=POST_FORM_ACTION_URI?>" class="popup js-popup">
    <button class="popup__btn-close js-close-popup" type="button">×</button>
    <form method="post" class="cloud-form order-form js-form">
    <input type="hidden" name="CODE" value="">
    <fieldset class="cloud-form__section">
    <h3 class="cloud-form__title">Ваш трек</h3>
    <p class="cloud-form__track js-track"></p>
    </fieldset>
    <label class="cloud-form__label js-input hidden">
        <input type="text" class="cloud-form__input" name="TEXT" value="">
    </label>
    <button class="btn cloud-form__submit js-getResponse" type="submit" name="submit" disabled>Отправить</button>
    <fieldset class="cloud-form__section js-response">
    </fieldset>
    </form>
</section>

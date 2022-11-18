<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Рулетка");
?>
  <link rel="stylesheet" href="style.css">

  <!-- главный блок -->
  <div class="modal-wheel">
      <div class="deal-wheel">
        <!-- блок с призами -->
        <ul class="spinner"></ul>
        <!-- язычок барабана -->
        <div class="ticker"></div>
        <!-- кнопка -->
      </div>
      <form class="form" action="" method="post">
          <input type="hidden" class="js-prize" name="PRIZE_ID" value="8">
          <label for="">
              введите email на который мы отправим ваш приз
              <input class="js-mail" type="mail" name="MAIL" value="" placeholder="Ваш email">
          </label>
          <label for="">
              <input class="js-agreement" type="checkbox" name="agreement" value="" checked required>
              <span>
                  Нажимая на кнопку “Испытай удачу”, вы подтверждаете свое совершеннолетие, соглашаетесь на обработку персональных данных и принимаете условия Пользовательского соглашения.
              </span>
          </label>
          <button class="btn-spin js-btnSpin" disabled>Испытай удачу</button>
      </form>
      <button class="modal-close" type="button" name="button">Х</button>
  </div>
    <!-- подключаем скрипт -->
    <script  src="script.js"></script>


  <?
  require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
  ?>

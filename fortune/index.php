<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Рулетка");
?>
  <link rel="stylesheet" href="style.css">

<!-- главный блок -->
<div class="deal-wheel">
  <!-- блок с призами -->
  <ul class="spinner"></ul>
  <!-- язычок барабана -->
  <div class="ticker"></div>
  <!-- кнопка -->
  <button class="btn-spin">Испытай удачу</button>
</div>
  <!-- подключаем скрипт -->
  <script  src="script.js"></script>

  <?
  // require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
  ?>

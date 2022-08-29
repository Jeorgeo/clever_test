<?php
namespace Clever;

/**
 * Trait DebugResult
 * Реализует вывод на экран
 */

trait DebugResult
{

     public function debugResult($result)
     {
         echo '<pre>';
         echo print_r($result);
         echo '</pre>';
     }
}
 ?>

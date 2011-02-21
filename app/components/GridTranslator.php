<?php

/**
 * Description of GridTranslator
 *
 * @author David Šabata
 * @copyright Copyright (c) 2009, 2010 David Šabata
 */
class GridTranslator implements \Nette\ITranslator {


   public function translate($message, $count = NULL) {

      $phrases = array(
         '%label% %input% of %count%'  => '%label% %input% z %count%',
         'Apply filters'   => 'Nastavit filtr',
         'First'  => 'První',
         'Previous'  => 'Předchozí',
         'Page'   => 'Strana',
         'Next'   => 'Následující',
         'Last'   => 'Poslední',
         'Change page'  => 'Nastavit stranu',
         'Reset state'  => 'Výchozí stav',
         'all' => 'vše',
         'Change' => 'Změnit',         
      );
      
      if (isset($phrases[$message]))
         return $phrases[$message];
      else
         return $message;
   }
}

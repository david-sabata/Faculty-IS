<?php
 /**
  * DateTimePicker input control
  *
  * @package   Nette\Extras\DateTimePicker
  * @example   http://nettephp.com/extras/datetimepicker
  * @version   $Id: DateTimePicker.php,v 1.0.0 2010/02/25 18:11:08 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2009 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 //require_once(LIBS_DIR.'/Nette/Forms/Controls/TextInput.php');

 class DateTimePicker extends Nette\Forms\TextInput
 {
   /**
    * Konstruktor
    *
    * @access public
    *
    * @param string $label label
    * @param int $cols šířka elementu input
    * @param int $maxLenght parametr maximální počet znaků
    */
   public function __construct($label, $cols = null, $maxLenght = null)
   {
     parent::__construct($label, $cols, $maxLenght);
   }

   /**
    * Vrácení hodnoty pole
    *
    * @access public
    *
    * @return string|NULL
    */
   public function getValue()
   {      
      $obj = \DateTime::createFromFormat('j. n. Y H:i', $this->value);
      if ($obj === FALSE)
         $obj = \DateTime::createFromFormat('Y-m-d H:i:s', $this->value);
      if ($obj !== FALSE)
         return $obj->format('Y-m-d H:i:s');

      return NULL;
   }

   /**
    * Nastavení hodnoty pole
    *
    * @access public
    *
    * @param DateTime|string $value hodnota
    *
    * @return void
    */
   public function setValue($value)
   {

      if ($value instanceof \DateTime) {
         $value = $value->format('j. n. Y H:i');
      } else {
         $value = preg_replace('~([0-9]{4})-([0-9]{2})-([0-9]{2})~', '$3.$2.$1', $value);
      }

     parent::setValue($value);
   }

   /**
    * Generování HTML elementu
    *
    * @access public
    *
    * @return Html
    */
   public function getControl()
   {
     $control = parent::getControl();

     $control->class = 'datetimepicker';

     return $control;
   }
 }
?>
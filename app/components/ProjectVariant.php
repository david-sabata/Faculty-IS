<?php

/**
 * Variant - form component
 *
 * @author David Šabata
 */
class ProjectVariant extends Nette\Forms\FormControl {

   const SUFF_TITLE = '_title';
   const SUFF_DESCRIPTION = '_desc';
   const SUFF_MAXTEAMS = '_maxteams';
   const SUFF_MAXMEMBERS = '_maxmembers';
   const SUFF_NOTEAMS = '_noteams';
   const SUFF_DBID = '_dbId';


   private $title, $description, $maxteams, $maxmembers, $noteams, $dbId;

   public function __construct($name, $title = NULL) {
      parent::__construct($name);

      $this->title = $title;
   }



   /**
    * Sets control's value.
    * @param array(title, description, maxteams, maxmembers, noteams)
    * @return Varian
    */
   public function setValue($value) {
      if (!is_array($value))
         return $this;

      if (isset($value['title']))
         $this->title = $value['title'];

      if (isset($value['description']))
         $this->description = $value['description'];

      if (isset($value['maxteams']))
         $this->maxteams = $value['maxteams'];

      if (isset($value['maxmembers']))
         $this->maxmembers = $value['maxmembers'];

      if (isset($value['noteams']))
         $this->noteams = $value['noteams'];

      if (isset($value['dbId']))
         $this->dbId = $value['dbId'];

      return $this;
   }


   public function getValue() {
      return array(
         'title' => $this->title,
         'description' => $this->description,
         'maxteams' => $this->maxteams,
         'maxmembers' => $this->maxmembers,
         'noteams' => $this->noteams,
         'dbId' => $this->dbId,
      );
   }

   
   /**
    * Loads HTTP data.
    */
   public function loadHttpData() {
      $name = $this->getHtmlName();
      $data = $this->getForm()->getHttpData();
      $v = array(
         'title' => Nette\ArrayTools::get($data, $name . self::SUFF_TITLE),
         'description' => Nette\ArrayTools::get($data, $name . self::SUFF_DESCRIPTION),
         'maxteams' => Nette\ArrayTools::get($data, $name . self::SUFF_MAXTEAMS),
         'maxmembers' => Nette\ArrayTools::get($data, $name . self::SUFF_MAXMEMBERS),
         'noteams' => Nette\ArrayTools::get($data, $name . self::SUFF_NOTEAMS),
         'dbId' => Nette\ArrayTools::get($data, $name . self::SUFF_DBID) ? Nette\ArrayTools::get($data, $name . self::SUFF_DBID) : NULL,
      );
      $this->setValue($v);
   }


   /**
    * Generates control's HTML element.
    * @return Nette\Templates\FileTemplate
    */
   public function getControl() {
      $control = parent::getControl();

      $t = new \Nette\Templates\FileTemplate(__DIR__ . '/ProjectVariant.phtml');
      $t->registerFilter(new \Nette\Templates\LatteFilter());

      $t->names = array(
         'base' => $this->getHtmlName(),
         'title' => $this->getHtmlName() . self::SUFF_TITLE,
         'description' => $this->getHtmlName() . self::SUFF_DESCRIPTION,
         'maxteams' => $this->getHtmlName() . self::SUFF_MAXTEAMS,
         'maxmembers' => $this->getHtmlName() . self::SUFF_MAXMEMBERS,
         'noteams' => $this->getHtmlName() . self::SUFF_NOTEAMS,
         'dbId' => $this->getHtmlName() . self::SUFF_DBID,
      );

      $t->ids = array(
         'base' => $this->getHtmlId(),
         'title' => $this->getHtmlId() . self::SUFF_TITLE,
         'description' => $this->getHtmlId() . self::SUFF_DESCRIPTION,
         'maxteams' => $this->getHtmlId() . self::SUFF_MAXTEAMS,
         'maxmembers' => $this->getHtmlId() . self::SUFF_MAXMEMBERS,
         'noteams' => $this->getHtmlId() . self::SUFF_NOTEAMS,
      );

      $t->values = $this->getValue();

      return $t;
   }

}

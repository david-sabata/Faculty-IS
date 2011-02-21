<?php

/**
 * Datagrid with basic settings
 *
 * @author David Šabata
 */
class BaseGrid extends DataGrid {

   public function  __construct() {
      parent::__construct();
      
      $renderer = $this->getRenderer();
      $renderer->infoFormat='Položky %from% - %to% z celkem %count% | Zobrazit na stranu: %selectbox% | %reset%';

      $this->setTranslator(new GridTranslator());
   }

}
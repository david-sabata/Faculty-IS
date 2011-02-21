<?php

use Nette\Application\Presenter,
   Nette\Environment;


/**
 * Base presenter for all backend ones.
 * PublicModule presenters are NOT descendants of this!
 *
 * This is the place for all shared methods, settings, etc.
 *
 * @author David Šabata
 */
abstract class BasePresenter extends Presenter {

   // css classes for flash messages
   const FLASH_GREEN = 'green';
   const FLASH_ORANGE = 'orange';
   const FLASH_RED = 'red';

   const ACCESS_DENIED = 'Nemáte oprávnění k provedení této akce';


   public function startup() {
      parent::startup();

      $user = $this->getUser();      

      // allow only authenticated users
      if (!$user->isLoggedIn())
         $this->redirect(':Public:Default:');

      // predefined resources/privileges - editable only in db
      $resources = Environment::getConfig('acl')->resourceNames;
      $privilege = Environment::getConfig('acl')->privilegeNames->signUp;

      // check permissions
      if ( ( preg_match('/^Admin:/', $this->name) && !$user->isAllowed($resources->adminModule, $privilege) ) ||
           ( preg_match('/^Teacher:/', $this->name) && !$user->isAllowed($resources->teacherModule, $privilege) ) ||
           ( preg_match('/^Student:/', $this->name) && !$user->isAllowed($resources->studentModule, $privilege)) )
      {
         $this->flashMessage('Do této části systému nemáte přístup', 'red');
         $this->redirect(':Public:Default:');
      }
   }


   public function  beforeRender() {
      parent::beforeRender();

      // register custom template helper to format full names
      $this->template->registerHelper('formatName', function($user) {
         if (is_object($user)) {
            $name = $user->name . ' ' . $user->surname;
            if ($user->name_prefix)
               $name = $user->name_prefix . ' ' . $name;
            if ($user->name_suffix)
               $name .= ', ' . $user->name_suffix;
         } else {
            $name = $user['name'] . ' ' . $user['surname'];
            if ($user['name_prefix'])
               $name = $user['name_prefix'] . ' ' . $name;
            if ($user['name_suffix'])
               $name .= ', ' . $user['name_suffix'];
         }

         return $name;
      } );


      // register custom helper to format marks
      $this->template->registerHelper('formatMark', function($rating) {
         if ($rating < 50)
            return '<span class="red">F</span>';
         elseif ($rating < 60)
            return '<span class="green">E</span>';
         elseif ($rating < 70)
            return '<span class="green">D</span>';
         elseif ($rating < 80)
            return '<span class="green">C</span>';
         elseif ($rating < 90)
            return '<span class="green">B</span>';
         else
            return '<span class="green">A</span>';
      });

      // use each module's own 'sublayout' specifying menu
      // and extending the global backend layout (app/templates/@layout.phtml)
      $this->setLayout('sublayout');
   }


   /**
    * Logs out the current user and redirects to login page
    * @param bool $silent show a flash message?
    */
   public function actionLogout($silent = FALSE) {
      $this->getUser()->logout(TRUE); // force identity removal

      if (!$silent)
         $this->flashMessage('Byl jste úspěšně odhlášen');

      $this->redirect(':Public:Default:');
   }



   /**
    * Custom implementation of ifCurrent macro with support of wildcards
    * @param mixed ...
    * @return bool
    */
   public function isCurrent() {
      $args = func_get_args();
      $fullName = $this->name . ':' . $this->action;

      foreach ($args as $arg) {
         // turn into regexp
         $arg = str_replace('*', '[^:]*', $arg);
         if (preg_match('/' . $arg . '/', $fullName))
            return TRUE;
      }

      return FALSE;
   }

}

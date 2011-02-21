<?php

namespace PublicModule;

use Nette\Forms\Form,
   Nette\Forms\SubmitButton,
   Nette\Environment,
   Nette\Web\User;

/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class DefaultPresenter extends \Nette\Application\Presenter {

   /**
    * Redirects already logged user
    */
   public function actionDefault() {

      $user = $this->getUser();

      // redirect already logged user to the right module
      if ($user->isLoggedIn())
         $this->redirectToModule();
      else if ($user->getLogoutReason() === User::INACTIVITY)
         $this->flashMessage('Z důvodů neaktivity jste byl odhlášen. Přihlašte se prosím znovu');               
   }


   /**
    * Login form factory
    * @return \Nette\Application\AppForm
    */
   public function createComponentLogin() {
      $f = new \Nette\Application\AppForm($this, 'login');

      $f->addText('username', 'Login')
         ->addRule(Form::FILLED, 'Zadejte prosím Váš login');

      $f->addPassword('password', 'Heslo')
         ->addRule(Form::FILLED, 'Zadejte prosím Vaše heslo');

      $f->addSubmit('login', 'Přihlásit')
         ->onClick[] = array($this, 'loginSubmitted');

      $f->getElementPrototype()->class('login');

      return $f;
   }


   /**
    * Login form submit handler. On successful login redirects to the right module.
    * @param SubmitButton $btn
    */
   public function loginSubmitted(SubmitButton $btn) {
      $form = $btn->getForm();
      
      try {
         $values = $form->values;
         
         $this->getUser()->login($values['username'], $values['password']);
         
         // redirect to any module, automatic redirection in backend BasePresenter
         // will pass the redirection to the right module according to roles
         $this->redirectToModule();

      } catch (\Nette\Security\AuthenticationException $e) {
         $form->addError($e->getMessage());
      }
   }


   /**
    * Redirects the logged in user to the right module according to their role
    */
   public function redirectToModule() {
      // predefined resources/privileges - editable only in db
      $resources = Environment::getConfig('acl')->resourceNames;
      $privilege = Environment::getConfig('acl')->privilegeNames->signUp;
      $user = $this->getUser();     
      
      if ($user->isAllowed($resources->adminModule, $privilege))
         $this->redirect(':Admin:Default:');      
      elseif ($user->isAllowed($resources->studentModule, $privilege))
         $this->redirect(':Student:Overview:');      
      elseif ($user->isAllowed($resources->teacherModule, $privilege))
         $this->redirect(':Teacher:Default:');

      // fallback - no role
      //$this->redirect(':Public:Default:');
   }

   
   /**
    * Initialize the database to default state
    */
   public function actionInit() {
      if (!\file_exists(\WWW_DIR . \DIRECTORY_SEPARATOR . 'db.sql')) {
         $this->flashMessage('Soubor s výchozím obsahem databáze neexistuje', 'red');
         $this->redirect('default');
      }

      $sql = \file_get_contents(WWW_DIR . \DIRECTORY_SEPARATOR . 'db.sql');
      $trunc = 'SET foreign_key_checks = 0; DROP TABLE `project_variants`, `projects`, `students_in_subjects`, `students_in_teams`, `students_in_variants`, `subjects`, `teachers_in_subjects`, `team_requests`, `teams`, `users`, `users_acl`, `users_in_roles`, `users_privileges`, `users_resources`, `users_roles`';

      $queries = explode(';', $trunc);
      foreach ($queries as $q) {
         try {
            \dibi::query($q); // drop all tables in db
         } catch(\DibiDriverException $e) { }
      }

      $queries = explode(';', $sql);
      foreach ($queries as $q) {
         try {
            \dibi::query($q); // import tables from file
         } catch(DibiDriverException $e) { }
      }

      $this->flashMessage('Databáze byla obnovena do výchozího stavu', 'green');
      $this->redirect('default');
   }

}


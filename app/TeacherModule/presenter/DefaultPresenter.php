<?php

namespace TeacherModule;

/**
 * Default entry point to teacher backend. Suitable for any kind
 * of overview. In this project we just redirect the teachers to
 * list of subject tought by them.
 *
 * @author David Šabata
 */
class DefaultPresenter extends \BasePresenter {

   /**
    * Redirect every request to SubjectsPresenter, default action
    */
   public function startup() {
      parent::startup();

      $this->redirect('Subjects:');
   }

}
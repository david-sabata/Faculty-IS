<?php

namespace AdminModule;

use Nette\Application\AppForm,
   Nette\Forms\Form;

/**
 * Description of SubjectsPresenter
 *
 * @author David Šabata
 */
class SubjectsPresenter extends \BasePresenter {

   /**
    * Datagrid factory ( called by {widget grid} )
    * @return \BaseGrid
    */
   public function createComponentGrid() {

      $model = new \SubjectsModel();

      $grid = new \BaseGrid;
      $grid->bindDataTable( $model->getSubjects() ); // binds DibiDataSource
      $grid->keyName = 'id'; // for actions or operations
      $grid->defaultOrder = 'year=d';

      $grid->addColumn('code', 'Zkratka')
               ->addFilter();
      $grid->addColumn('name', 'Název předmětu')
               ->addFilter();
      $grid->addColumn('year', 'Akademický rok')
               ->addFilter();
      $grid->addColumn('semester', 'Semestr')
               ->addSelectboxFilter(array('S' => 'Léto', 'W' => 'Zima'));
      $grid->addNumericColumn('credits', 'Kreditů')
               ->addFilter();
      $grid->addNumericColumn('students', 'Zapsaných studentů')
               ->addFilter();

      $grid['semester']->replacement['S'] = 'Léto';
      $grid['semester']->replacement['W'] = 'Zima';
      $grid['year']->formatCallback[] = function ($value) {
         return $value . ' / ' . ($value + 1);
      };

      $grid->addActionColumn('Akce');
      $icon = \Nette\Web\Html::el('span');
      $grid->addAction('Přidat předmět', 'add', clone $icon->class('icon icon-add'), FALSE, \DataGridAction::WITHOUT_KEY);
      $grid->addAction('Upravit', 'edit', clone $icon->class('icon icon-edit'));
      $grid->addAction('Odstranit', 'delete', clone $icon->class('icon icon-del'));

      return $grid;
   }

   
   /**
    * Creates a new subject and redirects to edit form
    */
   public function actionAdd() {
      $usersModel = new \UsersModel();
      $users = $usersModel->getUsersInRole( \Nette\Environment::getConfig('roles')->employee );
      $this->template->teachers = array('' => '---') + (array)$users;
      
      $this->setView('edit');
   }


   /**
    * We have to specify the function arguments so that the route
    * will know the $id is required parameter
    * @param int $id
    */
   public function actionEdit($id) {
      $usersModel = new \UsersModel();
      $users = $usersModel->getUsersInRole( \Nette\Environment::getConfig('roles')->employee );
      $this->template->teachers = array('' => '---') + (array)$users;
   }


   /**
    * Edit form factory (called by {widget editForm} )
    * @return \Nette\Application\AppForm
    */
   public function createComponentEditForm() {
      $f = new AppForm($this, 'editForm');
      $model = new \SubjectsModel();
      $id = $this->getParam('id');

      $f->addText('name', 'Název předmětu', NULL, 50)
         ->addRule(Form::FILLED, 'Vyplňte prosím název předmětu');

      $f->addText('code', 'Zkratka', 5, 5)
         ->addRule(Form::FILLED, 'Vyplňte prosím zkratku předmětu');

      $f->addText('year', 'Akademický rok', NULL, 11)
         ->addRule(Form::FILLED, 'Vyplňte prosím akademický rok')
         ->addRule(Form::REGEXP, 'Zadejte prosím platný akademický rok ve tvaru "2010 / 2011" nebo pouze "2010"', '/^[1-9]+[0-9]{3}(\s*\/\s*[1-9][0-9]{3})?$/')
         ->addRule(function($input) {
            preg_match('/^([1-9]+[0-9]{3})(\s*\/\s*([1-9][0-9]{3}))?$/', $input->value, $arr);
            return (count($arr) == 2 || (count($arr) == 4 && $arr[1] == $arr[3]-1));
         }, 'Zadejte prosím platný akademický rok');

      if ($id === NULL)
         $f['year']->setDefaultValue(date('Y') . ' / ' . (date('Y') + 1));

      $items = array('S' => 'Letní', 'W' => 'Zimní');
      $f->addRadioList('semester', 'Semestr', $items)
         ->addRule(Form::FILLED, 'Vyberte prosím semestr');

      $f->addText('credits', 'Počet kreditů', 5)
         ->addRule(Form::FILLED, 'Zadejte prosím počet kreditů')
         ->addRule(Form::RANGE, 'Počet kreditů musí být v rozmezí %d - %d', array(0, 999));

      // will be filled by javascript to simulate the dynamic addition of users
      $f->addText('teachers', 'Vyučující');      

      $f->addSubmit('save', 'Uložit')
         ->onClick[] = array($this, 'editFormSubmitted');

      $f->addSubmit('cancel', 'Zpět')
         ->setValidationScope(NULL)
         ->onClick[] = array($this, 'editFormCancelled');

      // preformat defaults
      if ($id !== NULL) {
         $defaults = $model->getSubject($id);        

         // 2009   ->   2009 / 2010
         $defaults['year'] = $defaults['year'] . ' / ' . ($defaults['year'] + 1);

         $f->setDefaults( $defaults );
      }

      return $f;
   }


   /**
    * Form submit handler. Gets executed only when the form was submitted with
    * valid data. Saves the data and redirects to the overview.
    * @param \Nette\Forms\SubmitButton $btn
    */
   public function editFormSubmitted(\Nette\Forms\SubmitButton $btn) {
      $model = new \SubjectsModel();
      $f = $btn->getForm();
      $id = $this->getParam('id'); // NULL when creating a new record
      $values = $f->getValues();

      // 2009 / 2010   or just   2009
      preg_match('/([0-9]+)(\s?\/\s?[0-9]+)?/', $values['year'], $arr);
      $values['year'] = $arr[0];

      // save teachers_in_subjects
      $teachers = explode(',', $values['teachers']);
      unset($values['teachers']);
      $set = array();
      foreach($teachers as $t)
         if ($t && array_search($t, $set) === false)
            $set[] = $t;

      if (!empty($set))
         $model->setTeachers($id, $set);

      if ( $model->save($id, $values) ) {
         $this->flashMessage('Uloženo', self::FLASH_GREEN);
         $this->redirect('default');
      } else {
         $f->addError('Nepodařilo se uložit záznam');
      }
   }

   /**
    * Form cancel handler. Redirects to the overview
    * @param \Nette\Forms\SubmitButton $btn
    */
   public function editFormCancelled(\Nette\Forms\SubmitButton $btn) {
      $this->redirect('default');
   }


   /**
    * Attempts to delete the role and redirects back to overview
    * @param int $id
    */
   public function actionDelete($id) {
      $model = new \SubjectsModel();

      if ( $model->delete($id) ) {
         $this->flashMessage('Odstraněno', self::FLASH_GREEN);         
      } else {
         $this->flashMessage('Záznam nelze odstranit. Na tento předmět jsou zapsáni studenti', self::FLASH_RED);
      }

      $this->redirect('default');
   }


}
<?php

namespace AdminModule;

use Nette\Application\AppForm,
    Nette\Forms\Form;

/**
 * Description of UsersPresenter
 *
 * @author David Šabata
 */
class UsersPresenter extends \BasePresenter {


   /**
    * Datagrid factory ( called by {widget grid} )
    * @return \BaseGrid
    */
   public function createComponentGrid() {

      $model = new \UsersModel();

      $grid = new \BaseGrid;
      $grid->bindDataTable( $model->getUsers() ); // binds DibiDataSource
      $grid->keyName = 'login'; // for actions or operations

      $grid->addColumn('login', 'Login')
               ->addFilter();
      $grid->addColumn('test', 'Celé jméno')
               ->addFilter();
      $grid->addColumn('roles', 'Role')
               ->addFilter();

      $grid->addActionColumn('Akce');
      $icon = \Nette\Web\Html::el('span');
      $grid->addAction('Přidat uživatele', 'add', clone $icon->class('icon icon-add'), FALSE, \DataGridAction::WITHOUT_KEY);
      $grid->addAction('Upravit', 'dummy-edit', clone $icon->class('icon icon-edit'));
      $grid->addAction('Odstranit', 'delete', clone $icon->class('icon icon-del'));

      return $grid;
   }

   /**
    * Shows the empty edit form
    */
   public function actionAdd() {
      $this->setView('edit');
   }

   /**
    * Dummy action. Datagrid doesn't support custom parameters and since
    * the primary column here is login, it passes the key in 'login' param.
    * Here we just change it to id which is defined in route and is always
    * required to be in the address. So the form doesn't lose the login
    * after submitting.
    */
   public function actionDummyEdit() {
      $this->redirect('edit', $this->getParam('login'));
   }

   /**
    * We have to specify the function arguments so that the route
    * will know the $id is required parameter
    * @param int $id
    */
   public function actionEdit($id) { }

   /**
    * Edit form factory (called by {widget editForm} )
    * @return \Nette\Application\AppForm
    */
   public function createComponentEditForm() {
      $f = new AppForm($this, 'editForm');
      $model = new \UsersModel();
      $login = $this->getParam('id');
      $defaults = $model->getUser($login);
      $userExists = $defaults && !empty($defaults);

      $f->addText('login', 'Login', NULL, 8);
      if ($userExists) {
         $f['login']->setDisabled(TRUE);
      } else {
         $f['login']->addRule(Form::FILLED, 'Vyplňte prosím login uživatele');
      }

      $f->addPassword('password', 'Heslo');
      if (!$userExists)
         $f['password']->addRule(Form::FILLED, 'Zadejte prosím nové heslo');

      $f->addText('name_prefix', 'Titul před jménem', 5, 15);
      
      $f->addText('name', 'Křestní jméno', 10, 20)
         ->addRule(Form::FILLED, 'Zadejte prosím křestní jméno');

      $f->addText('surname', 'Příjmení', 10, 20)
         ->addRule(Form::FILLED, 'Zadejte prosím příjmení');

      $f->addText('name_suffix', 'Titul za jménem', 5, 15);

      
      $f['name_prefix']->getControlPrototype()->placeholder = 'Ing.';
      $f['name']->getControlPrototype()->placeholder = 'Jan';
      $f['surname']->getControlPrototype()->placeholder = 'Novák';
      $f['name_suffix']->getControlPrototype()->placeholder = 'Ph.D';


      // get roles
      $rolesContainer = $f->addContainer('role');
      $rolesModel = new \RolesModel();
      $roles = $rolesModel->getRoles();
      foreach ($roles as $role)
         $rolesContainer->addCheckbox($role->id, $role->name);

      $f->addSubmit('save', 'Uložit')
         ->onClick[] = array($this, 'editFormSubmitted');

      $f->addSubmit('cancel', 'Zpět')
         ->setValidationScope(NULL)
         ->onClick[] = array($this, 'editFormCancelled');

      if ($login != NULL)
         $f->setDefaults( (array)$model->getUser($login) + array('role' => $rolesModel->getRolesForUser($login)) );

      return $f;
   }


   /**
    * Form submit handler. Gets executed only when the form was submitted with
    * valid data. Saves the data and redirects to the overview.
    * @param \Nette\Forms\SubmitButton $btn
    */
   public function editFormSubmitted(\Nette\Forms\SubmitButton $btn) {
      $model = new \UsersModel();
      $f = $btn->getForm();      
      $values = $f->getValues();
      $login = $this->getParam('id') ? $this->getParam('id') : $values['login'];

      // handle roles in the rolesModel      
      $roles = array();
      foreach ($values['role'] as $k => $v)
         if ($v === TRUE)
            $roles[] = $k;
         
      unset($values['role']);

      // additional roles validation
      if (empty($roles)) {
         $f->addError('Vyberte prosím alespoň jednu roli');
         return;
      }

      // insert/edit operation status
      $success = TRUE;

      // save/add the record
      $success = $model->save($login, $values);

      // set new roles
      $rolesModel = new \RolesModel();
      $rolesModel->setUserInRole($login, $roles);      

      if ($success) {
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
    * Attempts to delete the user and redirects back to overview
    * Uses presenter parameter $login passed in the route
    */
   public function actionDelete() {
      $model = new \UsersModel();
      $login = $this->getParam('login');

      if ( $model->delete($login) ) {
         $this->flashMessage('Odstraněno', self::FLASH_GREEN);
      } else {
         $this->flashMessage('Záznam nelze odstranit', self::FLASH_RED);
      }

      $this->redirect('default');
   }




   /**
    * Datagrid factory for students overview
    * @return \BaseGrid
    */
   public function createComponentStudentsGrid() {

      $model = new \UsersModel();

      $studentRole = \Nette\Environment::getConfig('roles')->student;

      $grid = new \BaseGrid;
      $grid->bindDataTable( $model->getUsersInSingleRole($studentRole) ); // binds DibiDataSource
      $grid->keyName = 'login'; 

      $grid->addColumn('login', 'Login')
               ->addFilter();
      $grid->addColumn('test', 'Celé jméno')
               ->addFilter();

      $grid->addActionColumn('Akce');
      $icon = \Nette\Web\Html::el('span');
      $grid->addAction('Registrovat předměty', 'dummyEditSubjects', clone $icon->class('icon icon-edit'));

      return $grid;
   }


   /**
    * Dummy action. Datagrid doesn't support custom parameters and since
    * the primary column here is login, it passes the key in 'login' param.
    * Here we just change it to id which is defined in route and is always
    * required to be in the address. So the form doesn't lose the login
    * after submitting.
    */
   public function actionDummyEditSubjects() {
      $this->redirect('editSubjects', $this->getParam('login'));
   }

   /**
    * Edit students subjects
    * @param string $id student login
    */
   public function actionEditSubjects($id) {
      $roleId = \Nette\Environment::getConfig('roles')->student;
      $model = new \UsersModel();
      $ds = $model->getUsersInSingleRole($roleId)->where('login=%s', $id);
      $this->template->student = $ds->fetch();

      // if user is empty, there is no student with that login
      if (!$this->template->student)
         $this->redirect(':Public:Error:e404');
   }


   /**
    * Form for editing students subjects factory
    * @return AppForm
    */
   public function createComponentEditSubjects() {
      $f = new AppForm($this, 'editSubjects');

      // get all subjects - here would be good place to apply some filter,
      // for example to an actual academic year or semester
      $subjectsModel = new \SubjectsModel();
      $ds = $subjectsModel->getSubjects()->orderBy('code');
      $subjects = $ds->fetchAll();
      
      foreach ($subjects as $subject) {
         $title = $subject->code . ' - ' . $subject->name . ' (' . $subject->year . '/' . ($subject->year+1) . ')';
         $f->addCheckbox($subject->id, $title);
      }


      $f->addSubmit('save', 'Uložit')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) use ($subjectsModel) {
            $f = $btn->getForm();
            $v = $f->getValues();
            $p = $f->lookup('\Nette\Application\Presenter');

            // Check the constrains - subject can't be unregistered when the student
            // is signed up for a project of the subject
            // This constrain cannot be declared in database
            $projectsModel = new \ProjectsModel();
            $projects = $projectsModel->getProjectsForStudent( $p->getParam('id') );

            $subjectNames = array(); // to display a nicer error message
            $projectSubjects = array();
            foreach($projects as $project) {
               $projectSubjects[] = $project->subject;
               $subjectNames[$project->subject] = $project->subject_name;
            }

            $toRegister = array();
            $toUnregister = array();            

            // check the constrains
            foreach ($v as $id => $value) {
               if ($value === FALSE && array_search($id, $projectSubjects) !== FALSE) {
                  $f->addError('Nelze odregistrovat předmět ' . $subjectNames[$id] . ', student má v tomto předmětu zapsaný projekt');
                  return;
               }

               if ($value === TRUE)
                  $toRegister[] = $id;
               else
                  $toUnregister[] = $id;
            }

            // student has no commitments in the subjects to be unregistered
            $subjectsModel->updateStudentSubjects($p->getParam('id'), $toRegister, $toUnregister);

            $p->flashMessage('Uloženo', $p::FLASH_GREEN);
            $p->redirect('register');
         };

      $f->addSubmit('cancel', 'Zpět')
         ->setValidationScope(FALSE)
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $btn->getForm()->lookup('\Nette\Application\Presenter')->redirect('register');
         };

      
      if (!$f->isSubmitted()) {
         // get the defaults
         $alreadyRegistered = $subjectsModel->getSubjectsByStudent( $this->getParam('id') );
         $defs = array();
         foreach ($alreadyRegistered as $s)
            $defs[$s->id] = TRUE;

         $f->setDefaults($defs);
      }

      return $f;
   }


   /**
    * @return \Nette\Application\AppForm
    */
   public function createComponentExportSettings() {
      $f = new AppForm($this, 'exportSettings');

      $subjectsModel = new \SubjectsModel();
      $years = $subjectsModel->getYears();
      
      foreach ($years as $year)
         $f->addCheckbox ($year, $year . '/' . ($year+1))
            ->setDefaultValue(TRUE);

      $f->addSubmit('export', 'Exportovat')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $p = $f->lookup('Nette\Application\Presenter');
            $v = $f->getValues();

            $years = array();
            foreach ($v as $y => $checked)
               if ($checked)
                  $years[] = $y;

            // all years has been selected
            if (count($years) == count($v))
               $p->redirect('doExport');
            else
               $p->redirect('doExport', array('years' => $years));
         };

      return $f;
   }


   /**
    * Exports registered students to XML
    * Years to be exported are in param 'years'. If the param
    * is not present, all years will be exported
    */
   public function actionDoExport() {
      $years = $this->getParam('years');

      $subjectsModel = new \SubjectsModel();
      $subjects = $subjectsModel->getSubjectsByYear($years);

      // get subject ids
      $subjectIds = array();
      foreach ($subjects as $subs)
         $subjectIds = array_merge($subjectIds, array_keys($subs));      

      $usersModel = new \UsersModel();
      $students = $usersModel->getStudentsInSubjects($subjectIds);

      $this->template->students = $students;
      $this->template->years = $subjects;
   }


   /**
    * Creates the import form
    * @return AppForm
    */
   public function createComponentImport() {
      $f = new AppForm($this, 'import');

      $f->addFile('import', 'Vyberte soubor k importu')
         ->addRule(Form::FILLED, 'Vyberte prosím soubor k importu')
         ->addRule(Form::MIME_TYPE, 'Importovaný soubor musí být ve formátu XML', 'application/xml,text/xml');

      $f->addSubmit('save', 'Importovat')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $p = $f->lookup('Nette\Application\Presenter');
            $v = $f->getValues();
            $subjectsModel = new \SubjectsModel();

            // parse XML
            $xml = \simplexml_load_file($v['import']->temporaryFile);

            $registrations = array();

            foreach ($xml->year as $year) {
               foreach ($year->subject as $subject) {    
                  // either ID or CODE+year has to be set
                  if (!empty($subject['id'])) {
                     ; // do nothing, it's ok
                  } elseif (!empty($subject['code']) && !empty($year['number'])) {
                     $subject['id'] = $subjectsModel->lookupId((string)$subject['code'], (string)$year['number']);
                  }
                  if (empty($subject['id'])) {
                     $f->addError('Importovaný soubor obsahuje chybu! Předmět '.$subject['title'].' nemá nastaveno ID ani jej nelze dohledat z kombinace zkratky předmětu a akademického roku.');
                     return;
                  }
                  
                  foreach ($subject->student as $student) {
                     // login has to be set
                     if (empty($student['login'])) {
                        $f->addError('Importovaný soubor obsahuje chybu! Student nemá definovaný login.');
                        return;
                     }

                     $registrations[] = array('login' => (string)$student['login'], 'id' => (string)$subject['id']);
                  }
               }
            }

            // get all students
            $usersModel = new \UsersModel();
            $students = $usersModel->getUsersInRole( \Nette\Environment::getConfig('roles')->student );

            // check registrations against valid logins
            $warnings = array();
            foreach ($registrations as $r) {
               if (!isset($students[ $r['login'] ]))
                  $warning[] = 'Login \'' . $r['login'] . '\' není platný studentský login.';
            }

            // incremental registration of students for subjects
            $res = $usersModel->registerStudents($registrations);
            
            if (!empty($warnings))
               $p->flashMessage('Dokončeno, při zpracování však nastaly chyby.<br />' . implode('<br />', $warnings), $p::FLASH_ORANGE);
            else
               $p->flashMessage('Import byl úspěšně dokončen. Vytvořeno ' . $res . ' nových registrací', $p::FLASH_GREEN);

            $p->redirect('this');
         };

      return $f;
   }

}
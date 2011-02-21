<?php

namespace TeacherModule;

use \Nette\Web\Html,
   \Nette\Environment,
   \Nette\Forms\Form,
   \Nette\Application\AppForm;

/**
 * Description of SubjectsPresenter
 *
 * @author David Šabata
 */
class SubjectsPresenter extends \BasePresenter {


   /**
    * Datagrid factory
    * @return \BaseGrid
    */
   public function createComponentActualSubjects() {

      $model = new \SubjectsModel();

      $grid = new \BaseGrid;
      $ds = $model->getSubjectsByTeacher( $this->getUser()->getId() );
      $ds->where('year=%i', date('Y'));
      $grid->bindDataTable( $ds ); // binds DibiDataSource
      $grid->keyName = 'id'; // for actions or operations
      $grid->defaultOrder = 'code=a';

      $grid->addColumn('code', 'Zkratka')
               ->addFilter();
      $grid->addColumn('name', 'Název předmětu')
               ->addFilter();
      $grid->addColumn('semester', 'Semestr')
               ->addSelectboxFilter(array('S' => 'Léto', 'W' => 'Zima'));
      $grid->addNumericColumn('credits', 'Kreditů')
               ->addFilter();      

      $grid['semester']->replacement['S'] = 'Léto';
      $grid['semester']->replacement['W'] = 'Zima';
      $grid['name']->formatCallback[] = function ($value, $row) {
         $href = Environment::getApplication()->getPresenter()->link('detail', $row['id']);
         return Html::el('a')->href($href)->setText($value);
         //return $value;
      };
      
      $grid->addActionColumn('Akce');
      $icon = Html::el('span');
      $grid->addAction('Detaily předmětu', 'detail', clone $icon->class('icon icon-detail'));
      $grid->addAction('Zadat projekt', 'addProject', clone $icon->class('icon icon-add-project'));

      return $grid;
   }




   /**
    * Subject details
    * @param int $id
    */
   public function actionDetail($id) {
      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($id);

      // non-existing subject
      if (!$subject)
         $this->redirect(':Public:Error:e404');

      $projectsModel = new \ProjectsModel();
      $p = $this->template->projects = $projectsModel->getProjectsForSubject($subject->id);     

      // check permissions
      $aclModel = new \AclModel();
      $this->template->editAllowed = $aclModel->isAllowedToEditProject($this->getUser(), $subject->id);
      $this->template->rateAllowed = $aclModel->isAllowedToRateProject($this->getUser(), $subject->id);
   }


   /**
    * Shows a form for creating a new project
    * @param int $id subject id
    */
   public function actionAddProject($id) {
      // look-up subject
      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($id);
      if (!$subject)
         $this->redirect(':Public:Error:e404');

      $this['editProject']->setDefaults( array('subject' => $subject->id) );

      // check permissions
      $aclModel = new \AclModel();
      if (!$aclModel->isAllowedToEditProject($this->getUser(), $subject->id)) {
         $this->flashMessage(self::ACCESS_DENIED, self::FLASH_RED);
         $this->redirect('detail', $subject->id);
      }

      $this->setView('editProject');
   }


   
   /**
    * Edit project
    * @param int $id project id
    */
   public function actionEditProject($id) {
      // look-up project
      $projectsModel = new \ProjectsModel();
      $project = $this->template->project = $projectsModel->getProject($id);
      $this['editProject']->setDefaults( $this->formatEditProjectDefaults($project) );

      // non-existing project
      if (!$project)
         $this->redirect(':Public:Error:e404');

      // look-up associated subject
      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($project->subject);

      // check permissions
      $aclModel = new \AclModel();
      if (!$aclModel->isAllowedToEditProject($this->getUser(), $subject->id)) {
         $this->flashMessage(self::ACCESS_DENIED, self::FLASH_RED);
         $this->redirect('detail', $subject->id);
      }
   }

   /**
    * Edit project form
    * @return \Nette\Application\AppForm
    */
   public function createComponentEditProject() {
      $f = new AppForm($this, 'editProject');

      $f->addHidden('id');
      $f->addHidden('subject');

      $f->addText('title', 'Název termínu', 40, 200)
         ->addRule(Form::FILLED, 'Zadejte prosím název termínu');

      $f->addTextArea('text', 'Popis termínu');

      $f->addText('max_points', 'Maximum bodů', 3, 4)
         ->addRule(Form::FILLED, 'Zadejte maximální počet bodů z tohoto termínu')
         ->addRule(Form::FLOAT, 'Zadejte prosím číslo')
         ->addRule(Form::RANGE, 'Počet bodů musí být nezáporné číslo', array(0, null));

      $f->addText('min_points', 'Minimum bodů', 3, 4)
         ->addCondition(Form::FILLED)
            ->addRule(Form::FLOAT, 'Zadejte prosím číslo')
            ->addRule(Form::RANGE, 'Počet bodů musí být nezáporné číslo', array(0, null));
      

      $f->addDateTimePicker('signup_from', 'Začátek přihlašování');
      $f->addDateTimePicker('signup_until', 'Konec přihlašování');

      $f['signup_until']->addConditionOn($f['signup_from'], Form::FILLED)
                        ->addRule(Form::FILLED, 'Pokud zadáte začátek přihlašování, musíte zadat i jeho konec');
      $f['signup_from']->addConditionOn($f['signup_until'], Form::FILLED)
                        ->addRule(Form::FILLED, 'Pokud zadáte konec přihlašování, musíte zadat i jeho začátek');         

      $f->addDateTimePicker('submit_from', 'Začátek odevzdávání');
      $f->addDateTimePicker('submit_until', 'Konec odevzdávání');

      $f['submit_until']->addConditionOn($f['submit_from'], Form::FILLED)
                        ->addRule(Form::FILLED, 'Pokud zadáte začátek odevzdávání, musíte zadat i jeho konec');
      $f['submit_from']->addConditionOn($f['submit_until'], Form::FILLED)
                        ->addRule(Form::FILLED, 'Pokud zadáte konec odevzdávání, musíte zadat i jeho začátek');

      $f->addRadioList('submit_files', 'Odevzdávat soubory', array(0 => 'Ne', 1 => 'Ano'))
         ->setDefaultValue(0)
         ->getSeparatorPrototype()->setName('span')->style('width:10px; display:inline-block');

      $f->addRadioList('variants', 'Více variant termínu', array(0 => 'Ne', 1 => 'Ano'))
         ->setDefaultValue(0)
         ->getSeparatorPrototype()->setName('span')->style('width:10px; display:inline-block');

      $f->addHidden('no_variant_id');

      $f->addText('no_variant_maxteams', 'Maximum týmů', 3, 3)
         ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'Zadejte prosím celé číslo')
            ->addRule(Form::RANGE, 'Počet týmů musí být nezáporný', array(0, null));

      $f->addText('no_variant_maxmembers', 'Maximum členů v týmu', 3, 5)
         ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'Zadejte prosím celé číslo')
            ->addRule(Form::RANGE, 'Kapacita musí být nezáporná', array(0, null));

      $f->addCheckbox('no_variant_teams', 'bez týmů');

      $f['no_variant_maxmembers']
         ->addConditionOn($f['variants'], Form::EQUAL, 0)
         ->addConditionOn($f['no_variant_teams'], Form::EQUAL, FALSE)
            ->addRule(Form::FILLED, 'Zadejte prosím maximální počet členů v týmu nebo zvolte termín bez týmů');

      // Generate 50 possible variants fields
      // The new version of the Nette framework (in beta right now) introduces
      // a form element 'dynamic' for that purpose.
      // Right now the only way is to set some (big) maximum count
      for ($i = 0; $i < 50; $i++)
         $f['variant'.$i] = new \ProjectVariant('variant'.$i);

      
      $f->addSubmit('save', 'Uložit')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $v = $f->getValues();
            $p = $f->lookup('\Nette\Application\Presenter');
            $subject = $v['subject'];

            $model = new \ProjectsModel();

            // adding?
            if (empty($v['id']))
               $v['id'] = $model->add($subject);

            if (($res = $model->save($v)) !== TRUE)
               $f->addError('Termín se nepodařilo uložit. ' . $res);
            else {
               $p->flashMessage('Uloženo', $p::FLASH_GREEN);
               $p->redirect('detail', array('id' => $subject));
            }
         };
      
      $f->addSubmit('back', 'Zrušit')
         ->setValidationScope(FALSE)
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $presenter = $btn->getForm()->lookup('\Nette\Application\Presenter');
            $v = $btn->getForm()->getValues();
            $presenter->redirect('detail', array('id' => $v['subject']));
         };

      return $f;
   }


   /**
    * Formats the given data to be useful for the editProject form
    * @param array|DibiRow $project
    * @return array
    */
   protected function formatEditProjectDefaults($project) {
      $defs = $project;

      $model = new \ProjectsModel();
      $variants = $model->getVariants($project->id);

      // format variants as defaults
      $defVariants = array();
      foreach ($variants as $k => $v) {
         $defVariants['variant' . $k] = array(
            'title' => $v['title'],
            'description' => $v['description'],
            'maxteams' => $v['max_teams'],
            'maxmembers' => $v['max_members'],
            'noteams' => !$v['teams_allowed'],
            'dbId' => $v['id'],
         );
      }

      $defs = (array)$defs + (array)$defVariants;

      // if this is a nonvariant project, move the limits to the $defs
      if ($defs['variants'] == 0) {
         $v = $defs['variant0'];
         $defs['no_variant_id'] = $v['dbId'];
         $defs['no_variant_maxteams'] = $v['maxteams'];
         $defs['no_variant_maxmembers'] = $v['maxmembers'];
         $defs['no_variant_teams'] = $v['noteams'];

         unset($defs['variant0']);
      }

      return $defs;
   }



   /**
    * Delete project
    * @param int $id project id
    */
   public function actionRemoveProject($id) {
      // look-up project
      $projectsModel = new \ProjectsModel();
      $project = $this->template->project = $projectsModel->getProject($id);

      // non-existing project
      if (!$project)
         $this->redirect(':Public:Error:e404');

      // look-up associated subject
      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($project->subject);
      
      // check permissions
      $aclModel = new \AclModel();
      if (!$aclModel->isAllowedToEditProject($this->getUser(), $subject->id)) {
         $this->flashMessage(self::ACCESS_DENIED, self::FLASH_RED);
         $this->redirect('detail', $subject->id);
      }

      if ($projectsModel->delete($id))
         $this->flashMessage('Smazáno', self::FLASH_GREEN);
      else
         $this->flashMessage('Termín nelze smazat. Zřejmě jsou na něj přihlášeni studenti nebo týmy.', self::FLASH_RED);

      $this->redirect('this');
   }



   /**
    * Shows students signed for a project (and its variants)
    * @param int $id project id
    */
   public function actionSignedForProject($id) {
      $projectsModel = new \ProjectsModel();
      $project = $this->template->project = $projectsModel->getProject($id);

      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($project->subject);
      $this->template->totalStudents = $subjectsModel->getStudentsCount($subject->id);

      $variants = $this->template->variants = $projectsModel->getVariants($id);

      $signed = $this->template->signed = $projectsModel->getStudentsForProject($id);

      $totalSignedUp = 0;
      foreach ($signed as $s)
         $totalSignedUp += count($s);

      $this->template->totalSignedUp = $totalSignedUp;

      $aclModel = new \AclModel();
      $this->template->rateAllowed = $aclModel->isAllowedToRateProject($this->getUser(), $subject->id);
   }


   /**
    * Rate project
    * @param int $id project id
    */
   public function actionRateProject($id) {       
      $projectsModel = new \ProjectsModel();
      $project = $this->template->project = $projectsModel->getProject($id);

      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($project->subject);

      $variants = $this->template->variants = $projectsModel->getVariants($project->id);


      if ($this->getParam('student')) {
        
         $usersModel = new \UsersModel();
         $student = $this->template->student = $usersModel->getUser($this->getParam('student'));

         $teamsModel = new \TeamsModel();
         $team = $this->template->team = $teamsModel->getTeamForStudent($student->login, $project->id);         
         
         if ($team) {
            $members = $this->template->members = $teamsModel->getStudentsInTeam($team->id);
            $this->template->ratedBy = $usersModel->getUser($team->rated_by);

            $this->createTeamRateForm($team->id);
            //$this['teamRate'.$team->id]->setAction($this->link('this', array('id' => $id, 'student' => $this->getParam('student'))));

            $files = \Nette\Finder::find($id . '-' . $team->leader . '.*')->in(WWW_DIR . \DIRECTORY_SEPARATOR . 'files');
            foreach ($files as $f) {
               $this->template->uploaded = $f->getFilename();
               break;
            }
         }
         else {
            $ids = array();
            foreach ($variants as $v)
               $ids[] = $v->id;

            $variant = $this->template->nonTeamVariant = $projectsModel->getVariantRating($ids, $student->login);
            $this->template->ratedBy = $usersModel->getUser($variant->rated_by);

            $this['rate']->setDefaults(array('rating' => $variant->rate, 'student' => $this->getParam('student')));

            $files = \Nette\Finder::find($id . '-' . $this->getParam('student') . '.*')->in(WWW_DIR . \DIRECTORY_SEPARATOR . 'files');
            foreach ($files as $f) {
               $this->template->uploaded = $f->getFilename();
               break;
            }
         }         
      }
   }



   /**
    * Form to search student to rate
    * @return AppForm
    */
   public function createComponentSearchToRate() {
      $f = new AppForm($this, 'searchToRate');

      $f->addText('student', 'Jméno nebo login studenta')
         ->addRule(Form::FILLED, 'Zadejte prosím jméno nebo login studenta kterého chcete hodnotit');

      $f->addSubmit('search', 'Hledat')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $v = $f->getValues();
            $p = $f->lookup('Nette\Application\Presenter');

            $usersModel = new \UsersModel();
            $user = $usersModel->find($v['student'], $p->getParam('id'));

            if ($user)
               $p->redirect('this', array('student' => $user->login));
            else
               $f->addError('Žádný ze studentů zapsaných na tento termín nevyhovuje hledání');
         };

      return $f;
   }


   
   public function createComponentRate() {
      $f = new AppForm($this, 'rate');

      $projectsModel = new \ProjectsModel();
      $project = $projectsModel->getProject($this->getParam('id'));

      $f->addText('rating', 'Hodnocení', 3)
         ->addRule(Form::FILLED, 'Vložte prosím hodnocení')
         ->addRule(Form::FLOAT, 'Hodnocení zadávejte jako desetinné číslo')
         ->addRule(Form::RANGE, 'Hodnocení musí být v rozmezí %f až %f bodů', array(0, $project->max_points));

      $f->addHidden('student');

      $f->addSubmit('save', 'Uložit')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $v = $f->getValues();
            $p = $f->lookup('Nette\Application\Presenter');

            $projectsModel = new \ProjectsModel();         
            $projectsModel->rateStudent( $p->getParam('id'), $v['student'], $v['rating'], $p->getUser()->id );
            
            $p->flashMessage('Uloženo', 'green');
            $p->redirect('this', array('id' => $p->getParam('id'), 'student' => $v['student']));
         };

      return $f;
   }


   public function createComponent($name) {      
      if (\strpos($name, 'teamRate')!==false) {
         $teamId = \str_replace('teamRate', '', $name);
         if (!empty($teamId))
            return $this->createTeamRateForm($teamId);
         else
            return parent::createComponent($name);
      } else
         return parent::createComponent($name);
   }

   /**
    * Creates a form for team rating
    * @param int $teamId
    * @return AppForm
    */
   public function createTeamRateForm($teamId) {
      $f = new AppForm($this, 'teamRate'.$teamId);

      $projectsModel = new \ProjectsModel();
      $project = $projectsModel->getProject($this->getParam('id'));

      $teamsModel = new \TeamsModel();
      $members = $teamsModel->getStudentsInTeam($teamId);

      foreach ($members as $member) {
         $f->addText($member->login, 'Hodnocení', 3)
            ->addRule(Form::FILLED, 'Vložte prosím hodnocení')
            ->addRule(Form::FLOAT, 'Hodnocení zadávejte jako desetinné číslo')
            ->addRule(Form::RANGE, 'Hodnocení musí být v rozmezí %f až %f bodů', array(0, $project->max_points));

         $f[$member->login]->setDefaultValue($member->rate);
      }

      $f->addHidden('student')
         ->setDefaultValue($this->getParam('student'));

      $f->addSubmit('save', 'Uložit')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $v = $f->getValues();
            $p = $f->lookup('Nette\Application\Presenter');
            
            $student = $v['student'];            
            unset($v['student']);

            $projectsModel = new \ProjectsModel();

            foreach ($v as $login => $rating)
               $projectsModel->rateStudent( $p->getParam('id'), $login, $rating, $p->getUser()->id );

            $p->flashMessage('Uloženo', 'green');
            $p->redirect('this', array('id' => $p->getParam('id'), 'student' => $student));
         };

      return $f;
   }

}
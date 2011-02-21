<?php

namespace StudentModule;

/**
 * Description of SubjectsPresenter
 *
 * @author David Šabata
 */
class SubjectsPresenter extends \BasePresenter {

   /**
    * Overview of all subjects that the student is registered to
    */
   public function actionDefault() {
      $model = new \SubjectsModel();
      $subjects = $model->getSubjectsByStudent( $this->getUser()->identity->login );

      // split subjects into years
      $this->template->years = array();
      foreach ($subjects as $s) {
         if (!isset($this->template->years[$s->year]))
            $this->template->years[$s->year] = array();

         $this->template->years[$s->year][] = $s;
      }

      $ids = array();
      foreach ($subjects as $s)
         $ids[] = $s->id;

      $projectsModel = new \ProjectsModel();
      $this->template->sums = $projectsModel->getRatingsInSubjects($ids, $this->getUser()->id);
   }



   public function actionDetail($id) {
      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($id);

      // non-existing subject
      if (!$subject)
         $this->redirect(':Public:Error:e404');

      $projectsModel = new \ProjectsModel();
      $projects = $this->template->projects = $projectsModel->getProjectsForSubject($subject->id);

      $ids = array();
      foreach ($projects as $p)
         $ids[] = $p->id;

      $ratings = $this->template->ratings = $projectsModel->getRatingsInProjects($ids, $this->getUser()->id);
   }


   public function actionProjectDetail($id) {
      $projectsModel = new \ProjectsModel();
      $project = $projectsModel->getProject($id);
      $variants = $projectsModel->getVariants($project->id);

      // non-existing project
      if (!$project)
         $this->redirect(':Public:Error:e404');

      $subjectsModel = new \SubjectsModel();
      $subject = $subjectsModel->getSubject($project->subject);

      $subject = $this->template->subject = $subject;
      $project = $this->template->project = $project;
      $variants = $this->template->variants = $variants;
      $this->template->signedCounts = $projectsModel->getSignedCounts($project->id);

      // team management stuff
      $teamMgmt = false;
      foreach ($variants as $v)
         if ($v->teams_allowed) {
            $teamMgmt = true;
            break;
         }
      $this->template->teamMgmt = $teamMgmt;

      $teamsModel = new \TeamsModel();
      $team = $this->template->team = $teamsModel->getTeamForStudent($this->getUser()->id, $project->id);
      if ($team) {

         $this->template->signed_variant = $team->variant;
         $colleagues = $this->template->colleagues = $teamsModel->getStudentsInTeam($team->id);

         // leader's stuff
         if ($team->leader == $this->getUser()->id) {
            $this->template->team_requests = $teamsModel->getTeamRequests($team->id);
         }

      }
      // user is not in team
      else {
         $variantIds = array();
         foreach ($variants as $v)
            $variantIds[] = $v->id;        
         $this->template->signed_variant = $projectsModel->getVariantForStudent($variantIds, $this->getUser()->id);
         if ($this->template->signed_variant)
            $this->template->signed_variant = $this->template->signed_variant->variant;
         else
            $this->template->signed_variant = NULL;
      }

      $ids = array($project->id);
      $ratings = $projectsModel->getRatingsInProjects($ids, $this->getUser()->id);
      $this->template->ratingForCurrentUser = isset($ratings[$project->id]) ? $ratings[$project->id] : NULL;
   }


   /**
    * AJAX call to create a new team leaded by current user
    */
   public function handleCreateTeam() {
      $teamsModel = new \TeamsModel();
      
      $team = $teamsModel->create( $this->getParam('id') );
      $this->template->team = $team;

      $u = $this->getUser()->identity;
      $this->template->colleagues = $teamsModel->getStudentsInTeam($team->id);

      $this->invalidateControl('teamInfo');
      $this->invalidateControl('teamManagement');
      $this->invalidateControl('variants');
   }


   /**
    * AJAX call to leave team. Only non-leader can to this.
    * @param int $teamId
    */
   public function handleLeaveTeam($teamId) {
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);
      $colleagues = $teamsModel->getStudentsInTeam($teamId); // team members including the current user

      $tooLate = false; // the time to sign-up has passed
      if ($team->variant) {
         $projectsModel = new \ProjectsModel();
         $project = $projectsModel->getProjectForVariant($team->variant);
         if ($project->signup_until != NULL && new \DateTime() > $project->signup_until)
            $tooLate = true;         
      }

      if ($team->leader == $this->getUser()->id && count($colleagues) > 1) {
         $this->template->hlaska = '<span class="red">Nemůžete opustit tým pokud jste vedoucí</span>';
      } elseif ($tooLate || $team->rated != NULL) {
         $this->template->hlaska = '<span class="red">Už nelze měnit složení týmu</span>';
      } else {
         $teamsModel->leaveTeam($team->id, $this->getUser()->id);

         if ($team->variant)
            $this->template->signedCounts = $projectsModel->getSignedCounts($project->id);

         $this->template->team = NULL;
         $this->template->colleagues = array();
      }

      $this->invalidateControl('variants');
      $this->invalidateControl('teamInfo');
      $this->invalidateControl('teamManagement');
   }


   /**
    * AJAX call to join the team
    * @param string $leader
    */
   public function handleJoinTeam($leader) {
      $projectsModel = new \ProjectsModel();
      $project = $projectsModel->getProject( $this->getParam('id') );

      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeamForStudent($leader, $project->id);

      if (empty($team) || $team->leader != $leader)
         $this->template->hlaska = '<span class="red">Zadaný student není na tento termín vedoucím žádného týmu</span>';
      else {
         $teamsModel->createRequest($team->id, $this->getUser()->id);
         $this->template->hlaska = '<span class="green">Žádost o přijetí do týmu byla odeslána</span>';
      }

      $this->invalidateControl('teamManagement');
   }

   /**
    * AJAX call to accept a team membership request. Only available to the leader.
    * Once accepted, all other team memberships and request of the user are deleted.
    * @param int $teamId
    * @param string $login
    */
   public function handleAcceptRequest($teamId, $login) {
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);

      // is the user allowed to handle membership request?
      if ($team->id != $teamId || $team->leader != $this->getUser()->id)
         return;

      $tooLate = false; // the time to sign-up has passed
      $teamFull = false;
      if ($team->variant) {
         $projectsModel = new \ProjectsModel();
         $project = $projectsModel->getProjectForVariant($team->variant);
         if ($project->signup_until && new \DateTime() > $project->signup_until)
            $tooLate = true;

         $variant = $projectsModel->getVariant($team->variant);
         if ($variant->max_members == count($teamsModel->getStudentsInTeam($teamId)))
            $teamFull = true;
      }

      if ($teamFull) {
         $this->template->hlaska = '<span class="red">Tým je plný. Pro přijetí nového člena se odhlašte z vybrané varianty.</span>';
      } elseif ($tooLate) {
         $this->template->hlaska = '<span class="red">Už nelze měnit složení týmu</span>';
      } else
         $teamsModel->acceptRequest($teamId, $login);

      $this->template->colleagues = $teamsModel->getStudentsInTeam($teamId);
      $this->template->team_requests = $teamsModel->getTeamRequests($team->id);

      $this->invalidateControl('variants');
      $this->invalidateControl('teamInfo');
      $this->invalidateControl('teamManagement');
   }

   
   /**
    * AJAX call to reject a team membership request. Only available to the leader.
    * @param int $teamId
    * @param string $login
    */
   public function handleRejectRequest($teamId, $login) {            
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);

      // is the user allowed to handle membership request?
      if ($team->id != $teamId || $team->leader != $this->getUser()->id)
         return;

      $teamsModel->rejectRequest($teamId, $login);

      $this->template->team_requests = $teamsModel->getTeamRequests($team->id);
      $this->invalidateControl('teamManagement');
   }


   /**
    * AJAX call to kick specified user from the team. Only available to the leader.
    * @param int $teamId
    * @param string $login
    */
   public function handleKickFromTeam($teamId, $login) {
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);

      if ($team->leader != $this->getUser()->id)
         return;

      $tooLate = false; // the time to sign-up has passed
      if ($team->variant) {
         $projectsModel = new \ProjectsModel();
         $project = $projectsModel->getProjectForVariant($team->variant);
         if ($project->signup_until && new \DateTime() > $project->signup_until)
            $tooLate = true;
      }

      if ($tooLate || $team->rated != NULL) {
         $this->template->hlaska = '<span class="red">Už nelze měnit složení týmu</span>';
      } else {
         $teamsModel->leaveTeam($team->id, $login);
         $this->template->colleagues = $teamsModel->getStudentsInTeam($teamId);
      }

      $this->invalidateControl('variants');
      $this->invalidateControl('teamInfo');
      $this->invalidateControl('teamManagement');
   }


   /**
    * AJAX call to transfer the leadership to another team member. Only available
    * to the current leader.
    * @param int $teamId
    * @param string $login
    */
   public function handleSetLeader($teamId, $login) {
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);

      if ($team->leader != $this->getUser()->id)
         return;

      $teamsModel->setLeader($teamId, $login);

      $this->template->team = $teamsModel->getTeam($teamId);
      $this->invalidateControl('teamInfo');
      $this->invalidateControl('teamManagement');
   }


   /**
    * AJAX call to sign up a team for a variant. Only available to the leader.
    * @param int $teamId
    * @param int $variantId
    */
   public function handleSignUpTeam($teamId, $variantId) {
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);

      if ($team->leader != $this->getUser()->id)
         return;

      // transaction-safe call to sign up the team for the variant
      $teamsModel->signUpTeam($teamId, $variantId);

      $projectsModel = new \ProjectsModel();
      $variant = $projectsModel->getVariant($variantId);
      $this->template->signedCounts = $projectsModel->getSignedCounts($variant->project);
      
      $this->template->team = $teamsModel->getTeam($teamId);
      $this->invalidateControl('variants');
   }

   /**
    * AJAX call to unsign a team from a variant. Only available to the leader.
    * @param int $teamId
    * @param int $variantId
    */
   public function handleUnsignTeam($teamId) {
      $teamsModel = new \TeamsModel();
      $team = $teamsModel->getTeam($teamId);
      $variantId = $team->variant;

      if ($team->leader != $this->getUser()->id)
         return;

      $teamsModel->unsignTeam($teamId);

      $projectsModel = new \ProjectsModel();
      $variant = $projectsModel->getVariant($variantId);
      $this->template->signedCounts = $projectsModel->getSignedCounts($variant->project);      

      $this->template->team = $teamsModel->getTeam($teamId);
      $this->invalidateControl('variants');
   }


   /**
    * AJAX call to sign up student for a variant
    * @param int $variantId
    */
   public function handleSignUpStudent($variantId) {
      $projectsModel = new \ProjectsModel();
      $project = $projectsModel->getProjectForVariant($variantId);
      $variants = $projectsModel->getVariants($project->id);

      if ( ($project->signup_from == NULL || $project->signup_from < new DateTime()) &&
            ($project->signup_until == NULL || $project->signup_until > new DateTime()) ) {

         // transaction-safe call to sign up student for a variant
         $projectsModel->signUpStudent($this->getUser()->id, $variantId);                 
      }

      $variantIds = array();
      foreach ($variants as $v)
         $variantIds[] = $v->id;

      $this->template->signed_variant = $projectsModel->getVariantForStudent($variantIds, $this->getUser()->id)->variant;
      $this->template->signedCounts = $projectsModel->getSignedCounts($project->id);

      $this->invalidateControl('variants');
   }


   /**
    * AJAX call to unsign user from a variant
    * @param int $variantId
    */
   public function handleUnsignStudent($variantId) {
      $projectsModel = new \ProjectsModel();
      $variant = $projectsModel->getVariant($variantId);

      $projectsModel->unsignStudent($this->getUser()->id, $variantId);

      $this->template->signed_variant = NULL;
      $this->template->signedCounts = $projectsModel->getSignedCounts($variant->project);
      $this->invalidateControl('variants');
   }



   /**
    * Detail of the variant
    * @param int $id
    */
   public function actionVariantDetail($id) {
      $projectsModel = new \ProjectsModel();
      $variant = $this->template->variant = $projectsModel->getVariant($id);

      // non-existing variant
      if (!$variant)
         $this->redirect(':Public:Error:e404');

      $project = $this->template->project = $projectsModel->getProject($variant->project);

      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($project->subject);
   }


   /**
    * Upload files to project
    * @param int $id project id
    */
   public function actionUploadFiles($id) {
      $projectsModel = new \ProjectsModel();
      $project = $this->template->project = $projectsModel->getProject($id);

      $subjectsModel = new \SubjectsModel();
      $subject = $this->template->subject = $subjectsModel->getSubject($project->subject);

      $files = \Nette\Finder::find($id . '-' . $this->getUser()->id . '.*')->in(WWW_DIR . \DIRECTORY_SEPARATOR . 'files');      
      foreach ($files as $f) {
         $this->template->uploaded = $f->getFilename();
         break;
      }
      
   }


   /**
    * Creates upload form
    * @return AppForm
    */
   public function createComponentUploadFiles() {
      $f = new \Nette\Application\AppForm($this, 'uploadFiles');

      $f->addFile('file', 'Soubor k odevzdání')
         ->addRule(\Nette\Forms\Form::FILLED, 'Vyberte prosím soubor k odevzdání');

      $f->addSubmit('upload', 'Nahrát')
         ->onClick[] = function(\Nette\Forms\SubmitButton $btn) {
            $f = $btn->getForm();
            $p = $f->lookup('Nette\Application\Presenter');
            $v = $f->getValues();

            try {
               $pinfo = pathinfo($v['file']->name);
               $newloc = WWW_DIR . \DIRECTORY_SEPARATOR . 'files' . \DIRECTORY_SEPARATOR . $p->getParam('id') . '-' . $p->getUser()->id . '.' . $pinfo['extension'];
               @unlink($newloc);
               $v['file']->move($newloc);
               $p->flashMessage('Uloženo', $p::FLASH_GREEN);
            } catch(Exception $e) {
               $p->flashMessage('Soubor se nepodařilo nahrát', $p::FLASH_RED);
            }

            $p->redirect('this');
         };

      return $f;
   }

}
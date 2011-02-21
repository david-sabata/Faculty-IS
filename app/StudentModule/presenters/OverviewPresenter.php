<?php

namespace StudentModule;

/**
 * Description of OverviewPresenter
 *
 * @author David Šabata
 */
class OverviewPresenter extends \BasePresenter {

	
   /**
    * Overview of subjects in actual academic year
    */
   public function renderDefault() {
      $subjectsModel = new \SubjectsModel();
      // filter manually to get subject of the actual year
      $subjects = $subjectsModel->getSubjectsByStudent( $this->getUser()->identity->login, \SubjectsModel::CURRENT_ACADEMIC_YEAR );
                     
      $this->template->subjects = $subjects;

      $ids = array();
      foreach ($subjects as $s)
         $ids[] = $s->id;

      $projectsModel = new \ProjectsModel();
      $this->template->sums = $projectsModel->getRatingsInSubjects($ids, $this->getUser()->id);
   }
        
}
<?php

use \dibi;

/**
 * Description of ProjectsModel
 *
 * @author David Šabata
 */
class ProjectsModel {
   
   /**
    * Returns an array of all project for the subject with eventualy chosen variant
    * ordered by submit_until
    * @param int $subject
    * @return array(DibiRow)
    */
   public function getProjectsForSubject($subject) {
      $q = dibi::query("
         SELECT p.*
         FROM projects p         
         WHERE p.subject = %i", $subject, "
         ORDER BY submit_until ASC, id ASC
      ");
      $q->setType('signup_from', dibi::TIME);
      $q->setType('signup_until', dibi::TIME);
      $q->setType('submit_from', dibi::TIME);
      $q->setType('submit_until', dibi::TIME);

      return $q->fetchAll();
   }
   

   /**
    * Returns an array of all project for the student
    * ordered by submit_until
    * @param string $login
    * @param int $subject
    * @return array(DibiRow)
    */
   public function getProjectsForStudent($login, $subject = NULL) {
      $q = dibi::query("
         SELECT p.*, p.id as id, s.*, s.name subject_name,
            pv.title variant_title, pv.description variant_description,
            pv.teams_allowed variant_teamsAllowed,
            pv.max_teams variant_maxTeams, pv.max_members variant_maxMembers 
         FROM projects p
         JOIN project_variants pv ON pv.project = p.id
         JOIN teams t ON t.variant = pv.id
         JOIN students_in_teams sit ON sit.team = t.id
         JOIN subjects s ON s.id = p.subject
         
         WHERE sit.login = %s", $login, "%if", $subject, "AND s.id=%i", $subject, "%end
         ORDER BY submit_until ASC
      ");
      $q->setType('signup_from', dibi::TIME);
      $q->setType('signup_until', dibi::TIME);
      $q->setType('submit_from', dibi::TIME);
      $q->setType('submit_until', dibi::TIME);

      return $q->fetchAll();
   }

   
   /**
    * Returns project under which the variant belongs
    * @param int $variantId 
    * @return DibiRow|NULL
    */
   public function getProjectForVariant($variantId) {
      $q = dibi::query("
         SELECT p.*
         FROM projects p
         JOIN project_variants v ON v.project = p.id
         WHERE v.id = %i", $variantId
      );
      $q->setType('signup_from', dibi::TIME);
      $q->setType('signup_until', dibi::TIME);
      $q->setType('submit_from', dibi::TIME);
      $q->setType('submit_until', dibi::TIME);

      return $q->fetch();
   }


   /**
    * Returns array of users signed for the variant (teamed and non-teamed students)
    * @param int $projectId
    * @return array
    */
   public function getStudentsForProject($projectId) {
      $q = dibi::query("
         SELECT u.*, pv.id variant, NULL team, NULL leader
         FROM users u
         JOIN students_in_variants siv ON u.login = siv.login
         JOIN project_variants pv ON pv.id = siv.variant
         WHERE pv.project = %i", $projectId
      );

      $nonteam = $q->fetchAssoc('variant[]');

      $t = dibi::query("
         SELECT u.*, t.variant variant, t.id team, t.leader leader
         FROM users u
         JOIN students_in_teams sit ON sit.login = u.login
         JOIN teams t ON t.id = sit.team
         WHERE t.project = %i", $projectId
      );

      $team = $t->fetchAssoc('variant[]');

      return $nonteam + $team;
   }


   /**
    * Returns variants of the project
    * @param int $projectId
    * @return array
    */
   public function getVariants($projectId) {
      $r = dibi::query("
         SELECT *
         FROM project_variants
         WHERE project = %i", $projectId
      );

      return $r->fetchAll();
   }

   /**
    * Gets a single project variant
    * @param int $id
    * @return DibiRow
    */
   public function getVariant($id) {
      $q = dibi::query("
         SELECT *
         FROM project_variants
         WHERE id = %i", $id
      );

      return $q->fetch();
   }


   /**
    * Returns one of given variant ID for which the specified user is signed up for
    * Only works for students which are not in team!!!
    * @param array $variantIds
    * @param string $login
    * @return int|NULL
    */
   public function getVariantForStudent(array $variantIds, $login) {
      $q = dibi::query("
         SELECT variant
         FROM students_in_variants
         WHERE login=%s", $login, "AND variant IN %in", $variantIds
      );
      $q->setType('rated', dibi::TIME);

      $v = $q->fetch();

      return $v ? $v : NULL;
   }

   /**
    * Returns variant with ID in $variantIds for which the specified user is signed up for
    * @param array $variantIds
    * @param string $login
    * @return DibiRow|NULL
    */
   public function getVariantRating(array $variantIds, $login) {
      $q = dibi::query("
         SELECT *
         FROM students_in_variants
         WHERE login=%s", $login, "AND variant IN %in", $variantIds
      );
      $q->setType('rated', dibi::TIME);

      return $q->fetch();
   }


   /**
    * Returns array($projectId => $rating)
    * @param array $ids
    * @param string $login
    * @return array
    */
   public function getRatingsInProjects(array $ids, $login) {
      $t = dibi::fetchPairs("
         SELECT t.project, sit.rate
         FROM students_in_teams sit
         JOIN teams t ON t.id = sit.team
         WHERE t.project IN %in", $ids, "AND sit.login=%s", $login
      );
      $n = dibi::fetchPairs("
         SELECT pv.project, siv.rate
         FROM students_in_variants siv
         JOIN project_variants pv ON pv.id = siv.variant
         WHERE pv.project IN %in", $ids, "AND siv.login=%s", $login
      );
      return $t + $n;
   }


   /**
    * Returns sum of ratings in subjects
    * @param array $ids
    * @param string $login
    * @return array
    */
   public function getRatingsInSubjects(array $ids, $login) {
      $t = dibi::fetchPairs("
         SELECT p.subject, SUM(sit.rate)
         FROM students_in_teams sit
         JOIN teams t ON t.id = sit.team
         JOIN projects p ON p.id = t.project
         WHERE p.subject IN %in", $ids, "AND sit.login=%s", $login, "
         GROUP BY p.subject
      ");
      $n = dibi::fetchPairs("
         SELECT p.subject, SUM(siv.rate)
         FROM students_in_variants siv
         JOIN project_variants pv ON pv.id = siv.variant
         JOIN projects p ON p.id = pv.project
         WHERE p.subject IN %in", $ids, "AND siv.login=%s", $login
      );

      // subject => sum
      $sums = array();
      foreach ($t as $k => $v)
         if (!isset($sums[$k]))
            $sums[$k] = $v;
         else
            $sums[$k] += $v;

      foreach ($n as $k => $v)
         if (!isset($sums[$k]))
            $sums[$k] = $v;
         else
            $sums[$k] += $v;

      return $sums;
   }


   /**
    * Returns project by its id
    * @param int $id
    * @return DibiRow
    */
   public function getProject($id) {
      $q = dibi::query("
         SELECT *
         FROM projects p
         WHERE p.id = %i", $id
      );
      $q->setType('signup_from', dibi::TIME);
      $q->setType('signup_until', dibi::TIME);
      $q->setType('submit_from', dibi::TIME);
      $q->setType('submit_until', dibi::TIME);

      return $q->fetch();
   }


   /**
    * Returns an array indexed by variants ids, containing numbers of teams
    * signed up for the project, or number of students for non-team projects
    * @param int $projectId
    * @return array
    */
   public function getSignedCounts($projectId) {
      $r = dibi::fetchPairs("
         SELECT pv.id, COUNT(*)
         FROM teams t
         JOIN project_variants pv ON t.variant = pv.id
         WHERE pv.project = %i", $projectId, "
         GROUP BY t.variant
      ");

      $s = dibi::fetchPairs("
         SELECT pv.id, COUNT(*)
         FROM students_in_variants siv
         JOIN project_variants pv ON siv.variant = pv.id         
         WHERE pv.project = %i", $projectId, "
         GROUP BY siv.variant
      ");

      return $r + $s;
   }



   /**
    * Creates a new project
    * @param int $subjectId
    * @return int the new project id
    */
   public function add($subjectId) {
      dibi::query("INSERT INTO projects SET subject=%i", $subjectId);
      return dibi::getInsertId();
   }


   /**
    * Updates the data in db for record $v['id']
    * @param array $v data from the form
    * @return bool|string
    */
   public function save($v) {

      $id = $v['id'];
      unset($v['id']);

      // even when 'no variants' is selected, one variant will be created to hold
      // the project limits
      if ($v['variants'] == 1) {

         $variantsAdd = array(); // variants to be added to db
         $variantsUpdate = array(); // variants to be updated
         $variantsDelete = array(); // variants to be deleted

         // parse variants
         foreach ($v as $key => $value) {
            if (preg_match('/^variant[0-9]+$/', $key)) {

               if (!empty($value['title'])) {                  
                  $variant = array(
                     'project' => $id,
                     'title' => $value['title'],
                     'description' => $value['description'],
                     'teams_allowed' => (!$value['noteams']),
                     'max_teams' => empty($value['maxteams']) ? NULL : $value['maxteams'],
                     'max_members' => ($value['maxmembers'] ? $value['maxmembers'] : NULL),
                  );

                  if ($value['dbId'])
                     $variantsUpdate[$value['dbId']] = $variant;
                  else
                     $variantsAdd[] = $variant;
               } elseif (!empty($value['dbId']))
                  $variantsDelete[] = $value['dbId'];
            }
         }

         if (empty($variantsAdd) && empty($variantsUpdate))
            return 'Přidejte alespoň jednu variantu anebo zvolte termín bez variant';

         try {
            if (!empty($variantsDelete))
               dibi::query("DELETE FROM project_variants WHERE id IN %in", $variantsDelete);
         } catch (DibiDriverException $e) {
            return 'Některé varianty se nepodařilo odstranit, protože jsou na ně již přihlášeni studenti';
         }

         if (!empty($variantsAdd))
            dibi::query("INSERT INTO project_variants %ex", $variantsAdd);

         foreach ($variantsUpdate as $vid => $variant) {
            dibi::query("UPDATE project_variants SET", $variant, "WHERE id=%i", $vid);
         }


      } else {
         // delete all previously added variants
         try {
            $oldVariants = $this->getVariants($id);
            $oldIds = array();
            foreach ($oldVariants as $oldVar)
               $oldIds[] = $oldVar->id;
            
            if (!empty($v['no_variant_id']))
               unset($oldIds[array_search($v['no_variant_id'], $oldIds)]);

            if (!empty($oldIds))
               dibi::query("DELETE FROM project_variants WHERE id IN %in", $oldIds);

         } catch (DibiDriverException $e) {
            return 'Nelze změnit na termín bez variant. Na některé varianty jsou již přihlášeni studenti.';
         }

         $variant = array(
            'project' => $id,
            'title' => '',
            'description' => '',
            'teams_allowed' => !((bool)$v['no_variant_teams']),
            'max_teams' => (empty($v['no_variant_maxteams']) ? NULL : $v['no_variant_maxteams']),
            'max_members' => (empty($v['no_variant_maxmembers']) ? NULL : $v['no_variant_maxmembers']),
         );

         if (empty($v['no_variant_id']))
            dibi::query("INSERT INTO project_variants", $variant);
         else
            dibi::query("UPDATE project_variants SET", $variant, " WHERE id = %i", $v['no_variant_id']);
      }

      // unset nonvariant limits
      unset ($v['no_variant_id']);
      unset ($v['no_variant_teams']);
      unset ($v['no_variant_maxteams']);
      unset ($v['no_variant_maxmembers']);

      // unset variants from the data to be saved
      foreach ($v as $key => $value)
         if (preg_match('/^variant[0-9]+$/', $key))
            unset($v[$key]);

      dibi::query("UPDATE projects SET", $v, "WHERE id = %i", $id);

      return TRUE;
   }



   /**
    * Deletes the project
    * @param int $id
    * @return bool
    */
   public function delete($id) {
      try {
         dibi::query("
            DELETE FROM projects_variants
            WHERE projec = %i", $id
         );

         dibi::query("
            DELETE FROM projects
            WHERE id = %i", $id
         );
      } catch (DibiDriverException $e) {
         return FALSE;
      }
      return TRUE;
   }


   /**
    * Transaction-safe student sign up for a variant
    * @param string $login
    * @param int $variantId
    */
   public function signUpStudent($login, $variantId) {
      $variant = $this->getVariant($variantId);

      // if the capacity is limited, use critical section
      if ($variant->max_members != NULL) {

         // --- critical section --------------------------------
         dibi::query("LOCK TABLES students_in_variants WRITE, project_variants READ");

         $alreadySigned = dibi::fetchSingle("
            SELECT COUNT(*)
            FROM students_in_variants
            WHERE variant=%i", $variantId
         );

         if ($alreadySigned < $variant->max_members) {
            // unsign any other variants
            $this->unsignStudentFromProject($login, $variant->project);

            dibi::query("
               INSERT INTO students_in_variants
               SET login=%s", $login, ", variant=%i", $variantId
            );
         }

         dibi::query("UNLOCK TABLES");
         // --- critical section --------------------------------

      }
      else {
         // nothing critical here

         $this->unsignStudentFromProject($login, $variant->project);

         dibi::query("
            INSERT INTO students_in_variants
            SET login=%s", $login, ", variant=%i", $variantId
         );
      }
   }


   /**
    * Unsign student from a variant
    * @param string $login
    * @param int $variantId
    */
   public function unsignStudent($login, $variantId) {
      dibi::query("
         DELETE FROM students_in_variants
         WHERE login=%s", $login, "AND variant=%i", $variantId
      );
   }


   /**
    * Unsign student from all variants of the project
    * @param string $login
    * @param int $projectId
    */
   public function unsignStudentFromProject($login, $projectId) {
      // get all variants
      $variants = $this->getVariants($projectId);
      $ids = array();
      foreach ($variants as $v)
         $ids[] = $v->id;

      dibi::query("
         DELETE FROM students_in_variants
         WHERE login=%s", $login, "AND variant IN %in", $ids
      );
   }


   /**
    * Rate student on a project
    * @param int $projectId
    * @param string $login
    * @param float $rating
    * @param string $ratedBy
    */
   public function rateStudent($projectId, $login, $rating, $ratedBy) {
      $variants = $this->getVariants($projectId);
      dibi::query("
         UPDATE students_in_variants
         SET rated=NOW(), rated_by=%s", $ratedBy, ", rate=%f", $rating, "
         WHERE login=%s", $login, "AND variant IN %in", $variants
      );

      // assume team member
      if (dibi::getAffectedRows() == 0) {
         $teamsModel = new TeamsModel();
         $team = $teamsModel->getTeamForStudent($login, $projectId);

         dibi::query("
            UPDATE teams
            SET rated=NOW(), rated_by=%s", $ratedBy, "
            WHERE id=%i", $team->id
         );
         dibi::query("
            UPDATE students_in_teams
            SET rate=%f", $rating, "
            WHERE login=%s", $login, "AND team=%i", $team->id
         );
      }
   }

}

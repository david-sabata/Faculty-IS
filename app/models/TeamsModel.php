<?php

use \dibi;

/**
 * TeamsModel
 *
 * @author David Šabata
 * @copyright Copyright (c) 2009, 2010 David Šabata
 */
class TeamsModel {


   /**
    * Returns student's team
    * @param string $login
    * @param int $project
    * @return DibiRow
    */
   public function getTeamForStudent($login, $project) {
      $q = dibi::query("
         SELECT *, t.id id, sit.rate rate
         FROM teams t
         JOIN students_in_teams sit ON sit.team = t.id         
         WHERE sit.login = %s", $login, "AND t.project = %i", $project
      );
      $q->setType('rated', dibi::TIME);

      return $q->fetch();
   }

   /**
    * Returns an array of students in the team
    * @param int $teamId
    * @return array
    */
   public function getStudentsInTeam($teamId) {
      return dibi::fetchAll("
         SELECT u.*, sit.rate
         FROM students_in_teams sit
         JOIN users u ON u.login = sit.login
         WHERE sit.team = %i", $teamId
      );
   }

   /**
    * Returns the team
    * @param int $id
    * @return DibiRow
    */
   public function getTeam($id) {
      return dibi::fetch("
         SELECT *
         FROM teams t
         WHERE t.id = %i", $id
      );
   }

   /**
    * Returns an array of teams signed up for a project (any variant)
    * @param int $projectId
    * @return array
    */
   public function getTeamsForProject($projectId) {
      return dibi::fetchAll("
         SELECT *
         FROM teams
         WHERE project = %i", $projectId
      );
   }


   /**
    * Creates and returns a new team
    * @param int $projectId
    * @return DibiRow
    */
   public function create($projectId) {
      $user = \Nette\Environment::getUser();
      dibi::query("INSERT INTO teams SET leader = %s", $user->id, ", project = %i", $projectId);
      $teamId = dibi::getInsertId();

      dibi::query("INSERT INTO students_in_teams SET login = %s", $user->id, ", team = %i", $teamId);
      
      return $this->getTeam($teamId);
   }

   /**
    * Creates a new team membership request
    * @param int $teamId
    * @param string $login
    */
   public function createRequest($teamId, $login) {
      try {
         dibi::query("INSERT INTO team_requests SET team = %i", $teamId, ", login = %s", $login);
      } catch (DibiDriverException $e) {
         // ignore attempts to re-request while one request is already sent
      }
   }

   /**
    * Accept the team membership request
    * @param int $teamId
    * @param string $login
    */
   public function acceptRequest($teamId, $login) {
      // delete all other memberships and membership requests on this project
      $team = $this->getTeam($teamId);
      $projectsModel = new ProjectsModel();
      $variant = $projectsModel->getVariant($team->variant);      
      $teams = $this->getTeamsForProject($team->project);
      
      $teamIds = array();
      foreach ($teams as $t)
         $teamIds[] = $t->id;

      dibi::query("DELETE FROM students_in_teams WHERE login=%s", $login, "AND team IN %in", $teamIds);
      dibi::query("DELETE FROM team_requests WHERE login=%s", $login, "AND team IN %in", $teamIds);

      dibi::query("INSERT INTO students_in_teams SET login=%s", $login, ", team=%i", $teamId);
   }

   /**
    * Reject the team membership request
    * @param int $teamId
    * @param string $login
    */
   public function rejectRequest($teamId, $login) {
      dibi::query("DELETE FROM team_requests WHERE login=%s", $login, "AND team=%i", $teamId);
   }


   /**
    * Returns an array (rows of Users) of team membership requests
    * @param int $teamId
    */
   public function getTeamRequests($teamId) {
      return dibi::fetchAll("
         SELECT u.*
         FROM team_requests tr
         JOIN users u ON u.login = tr.login
         WHERE tr.team = %i", $teamId
      );
   }


   /**
    * Leave the team / kick student out of the team
    * @param int $teamId
    * @param string $login 
    */
   public function leaveTeam($teamId, $login) {
      dibi::query("
         DELETE FROM students_in_teams 
         WHERE login = %s", $login, "AND team = %i", $teamId
      );

      // if this was the last team member, delete the whole team
      if (count($this->getStudentsInTeam($teamId)) == 0)
         dibi::query("DELETE FROM teams WHERE id = %i", $teamId);
   }


   /**
    * Sets the new leader for the team
    * @param int $teamId
    * @param string $login
    */
   public function setLeader($teamId, $login) {
      dibi::query("UPDATE teams SET leader=%s", $login, "WHERE id=%i", $teamId);
   }


   /**
    * Transaction-safe sign up of team for the variant
    * @param int $teamId
    * @param int $variantId
    */
   public function signUpTeam($teamId, $variantId) {
      $projectsModel = new ProjectsModel();
      $variant = $projectsModel->getVariant($variantId);

      // No need to be in critical section - after-sign-up team member changes are allowed.
      // Also the only way to attempt to sign up a team with more members than
      // allowed is to forge the request.
      $members = $this->getStudentsInTeam($teamId);
      if (count($members) > $variant->max_members)
         return;

      // if the number of teams is limited, use critical section
      if ($variant->max_teams != NULL) {

         // --- critical section --------------------------------
         dibi::query("LOCK TABLES teams WRITE");

         $alreadySigned = dibi::fetchSingle("
            SELECT COUNT(*)
            FROM teams
            WHERE variant=%i", $variantId
         );

         if ($alreadySigned < $variant->max_teams)
            dibi::query("
               UPDATE teams
               SET variant=%i", $variantId, "
               WHERE id=%i", $teamId
            );

         dibi::query("UNLOCK TABLES");
         // --- critical section --------------------------------
         
      }
      else {
         // nothing critical here
         dibi::query("
            UPDATE teams
            SET variant=%i", $variantId, "
            WHERE id=%i", $teamId
         );
      }
   }


   /**
    * Unsign the team from any variant
    * @param int $teamId
    */
   public function unsignTeam($teamId) {
      dibi::query("
         UPDATE teams
         SET variant=%i", NULL, "
         WHERE id=%i", $teamId
      );
   }


}

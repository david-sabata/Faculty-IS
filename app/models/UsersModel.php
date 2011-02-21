<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */

use Nette\Object;
use Nette\Security\AuthenticationException;


/**
 * Users authenticator.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class UsersModel extends Object implements Nette\Security\IAuthenticator
{

   /**
    * Performs an authentication
    * @param  array
    * @return IIdentity
    * @throws AuthenticationException
    */
   public function authenticate(array $credentials) {
      $login = $credentials[self::USERNAME];
      $password = sha1($login . $credentials[self::PASSWORD]);

      $row = dibi::fetch('SELECT * FROM users WHERE login=%s', $login);

      if (!$row) {
            throw new AuthenticationException("Uživatelský účet '$login' neexistuje.", self::IDENTITY_NOT_FOUND);
      }

      if ($row->password !== $password) {
            throw new AuthenticationException("Nesprávné heslo.", self::INVALID_CREDENTIAL);
      }

      // load roles
      $res = dibi::query("
         SELECT ur.name
         FROM users_in_roles uir
         JOIN users_roles ur ON uir.role_id = ur.id
         WHERE uir.login=%s", $login
      );     
      $roles = $res->fetchPairs();      

      // build full name
      $row['fullName'] = ($row->name_prefix ? $row->name_prefix . ' ' : '') . $row->name . ' ' . $row->surname
                           . ($row->name_suffix ? ', ' . $row->name_suffix : '');

      unset($row->password);
      return new Nette\Security\Identity($row->login, $roles, $row);
   }



   /**
    * Returns datasource of all users
    * @return DibiDataSource
    */
   public function getUsers() {
      $ds = \dibi::dataSource("
         SELECT u.login, u.name_prefix, u.name, u.surname, u.name_suffix, 
            CONCAT_WS('', CONCAT(u.name_prefix, ' '), u.name, ' ', u.surname, CONCAT(', ', u.name_suffix)) test,
          (
            SELECT GROUP_CONCAT(r.name)
            FROM users_in_roles uir
            LEFT JOIN users_roles r ON r.id = uir.role_id
            WHERE uir.login = u.login
          ) roles
         FROM users AS u
      ");

      return $ds;
   }


   /**
    * Returns simple array of all users in specfied (or higher) role
    * @return array(login=>fullName)
    */
   public function getUsersInRole($roleId) {
      $ds = \dibi::fetchAll("
         SELECT u.login,
            CONCAT_WS('', u.surname, ' ', u.name, CONCAT(', ', u.name_prefix, ' '), u.name_suffix) name,
          (
            SELECT GROUP_CONCAT(r.name)
            FROM users_in_roles uir
            LEFT JOIN users_roles r ON r.id = uir.role_id
            WHERE uir.login = u.login
          ) roles
         FROM users AS u
      ");

      // get ascendent roles via the authorizator
      $auth = \Nette\Environment::getService('Nette\Security\IAuthorizator');

      // authorizator works with role names, not ids
      $rolesModel = new \RolesModel();
      $baseRole = $rolesModel->getRole($roleId)->name;

      // array to be returned
      $users = array();

      foreach ($ds as $row) {
         $boom = explode(',', $row['roles']);
         foreach ($boom as $role)
            if ($auth->roleInheritsFrom($role, $baseRole)) {
               $users[$row->login] = $row->name;
               break;
            }
      }

      return $users;
   }


   /**
    * Returns a datasource of user in the role. Not including ascendent roles!
    * @param int $roleId
    * @return DibiDataSource
    */
   public function getUsersInSingleRole($roleId) {
      $ds = \dibi::dataSource("
         SELECT u.login, u.name_prefix, u.name, u.surname, u.name_suffix,
            CONCAT_WS('', CONCAT(u.name_prefix, ' '), u.name, ' ', u.surname, CONCAT(', ', u.name_suffix)) test
         FROM users AS u
         JOIN users_in_roles uir ON uir.login = u.login
         WHERE uir.role_id = %i", $roleId
      );

      return $ds;
   }

   /**
    * Returns single user by login
    * @param string $login
    * @return DibiRow
    */
   public function getUser($login) {
      return dibi::fetch("
         SELECT u.*
         FROM users AS u
         WHERE u.login = %s", $login
      );
   }


   /**
    * Creates an empty user record (just with login)
    * @param string $login the new login
    * @return int id of the new record
    */
   public function add($login) {
      try {
         \dibi::query("INSERT INTO users SET login = %s", $login);
      } catch (DibiDriverException $e) {
         Nette\Debug::barDump($e->getMessage());
         return FALSE;
      }
      return TRUE;
   }


   /**
    * Saves the user (assumes the data has already been validated)
    * @param string $login
    * @param array $data
    * @return bool
    */
   public function save($login, array $data) {      

      // empty password means do not change
      if (empty($data['password']))
         unset($data['password']);
      else
         $data['password'] = sha1($login . $data['password']);

      // replace empty strings with NULL
      if ($data['name_prefix'] == '')
         $data['name_prefix'] = NULL;
      if ($data['name_suffix'] == '')
         $data['name_suffix'] = NULL;

      if (empty($data))
         return TRUE;

      // create a new record if necessary
      if ( dibi::fetchSingle("SELECT COUNT(*) FROM users WHERE login=%s", $login) == 0 && isset($data['login']) )
         if ($this->add($data['login']) === FALSE)
            return FALSE;
         else {
            $login = $data['login'];
            unset($data['login']);
         }

      try {
         \dibi::query("UPDATE users SET", $data, "WHERE login=%s", $login);
      } catch (DibiDriverException $e) {
         \Nette\Debug::barDump($e->getMessage());
         return FALSE;
      }

      return TRUE;
   }


   /**
    * Deletes the user
    * @param string $login
    * @return bool
    */
   public function delete($login) {
      if ($login == \Nette\Environment::getUser()->identity->login)
         return FALSE;

      try {
         dibi::query("DELETE FROM users WHERE login=%s", $login);
      } catch (DibiDriverException $e) {
         return FALSE;
      }

      return TRUE;
   }



   /**
    * Finds the user in database by login or full name, signed up for a project
    * @param string $student
    * @param int $projectId
    * @return DibiRow|NULL
    */
   public function find($student, $projectId) {
      $nonteam = dibi::fetch("
         SELECT u.*
         FROM users u
         JOIN students_in_variants siv ON siv.login = u.login
         JOIN project_variants pv ON pv.id = siv.variant
         WHERE pv.project=%i", $projectId, "AND (u.login=%s", $student, " OR CONCAT(u.name, ' ', u.surname) LIKE %s", '%'.$student.'%', ")
      ");

      if ($nonteam)
         return $nonteam;

      $team = dibi::fetch("
         SELECT u.*
         FROM users u
         JOIN students_in_teams sit ON sit.login = u.login
         JOIN teams t ON t.id = sit.team
         WHERE t.project=%i", $projectId, "AND (u.login=%s", $student, " OR CONCAT(u.name, ' ', u.surname) LIKE %s", '%'.$student.'%', ")
      ");

      if ($team)
         return $team;

      return NULL;
   }


   /**
    * Returns students registered in specified subjects
    * @param array $subjectIds
    * @return array
    */
   public function getStudentsInSubjects(array $subjectIds) {
      $q = dibi::query("
         SELECT sis.id subject, u.*
         FROM students_in_subjects sis
         JOIN users u ON u.login = sis.login
         WHERE sis.id IN %in", $subjectIds
      );

      return $q->fetchAssoc('subject[]');
   }


   /**
    * Register students for subjects
    * The format of input is array of arrays(login=>login, id=>subjectId)
    * @param array $regs
    * @return int number of new registrations
    */
   public function registerStudents(array $regs) {
      dibi::query("
         INSERT INTO students_in_subjects
         %ex", $regs, "
         ON DUPLICATE KEY UPDATE id=id
      ");
      return dibi::getAffectedRows();
   }


}

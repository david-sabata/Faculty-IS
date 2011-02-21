<?php


/**
 * Description of RolesModel
 *
 * @author David Šabata
 * @copyright Copyright (c) 2009, 2010 David Šabata
 */
class RolesModel {

   // return values of deleting function to distinguish between deleting
   // protected role and deleting which breaks constrains
   const ERR_OK = 1;
   const ERR_CONSTRAIN = 2;
   const ERR_PROTECTED = 3;

   const CACHE_KEY = 'roles';
   private $cache;


   public function __construct() {
      $this->cache = \Nette\Environment::getCache(self::CACHE_KEY);
   }

   
   /**
    * Returns datasource of all roles
    * @return DibiDataSource
    */
   public function getRoles() {
      $ds = \dibi::dataSource("
         SELECT r.*, rr.name AS parent_name
         FROM users_roles AS r
         LEFT JOIN users_roles AS rr ON rr.id = r.parent_id
      ");

      return $ds;
   }


   /**
    * Returns single role by id
    * @param int $id
    * @return DibiRow
    */
   public function getRole($id) {
      return dibi::fetch("
         SELECT r.*, rr.name AS parent_name
         FROM users_roles AS r
         LEFT JOIN users_roles AS rr ON rr.id = r.parent_id
         WHERE r.id = %i", $id
      );
   }


   /**
    * Returns array of available parents for given role
    * @todo prevent possible circular inheritance?
    * @param int $id 
    * @return array(id=>name)
    */
   public function getAvailableParents($id) {
      return dibi::fetchPairs("
         SELECT id, name
         FROM users_roles
         WHERE id != %i", (int)$id
      );      
   }



   /**
    * Creates an empty role
    * @return int id of the new record
    */
   public function add() {
      \dibi::query("INSERT INTO users_roles SET parent_id = NULL");
      return \dibi::insertId();
   }


   /**
    * Saves the role (assumes the data has already been validated)
    * @param int|NULL $id
    * @param array $data
    * @return bool
    */
   public function save($id, array $data) {

      if ($id === NULL)
         $id = $this->add();

      try {
         \dibi::query("UPDATE users_roles SET", $data, "WHERE id=%i", $id);
      } catch (DibiDriverException $e) {
         return FALSE;
      }

      // clean the cache
      $this->cache->offsetUnset('roleNames');
      $tmp = \Nette\Environment::getCache(\AclModel::CACHE_KEY);
      $tmp->offsetUnset('roles');
      $tmp->offsetUnset('rulesid_' . $id);
      $tmp->offsetUnset('rules');

      return TRUE;
   }


   /**
    * Deletes the role
    * @param int $id
    * @return \RolesModel::ERR_PROTECTED|\RolesModel::ERR_CONSTRAIN|\RolesModel::ERR_OK
    */
   public function delete($id) {
      // check for protected roles
      $roles = \Nette\Environment::getConfig('roles');
      if (array_search($id, (array)$roles) !== FALSE)
         return self::ERR_PROTECTED;      

      try {
         dibi::query("DELETE FROM users_roles WHERE id=%i", $id);
      } catch (DibiDriverException $e) {
         return self::ERR_CONSTRAIN;
      }

      $this->cache->offsetUnset('roleNames');
      return self::ERR_OK;
   }


   /**
    * Returns array of roles for given user
    * @param string $login
    * @return array roles id
    */
   public function getRolesForUser($login) {
      $roles = dibi::fetchPairs("
         SELECT r.id
         FROM users_in_roles uir
         JOIN users_roles r ON r.id = uir.role_id
         WHERE uir.login = %s", $login
      );

      $roles = array_flip($roles);
      $roles = array_fill_keys(array_keys($roles), TRUE);

      return $roles;
   }


   /**
    * Sets the user in given roles, deleting all previously assigned roles
    * @param string $login
    * @param array $roles
    */
   public function setUserInRole($login, array $roles) {
      dibi::query("DELETE FROM users_in_roles WHERE login = %s", $login);
      
      $newRoles = array();
      foreach ($roles as $r)
         $newRoles[] = array(
            'login' => $login,
            'role_id' => $r,
         );

      dibi::query("INSERT INTO users_in_roles %ex", $newRoles);
   }



   /**
    * Returns array of names of system defined roles
    * @return array
    */
   public function getSystemRoleNames() {
      if (!isset($this->cache['roleNames']))
         return $this->cache['roleNames'];

      $roles = Environment::getConfig()->roles;
      $roleNames = array(
         'administrator' => $this->getRole($roles->administrator)->name,
         'employee' => $this->getRole($roles->employee)->name,
         'student' => $this->getRole($roles->student)->name,
      );

      $this->cache->save('roleNames', $roleNames);
      return $roleNames;
   }


}
?>

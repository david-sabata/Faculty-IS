<?php

/**
 * ACL model
 * Internally uses cache which is cleaned every time the updateRules is called.
 *
 * @author David Šabata
 */
class AclModel extends \Nette\Object {

   const CACHE_KEY = 'permission';
   private $cache;

   public function __construct() {
      $this->cache = \Nette\Environment::getCache(self::CACHE_KEY);
   }


   /**
    * @return array of DibiRow
    */
   public function getRoles() {
      //if (isset($this->cache['roles']))
      //   return $this->cache['roles'];
      
      $ret = dibi::fetchAll("SELECT r1.name name, r2.name parent_name
        FROM users_roles r1
        LEFT JOIN users_roles r2 ON r1.parent_id = r2.id
        ORDER BY r1.parent_id, r1.id
      ");

      //$this->cache->save('roles', $ret);
      return $ret;
   }


   /**
    * @return array of DibiRow
    */
   public function getResources() {
      //if (isset($this->cache['resources']))
      //   return $this->cache['resources'];

      $ret = dibi::fetchAll("SELECT name FROM users_resources");

      //$this->cache->save('resources', $ret);
      return $ret;
   }


   /**
    * @return array of DibiRow
    */
   public function getPrivileges() {
      //if (isset($this->cache['privileges']))
      //   return $this->cache['privileges'];
      
      $ret = dibi::fetchAll("SELECT name FROM users_privileges");
      //$this->cache->save('privileges', $ret);

      return $ret;
   }


   /**
    * Returns rules for specified role or just all rules
    * @param int|NULL $roleId
    * @return array of DibiRow
    */
   public function getRules($roleId = NULL) {
      $key = 'rules' . ($roleId === NULL ? '' : '_' . $roleId);

      //if (isset($this->cache[$key]))
      //   return $this->cache[$key];

      $q = dibi::query("
      SELECT
         a.allowed allowed,
         r.name role,
         res.name resource,
         p.name privilege

      FROM users_acl a
      JOIN users_roles r ON a.role_id = r.id
      LEFT JOIN users_resources res ON a.resource_id = res.id
      LEFT JOIN users_privileges p ON a.privilege_id = p.id
      %if", $roleId, "WHERE a.role_id = %i", $roleId, "%end
      ");
      //$q->setType('access', Dibi::BOOL);
      $ret = $q->fetchAll();

      //$this->cache->save($key, $ret);
      return $ret;
   }

   /**
    * Returns rules with integer keys to roles/resources/privileges
    * @param int|NULL $roleId
    * @return array of DibiRow
    */
   public function getRulesNumeric($roleId = NULL) {
      $key = 'rulesid' . ($roleId === NULL ? '' : '_' . $roleId);

      //if (isset($this->cache[$key]))
      //   return $this->cache[$key];

      $q = dibi::query("
         SELECT
            role_id role,
            privilege_id privilege,
            resource_id resource,
            allowed
         FROM users_acl
         %if", $roleId, "WHERE role_id = %i", $roleId, "%end
      ");
      //$q->setType('access', Dibi::BOOL);
      $ret = $q->fetchAll();

      //$this->cache->save($key, $ret);
      return $ret;
   }
   
   
   /**
    * Updates the acl rules for the given role, deleting all previous rules for the role
    * @param int $roleId
    * @param array $rules array of arrays(resource, privilege)
    */
   public function updateRules($roleId, array $rules) {
      dibi::query("DELETE FROM users_acl WHERE role_id = %i", $roleId);

      // rules formated for dibi query
      $newRules = array();

      foreach ($rules as $k => $v)
         $newRules[] = array(
            'role_id' => $roleId,
            'resource_id' => $v['resource'],
            'privilege_id' => $v['privilege'],
            'allowed' => 'Y',
         );

      if (count($newRules) > 0)
         dibi::query("INSERT INTO users_acl %ex", $newRules);      

      // clean the cache
      $this->cache->offsetUnset('rulesid_' . $roleId);
      $this->cache->offsetUnset('rules');
   }


   /**
    * Returns the resource name
    * @param int $id
    */
   public function getResourceName($id) {
      return dibi::fetchSingle("
         SELECT name
         FROM users_resources
         WHERE id = %i", $id
      );
   }

   /**
    * Returns the privilege name
    * @param int $id
    */
   public function getPrivilegeName($id) {
      return dibi::fetchSingle("
         SELECT name
         FROM users_privileges
         WHERE id = %i", $id
      );
   }


   /**
    * Is user allowed to edit projects in the subject
    * @param \Nette\Web\User $user
    * @param int $subjectId
    * @return bool
    */
   public function isAllowedToEditProject($user, $subjectId) {
      $cache = $this->cache;
      $resId = Nette\Environment::getConfig('acl')->resource->project;
      $privId = Nette\Environment::getConfig('acl')->privilege->create;
      if (!isset($cache['resName-'.$resId])) {
         $cache->save('resName-'.$resId, $this->getResourceName($resId));
      }
      if (!isset($cache['privName-'.$privId])) {
         $cache->save('privName-'.$privId, $this->getPrivilegeName($privId));
      }
      $resName = $cache['resName-'.$resId];
      $privName = $cache['privName-'.$privId];

      // check if the actual user is allowed to edit this subject's projects
      if (!\SubjectsModel::isToughtBy($subjectId, $user->getId()) || !$user->isAllowed($resName, $privName))
         return FALSE;      
      else
         return TRUE;
   }


   /**
    * Is user allowed to rate projects in the subject
    * @param \Nette\Web\User $user
    * @param int $subjectId
    * @return bool
    */
   public function isAllowedToRateProject($user, $subjectId) {
      $cache = $this->cache;
      $resId = Nette\Environment::getConfig('acl')->resource->project;
      $privId = Nette\Environment::getConfig('acl')->privilege->mark;
      if (!isset($cache['resName-'.$resId])) {
         $cache->save('resName-'.$resId, $this->getResourceName($resId));
      }
      if (!isset($cache['privName-'.$privId])) {
         $cache->save('privName-'.$privId, $this->getPrivilegeName($privId));
      }
      $resName = $cache['resName-'.$resId];
      $privName = $cache['privName-'.$privId];      

      // check if the actual user is allowed to edit this subject's projects
      if (!\SubjectsModel::isToughtBy($subjectId, $user->getId()) || !$user->isAllowed($resName, $privName))
         return FALSE;
      else
         return TRUE;
   }

}


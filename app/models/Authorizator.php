<?php

/**
 * Description of Authorizator
 *
 * @author David Šabata
 */
class Authorizator extends \Nette\Security\Permission {

   public function  __construct() {
      $model = new \AclModel();
     
      foreach($model->getRoles() as $role)
         $this->addRole($role->name, $role->parent_name);
      
      foreach($model->getResources() as $resource)
         $this->addResource($resource->name);
      
      foreach($model->getRules() as $rule)
         $this->{$rule->allowed ? 'allow' : 'deny'}($rule->role, $rule->resource, $rule->privilege);
   }

}

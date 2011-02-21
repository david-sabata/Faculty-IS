<?php

namespace AdminModule;

use Nette\Application\AppForm,
   Nette\Forms\Form;

/**
 * Description of RolesPresenter
 *
 * @author David Šabata
 */
class RolesPresenter extends \BasePresenter {

   private $aclActions = array();


   public function __construct(Nette\IComponentContainer $parent = NULL, $name = NULL) {
      parent::__construct($parent, $name);

      $aclPreconf = \Nette\Environment::getConfig('acl');

      // set up predefined acl actions
      $this->aclActions = array(
         'Vytváří a upravuje zadání projektů' => array(
            'resource' => $aclPreconf->resource->project,
            'privilege' => $aclPreconf->privilege->create,
         ),
         'Hodnotí projekty' => array(
            'resource' => $aclPreconf->resource->project,
            'privilege' => $aclPreconf->privilege->mark,
         ),
         'Registruje se na projekty' => array(
            'resource' => $aclPreconf->resource->project,
            'privilege' => $aclPreconf->privilege->signUp,
         ),
      );
   }

   /**
    * Datagrid factory ( called by {widget grid} )
    * @return \BaseGrid
    */
   public function createComponentGrid() {

      $model = new \RolesModel();

      $grid = new \BaseGrid;
      $grid->bindDataTable( $model->getRoles() ); // binds DibiDataSource
      $grid->keyName = 'id'; // for actions or operations
      
      $grid->addColumn('name', 'Název role');
      $grid->addColumn('parent_name', 'Dědí od role');

      $grid->addActionColumn('Akce');
      $icon = \Nette\Web\Html::el('span');
      $grid->addAction('Přidat roli', 'add', clone $icon->class('icon icon-add'), FALSE, \DataGridAction::WITHOUT_KEY);
      $grid->addAction('Upravit', 'edit', clone $icon->class('icon icon-edit'));
      $grid->addAction('Odstranit', 'delete', clone $icon->class('icon icon-del'));

      // hide footer
      $renderer = $grid->getRenderer();
      $renderer->footerFormat = '';

      return $grid;
   }

   
   /**
    * Creates a new role and redirects to edit form
    */
   public function actionAdd() {
      $this->setView('edit');
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
      $model = new \RolesModel();
      $id = $this->getParam('id');

      $f->addText('name', 'Název role', NULL, 64)
         ->addRule(Form::FILLED, 'Vyplňte prosím název role');

      // get roles available for parenthood
      $parents = $model->getAvailableParents($id);
      $parents = array('' => '---') + $parents;
      $f->addSelect('parent_id', 'Dědí od', $parents);

      // get predefined resource-privilege sets
      foreach ($this->aclActions as $title => $a) {
         $f->addCheckbox('acl_' . $a['resource'] . '_' . $a['privilege'], $title);
      }

      $f->addSubmit('save', 'Uložit')
         ->onClick[] = array($this, 'editFormSubmitted');

      $f->addSubmit('cancel', 'Zpět')
         ->setValidationScope(NULL)
         ->onClick[] = array($this, 'editFormCancelled');

      if ($id != NULL) {
         // get and format rules
         $acl = new \AclModel();
         $rules = $acl->getRulesNumeric($id);
         $checked = array();
         foreach ($rules as $rule)
            if ($rule->allowed)
               $checked['acl_' . $rule->resource . '_' . $rule->privilege] = TRUE;

         $f->setDefaults( (array)$model->getRole($id) + $checked );
      }

      return $f;
   }


   /**
    * Form submit handler. Gets executed only when the form was submitted with
    * valid data. Saves the data and redirects to the overview.
    * @param \Nette\Forms\SubmitButton $btn
    */
   public function editFormSubmitted(\Nette\Forms\SubmitButton $btn) {
      $model = new \RolesModel();
      $f = $btn->getForm();
      $id = $this->getParam('id');
      $values = $f->getValues();

      // prepare acl rules to be set
      $rules = array();
      foreach ($values as $k => $v)
         if (preg_match('/^acl_([0-9]+)_([0-9]+)$/', $k, $arr)) {
            if ($v)
               $rules[] = array(
                  'resource' => $arr[1],
                  'privilege' => $arr[2],
               );

            unset($values[$k]);
         }

      // change empty parent id to NULL
      if ($values['parent_id'] == '')
         $values['parent_id'] = NULL;

      if ( $model->save($id, $values) ) {
         // save the new rules
         $aclModel = new \AclModel();
         $aclModel->updateRules($id, $rules);

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
    * Attempts to delete the role and redirects back to overview
    * @param int $id
    */
   public function actionDelete($id) {
      $model = new \RolesModel();
      $reason = $model->delete($id);      

      switch ($reason) {
         case \RolesModel::ERR_OK:
            $this->flashMessage('Odstraněno', self::FLASH_GREEN);
            break;
         case \RolesModel::ERR_CONSTRAIN:
            $this->flashMessage('Záznam nelze odstranit. Zřejmě tuto roli využívá některý uživatel nebo od ní dědí jiná role.', self::FLASH_RED);
            break;
         case \RolesModel::ERR_PROTECTED:
            $this->flashMessage('Záznam nelze odstranit. Tato role je systémová a nelze ji smazat.', self::FLASH_RED);
            break;
      }

      $this->redirect('default');
   }

}
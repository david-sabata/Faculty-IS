<?php
/**
 * Representation of data grid column checkbox filter.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */

use Nette\Forms\Checkbox,
	Nette\Forms\FormControl;

class CheckboxFilter
extends DataGridColumnFilter
{
	/**
	 * Returns filter's form element.
	 * @return FormControl
	 */
	public function getFormControl()
	{
		if ($this->element instanceof FormControl)
			return $this->element;
		$element=new Checkbox($this->getName());
		
		return $this->element=$element;
	}
}
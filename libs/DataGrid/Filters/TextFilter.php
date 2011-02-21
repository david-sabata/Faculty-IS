<?php
/**
 * Representation of data grid column textual filter.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */

use Nette\Forms\TextInput,
	Nette\Forms\FormControl;

class TextFilter extends DataGridColumnFilter
{
	/**
	 * Returns filter's form element.
	 * @return FormControl
	 */
	public function getFormControl()
	{
		if ($this->element instanceof FormControl)
			return $this->element;
		
		$this->element=new TextInput($this->getName(), 5);
		return $this->element;
	}
}
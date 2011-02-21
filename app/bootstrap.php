<?php

/**
 * My Application bootstrap file.
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */


use Nette\Debug;
use Nette\Environment;
use Nette\Application\Route;
use Nette\Application\SimpleRouter;


// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/Nette/loader.php';



// Step 2: Configure environment
// 2a) enable Nette\Debug for better exception and error visualisation
Debug::$strictMode = TRUE;
Debug::$logDirectory = WWW_DIR . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'log';
Debug::enable( Debug::DEVELOPMENT );

Debug::$showBar = FALSE;


// 2b) load configuration from config.ini file
Environment::loadConfig();

// 2c) set up user login session expiration : 15minutes, restores session even if browser was closedd
Environment::getUser()->setExpiration('+ 15 minutes', FALSE, TRUE);

// Step 3: Configure application
// 3a) get and setup a front controller
$application = Environment::getApplication();
//$application->errorPresenter = 'Error';
$application->catchExceptions = FALSE;

// 3b) connect to a database
dibi::connect(Environment::getConfig('database'));

// Step 4: Setup application router
$router = $application->getRouter();

$router[] = new Route('index.php', array(
	'presenter' => 'Homepage',
	'action' => 'default',
), Route::ONE_WAY);

$router[] = new Route('init', array(
      'module' => 'Public',
      'presenter' => 'Default',
	'action' => 'init',
	'id' => NULL,
));

$router[] = new Route('<module>/<presenter>/<action>/<id>', array(
      'module' => 'Public',
      'presenter' => 'Default',
	'action' => 'default',
	'id' => NULL,
));



// extend the Form class with JS DateTimePicker
function Form_addDateTimePicker(\Nette\Forms\Form $_this, $name, $label, $cols = NULL, $maxLength = NULL) {
  return $_this[$name] = new DateTimePicker($label, $cols, $maxLength);
}

\Nette\Forms\Form::extensionMethod('addDateTimePicker', 'Form_addDateTimePicker');


// Step 5: Run the application!
$application->run();

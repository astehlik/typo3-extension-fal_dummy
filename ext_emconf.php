<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'FAL dummy driver',
	'description' => 'Provides a dummy image driver the TYPO3 File Abstraction Layer (FAL) that can be used in development.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.1.0',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Alexander Stehlik',
	'author_email' => 'astehlik.deleteme@intera.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '6.2.2-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

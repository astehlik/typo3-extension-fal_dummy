<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_dummy'] = array(
	'class' => 'Tx\\FalDummy\\DummyDriver',
	'shortName' => 'Dummy',
	'flexFormDS' => 'FILE:EXT:core/Configuration/Resource/Driver/LocalDriverFlexForm.xml',
	'label' => 'Dummy'
);
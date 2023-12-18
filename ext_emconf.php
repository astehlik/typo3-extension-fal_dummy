<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL dummy image driver',
    'description' => 'Provides a dummy image driver the TYPO3 File Abstraction Layer (FAL) that can be used in development.',
    'category' => 'misc',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Alexander Stehlik',
    'author_email' => 'astehlik.deleteme@intera.de',
    'author_company' => '',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => ['typo3' => '8.7.55-9.5.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];

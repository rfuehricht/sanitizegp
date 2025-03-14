<?php

$EM_CONF['sanitizegp'] = [
    'title' => 'Sanitize GET/POST',
    'description' => 'Globally configure sanitizing actions for GET/POST parameters.',
    'category' => 'frontend',
    'version' => '1.0.0',
    'state' => 'stable',
    'author' => 'Reinhard Führicht',
    'author_email' => 'r.fuehricht@gmail.com',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.99.99'
        ],
        'conflicts' => [
        ],
    ],
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1
];

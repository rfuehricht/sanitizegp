<?php

use Rfuehricht\Sanitizegp\Middleware\SanitizeGetPost;

return [
    'frontend' => [
        'rfuehricht/sanitizegp/sanitize-get-post' => [
            'target' => SanitizeGetPost::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/maintenance-mode'
            ]
        ],
    ]
];

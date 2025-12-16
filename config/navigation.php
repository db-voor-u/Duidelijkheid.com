<?php

return [
    'public' => [
        'links' => [
            ['label' => 'Welkom', 'route' => 'home'],
            ['label' => 'Blog', 'route' => 'blog.index'],
        ],
        'groups' => [
            [
                'label' => 'Meer',
                'items' => [
                    ['label' => 'Over ons', 'url' => '/over'],
                    ['label' => 'Contact',  'url' => '/contact'],
                ],
            ],
        ],
        'cta' => ['label' => 'Contact', 'url' => '/contact'],
    ],
];

<?php

$apiEndpoints = [
    'store' => [
        'create' => [
            'route' => '/api/stores/new',
            'method' => 'POST',
        ],
        'retrieve' => [
            'route' => '/api/stores/{id}',
            'method' => 'GET',
            'params' => ['name'],
        ],
        'edit' =>[
            'route' => '/api/stores/{id}/edit',
            'method' => 'PATCH',
        ],
        'delete' =>[
            'route' => '/api/stores/{id}/delete',
            'method' => 'DEL',
        ],
    ],
];
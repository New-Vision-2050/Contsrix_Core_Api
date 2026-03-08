<?php

return [
    'permissions' => [
        // Client Request Management
        'CLIENT_REQUEST_LIST' => 'client-relations.client-requests*client-requests.list',
        'CLIENT_REQUEST_VIEW' => 'client-relations.client-requests*client-requests.view',
        'CLIENT_REQUEST_CREATE' => 'client-relations.client-requests*client-requests.create',
        'CLIENT_REQUEST_UPDATE' => 'client-relations.client-requests*client-requests.update',
        'CLIENT_REQUEST_DELETE' => 'client-relations.client-requests*client-requests.delete',
        'CLIENT_REQUEST_EXPORT' => 'client-relations.client-requests*client-requests.export',

        'PRICE_OFFER_LIST' => 'client-relations.price-offer*price-offer.list',

    ],
];

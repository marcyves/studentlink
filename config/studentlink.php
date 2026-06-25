<?php

return [

    /*
    | Adresse qui reçoit les demandes d'accès professeur (landing).
    */
    'admin_email' => env('STUDENTLINK_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

];

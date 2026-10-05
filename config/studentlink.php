<?php

return [

    /*
    | Adresse qui reçoit les demandes d'accès professeur (landing).
    */
    'admin_email' => env('STUDENTLINK_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

    /*
    | Free IP lookup (ipwho.is) for the admin connection journal.
    | Country and city stay empty when the address cannot be resolved.
    */
    'geolocate_logins' => env('STUDENTLINK_GEOLOCATE', true),

];

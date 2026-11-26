<?php
// Kopieer dit bestand naar config.php en vul je eigen gegevens in.
// config.php staat niet in Git en is niet via de browser op te vragen.

return [
    // Databasegegevens (aanmaken in DirectAdmin → MySQL Management)
    'db_host' => 'localhost',
    'db_name' => 'pausefish_qr',
    'db_user' => 'pausefish_qr',
    'db_pass' => 'VUL-HIER-JE-WACHTWOORD-IN',

    // Adres waarop deze map bereikbaar is, met / aan het eind.
    // Dit staat in elke QR-code: NIET meer wijzigen nadat je QR-codes hebt geprint.
    'base_url' => 'https://qr.pausefish.com/',

    // Waarheen als iemand een onbekende of verwijderde code scant (leeg = foutpagina).
    'fallback_url' => '',
];

# QR Dashboard: PHP + MySQL-versie (Vimexx / DirectAdmin)

Dynamische QR-codes op je eigen subdomein, bijvoorbeeld `https://qr.pausefish.com/menu`.
Met login, beheer van QR-codes, direct live wijzigingen en een scanteller.

## Wat zit erin

| Pad | Wat |
|---|---|
| `https://qr.pausefish.com/<code>` | Stuurt door naar de ingestelde bestemming (en telt de scan) |
| `/admin/` | Overzicht van alle QR-codes |
| `/admin/nieuw.php` | Nieuwe QR-code aanmaken |
| `/admin/bewerk.php?id=…` | QR-code bewerken: bestemming/naam wijzigen, downloaden (PNG/SVG), statistieken, verwijderen |
| `/admin/account.php` | Wachtwoord wijzigen, extra gebruikers toevoegen |
| `setup.php` | Eenmalige installatie (tabellen + eerste account); daarna verwijderen |

## Installatie

### 1. Subdomein aanmaken
DirectAdmin → **Subdomain Management** → maak `qr` aan voor `pausefish.com`.
Zet daarna onder **SSL Certificates** een (gratis Let's Encrypt-)certificaat aan dat `qr.pausefish.com` dekt.

### 2. Database aanmaken
DirectAdmin → **MySQL Management** → **Create new Database**.
Noteer de databasenaam, gebruikersnaam en het wachtwoord (DirectAdmin zet je accountnaam ervoor, bijv. `pausefish_qr`).

De tabellen hoef je niet zelf aan te maken: dat doet `setup.php`. Wil je het toch zelf doen, importeer dan
`schema.sql` via **phpMyAdmin → Importeren**.

### 3. Bestanden uploaden
Upload de **inhoud** van deze map `php/` (dus niet de map zelf) naar de map van het subdomein, meestal
`domains/pausefish.com/public_html/qr/` (DirectAdmin laat het pad zien bij het subdomein).
Zorg dat ook het verborgen bestand `.htaccess` mee wordt geüpload.

### 4. Configuratie
Kopieer op de server `config.sample.php` naar `config.php` en vul in:
- `db_name`, `db_user`, `db_pass`: uit stap 2
- `base_url`: `https://qr.pausefish.com/`

> ⚠️ `base_url` staat in elke QR-code. Wijzig deze niet meer nadat je QR-codes hebt geprint.

### 5. Installeren
Open `https://qr.pausefish.com/setup.php`, maak je account aan en **verwijder daarna `setup.php`** van de server.

Klaar: log in op `https://qr.pausefish.com/admin/`.

## Goed om te weten

- **Wijzigingen zijn direct live.** De doorverwijzing is een tijdelijke (302) redirect, dus telefoons onthouden de oude bestemming niet.
- **De code van een QR-code kan niet veranderen**, de bestemming wel. Verwijder geen QR-codes die nog in omloop zijn.
- **Beveiliging:** wachtwoorden worden gehasht opgeslagen (bcrypt), formulieren zijn beschermd tegen CSRF,
  na 10 mislukte inlogpogingen in 15 minuten wordt het IP-adres tijdelijk geblokkeerd, en `config.php`,
  `inc/` en `schema.sql` zijn niet via de browser op te vragen.
- **Scanteller:** telt alleen het aantal scans en het tijdstip van de laatste scan; er worden geen IP-adressen of andere persoonsgegevens van bezoekers opgeslagen.
- **Geen SSL?** Haal dan tijdelijk de twee HTTPS-regels bovenaan `.htaccess` weg.
- **Onbekende code gescand?** Er verschijnt een nette foutpagina, of je stelt `fallback_url` in `config.php` in om door te sturen naar bijvoorbeeld je homepage.

## Vereisten
PHP 7.4 of hoger (8.x aanbevolen) met PDO MySQL, MySQL 5.7+ of MariaDB 10.3+, Apache/LiteSpeed met `.htaccess` (mod_rewrite).
Dit is standaard bij Vimexx-webhosting.

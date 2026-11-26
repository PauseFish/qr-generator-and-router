# QR Dashboard

Dynamische QR-codes op je eigen (sub)domein, bijvoorbeeld `https://qr.pausefish.com/menu`.
De QR-code blijft altijd hetzelfde; de bestemming wijzig je in het dashboard en is direct live.

Gebouwd met gewone PHP + MySQL, zonder framework of buildstap: uploaden is genoeg.

## Wat zit erin

| Pad | Wat |
|---|---|
| `/<code>` | Stuurt door naar de ingestelde bestemming (en telt de scan) |
| `/admin/` | Overzicht van alle QR-codes |
| `/admin/nieuw.php` | Nieuwe QR-code aanmaken |
| `/admin/bewerk.php?id=…` | QR-code bewerken: bestemming/naam wijzigen, downloaden (PNG/SVG), statistieken, verwijderen |
| `/admin/account.php` | Wachtwoord wijzigen, extra gebruikers toevoegen |
| `setup.php` | Eenmalige installatie (tabellen + eerste account); daarna verwijderen |

```
.htaccess            routering, HTTPS, afscherming
index.php            de doorverwijzing (/<code>)
setup.php            eenmalige installatie
config.sample.php    voorbeeldconfiguratie → kopiëren naar config.php
schema.sql           databasetabellen
admin/               de beheerpagina's
assets/              opmaak (style.css) en QR-tekenen/downloaden (qr.js)
inc/                 gedeelde PHP-code (database, login, layout)
vendor/qrcode.js     QR-bibliotheek (qrcode-generator, MIT)
dev/router.php       alleen voor lokaal ontwikkelen, niet uploaden
```

## Lokaal ontwikkelen

Je hebt PHP en MySQL/MariaDB nodig op je computer. Op Windows is **[Laragon](https://laragon.org)** het makkelijkst
(Apache, PHP en MySQL in één, en `.htaccess` werkt gewoon).

### Met Laragon
1. Zet (of clone) deze projectmap in `C:\laragon\www\qr`.
2. Start Laragon (**Start All**). De site is bereikbaar op `http://qr.test`.
3. Maak een database: Laragon → **Database** (HeidiSQL) → nieuwe database `qr`.
4. Kopieer `config.sample.php` naar `config.php`:
   ```php
   'db_host' => '127.0.0.1',
   'db_name' => 'qr',
   'db_user' => 'root',
   'db_pass' => '',           // Laragon heeft standaard geen wachtwoord
   'base_url' => 'http://qr.test/',
   ```
5. Open `http://qr.test/setup.php` en maak een (test)account aan.

> Laat je `db_pass` leeg, dan vul je bij de setup ook een leeg databasewachtwoord in.

### Of met de ingebouwde PHP-server
Met PHP en MySQL al geïnstalleerd, in de projectmap:
```bash
php -S localhost:8000 dev/router.php
```
Zet `base_url` in `config.php` dan op `http://localhost:8000/`.

`config.php` staat in `.gitignore`: je lokale en je live configuratie blijven dus gescheiden.

## Uploaden naar de server (WinSCP)

1. Druk in WinSCP op **Ctrl+Alt+H** om verborgen bestanden (zoals `.htaccess`) te tonen.
2. Upload naar `/domains/qr.pausefish.com/public_html/`:
   `.htaccess`, `index.php`, `setup.php`*, `config.sample.php`, `schema.sql`, en de mappen `admin/`, `assets/`, `inc/`, `vendor/`.
3. **Niet** uploaden: `.git/`, `dev/`, `README.md`, `.gitignore`, en je lokale `config.php`
   (op de server staat een eigen `config.php` met de live gegevens).

\* `setup.php` alleen bij de eerste installatie; verwijder hem daarna van de server.

Bij latere wijzigingen upload je alleen de bestanden die je hebt aangepast.

## Eerste installatie op de server

1. **Domein:** DirectAdmin → **Domain Setup** → `qr.pausefish.com` toevoegen.
2. **DNS:** in de zone van `pausefish.com` records `qr` en `www.qr` toevoegen (A → `185.104.29.116`, AAAA → `2a06:2ec0:1::140`).
3. **SSL:** Let's Encrypt-certificaat voor `qr.pausefish.com` aanvragen en **Force SSL** aanzetten.
4. **Database:** DirectAdmin → **MySQL Management** → **Create new Database**. Noteer naam, gebruiker en wachtwoord
   (DirectAdmin zet je accountnaam ervoor). De tabellen maakt `setup.php` zelf aan.
5. **Bestanden:** uploaden zoals hierboven; verwijder de standaard `index.html` van DirectAdmin.
6. **Configuratie:** op de server `config.sample.php` kopiëren naar `config.php` en invullen
   (`base_url` = `https://qr.pausefish.com/`).
7. **Installeren:** open `https://qr.pausefish.com/setup.php`, vul ter controle het databasewachtwoord uit `config.php` in,
   maak je account aan en **verwijder daarna `setup.php`**.
8. **Controle:** `https://qr.pausefish.com/config.php` moet "Forbidden" geven.

> ⚠️ `base_url` staat in elke QR-code. Wijzig deze niet meer nadat je QR-codes hebt geprint.

## Goed om te weten

- **Wijzigingen zijn direct live.** De doorverwijzing is een tijdelijke (302) redirect, dus telefoons onthouden de oude bestemming niet.
- **De code van een QR-code kan niet veranderen**, de bestemming wel. Verwijder geen QR-codes die nog in omloop zijn.
- **Beveiliging:** wachtwoorden met bcrypt, CSRF-bescherming op alle formulieren, blokkade na 10 mislukte inlogpogingen
  in 15 minuten, prepared statements, en `config.php`, `inc/`, `dev/` en `.git` zijn niet via de browser op te vragen.
- **Scanteller:** telt alleen het aantal scans en het tijdstip van de laatste scan; er worden geen IP-adressen of andere persoonsgegevens van bezoekers opgeslagen.
- **Onbekende code gescand?** Er verschijnt een nette foutpagina, of je stelt `fallback_url` in `config.php` in.

## Vereisten
PHP 7.4 of hoger (8.x aanbevolen) met PDO MySQL, MySQL 5.7+ of MariaDB 10.3+, Apache/LiteSpeed met `.htaccess` (mod_rewrite).
Dit is standaard bij Vimexx-webhosting.

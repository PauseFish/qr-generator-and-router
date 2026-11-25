# QR generator & router

QR-codes met een **vaste URL** waarvan je de **bestemming altijd kunt wijzigen**.
Alles draait statisch op GitHub Pages — geen server of database nodig.

## Hoe het werkt

```
QR-code  →  https://pausefish.github.io/qr-generator-and-router/r/?menu
                         │
                         ▼  leest links.json
             "menu": "https://jouw-bestemming.nl"  →  doorsturen
```

- `index.html` — het dashboard: QR-codes aanmaken, bestemming wijzigen, verwijderen, downloaden als PNG/SVG.
- `r/index.html` — de router: zoekt de code op in `links.json` en stuurt door.
- `links.json` — de lijst met codes en bestemmingen. **Dit is het enige bestand dat je hoeft aan te passen.**

## Instellen (eenmalig)

1. Merge deze branch naar `main`.
2. Ga in GitHub naar **Settings → Pages**, kies **Deploy from a branch**, branch `main`, map `/ (root)`.
3. Na een minuut staat de site op `https://pausefish.github.io/qr-generator-and-router/`.

## Dashboard koppelen aan GitHub (eenmalig)

Het dashboard slaat wijzigingen op door `links.json` in deze repository te committen.
Daarvoor heeft het een GitHub-token nodig:

1. Ga naar <https://github.com/settings/personal-access-tokens/new>.
2. *Repository access* → **Only select repositories** → deze repository.
3. *Permissions* → **Contents: Read and write**.
4. Plak het token in het dashboard onder **Instellingen & GitHub-koppeling** en klik **Opslaan & verbinden**.

Het token wordt alleen in je eigen browser bewaard (localStorage). Zonder token kun je
het dashboard bekijken en QR-codes downloaden, maar niets wijzigen.

## Gebruik

- **Nieuwe QR-code:** vul code, naam en bestemming in en klik *Aanmaken*. Download daarna de PNG/SVG.
- **Bestemming wijzigen:** pas de URL bij de QR-code aan en klik *Opslaan*.
- Wijzigingen zijn na de Pages-deploy (~1 minuut) live; de QR-code zelf blijft hetzelfde.

> Let op: verander de **code** (de naam links) niet en verplaats de repository niet,
> anders klopt de URL in de geprinte QR-codes niet meer.

QR-bibliotheek: [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) (MIT), meegeleverd in `vendor/`.

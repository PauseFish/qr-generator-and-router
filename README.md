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

- `index.html` — de generator: kies een code, download de QR als PNG of SVG.
- `r/index.html` — de router: zoekt de code op in `links.json` en stuurt door.
- `links.json` — de lijst met codes en bestemmingen. **Dit is het enige bestand dat je hoeft aan te passen.**

## Instellen (eenmalig)

1. Merge deze branch naar `main`.
2. Ga in GitHub naar **Settings → Pages**, kies **Deploy from a branch**, branch `main`, map `/ (root)`.
3. Na een minuut staat de site op `https://pausefish.github.io/qr-generator-and-router/`.

## Nieuwe QR-code maken

1. Voeg een regel toe aan `links.json`, bijv. `"menu": "https://example.com/menu"`.
2. Open de generator, typ `menu`, download de PNG/SVG en print/deel hem.

## Bestemming wijzigen

Pas de URL achter de code in `links.json` aan en commit. De bestaande QR-code
stuurt daarna (na de Pages-deploy, ~1 minuut) door naar de nieuwe bestemming.

> Let op: verander de **code** (de naam links) niet en verplaats de repository niet,
> anders klopt de URL in de geprinte QR-codes niet meer.

QR-bibliotheek: [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) (MIT), meegeleverd in `vendor/`.

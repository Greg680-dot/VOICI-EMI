# VoiCI-EMI Afrique

Application React/Vite qui reprend les huit pages HTML d’origine avec une navigation, un en-tête et un pied de page communs. Les styles Tailwind, polices, icônes et médias sont servis localement.

## Démarrer en local

Prérequis : Node.js 20.19+ et npm.

```powershell
npm.cmd install
Copy-Item .env.example .env
npm.cmd run dev
```

Le site est servi sur <http://127.0.0.1:5173>.

## API Laravel

Lancer l’API Laravel sur `http://127.0.0.1:8000` (par exemple avec `php artisan serve --host 127.0.0.1 --port 8000`). Le proxy Vite transmet les appels `/api/*` à cette adresse afin d’éviter la configuration CORS en développement.

`src/services/api.js` expose `apiRequest(path, options)` pour appeler les routes Laravel, par exemple `apiRequest('articles')` ou `apiRequest('signalements', { method: 'POST', body: donnees })`. Définir `VITE_API_BASE_URL` dans `.env` si le préfixe API diffère de `/api`, et `LARAVEL_API_URL` si Laravel écoute sur une autre adresse. Les routes et formats de données métier restent à raccorder selon le contrat de l’API.

## Pages

| Page | URL locale |
| --- | --- |
| Accueil | `/` |
| Initiative | `/initiative` |
| Blog | `/blog` |
| Formations | `/formation` |
| Agenda | `/agenda` |
| Article | `/article` |
| Attestation | `/page_attestation` |
| Détail d’une formation | `/formation-detail` |

`npm.cmd run build` produit le site statique dans `dist/`.

## Médias

Les images de maquette d’origine pointaient vers des URL Google non accessibles sans session. Elles ont été remplacées par des illustrations SVG locales afin de ne charger aucune image depuis un CDN.

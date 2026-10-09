# Ma Résidence

SaaS de gestion locative multi-bailleurs. Chaque organisation ne voit que ses biens, ses locataires, ses contrats et son argent. Les factures disent ce qui est dû. Seul un paiement validé est un encaissement. L’historique financier ne se supprime pas : une correction est une nouvelle écriture.

La V1 enregistre les paiements déclarés (espèces, transfert, autre) et les fait valider à la main. Aucun opérateur mobile money n’est branché. Le canal de paiement est prévu pour en ajouter un plus tard sans refaire la comptabilité.

## Démarrage

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

En production, utilisez MySQL (`DB_CONNECTION=mysql`) et le fuseau `Africa/Kinshasa`. Les migrations restent portables. Les montants sont des entiers en centimes, jamais des flottants.

Le planificateur émet les loyers, les rappels et les départs proches :

```bash
* * * * * php /chemin/artisan schedule:run
```

## Comptes de démonstration

Mot de passe : `password`

| Rôle | Téléphone |
| --- | --- |
| Super admin | 0900000001 |
| Omar, bailleur | 0810000001 |
| Sarah, recouvrement | 0810000002 |
| Jean, locataire | 0820000001 |

L’organisation de démonstration s’appelle Chez Omar. Le taux enregistré est 1 USD = 2 900 FC.

## Tests

```bash
php artisan test
```

## Application installable

Le manifeste `public/manifest.webmanifest`, les icônes `public/icons/` et le service worker `public/sw.js` préparent l’installation. Le worker ne met en cache que des fichiers statiques (`/build/`, `/icons/`, le favicon et le manifeste). Les pages HTML, les espaces privés, les conversations et les fichiers ne sont pas mis en cache. À l’activation, il retire seulement les anciennes caches statiques de l’application. Il n’efface ni la session, ni les données du compte.

L’installation Android et l’invite « Sur l’écran d’accueil » d’iPhone se vérifient sur un téléphone, avec le site servi en HTTPS. Un serveur local en HTTP ne déclenche pas `beforeinstallprompt`.

## Application Android

`capacitor.config.json` ne contient pas d’adresse de serveur. Aucun APK n’est produit dans ce dépôt : l’adresse HTTPS doit être celle du site réellement déployé, puis ajoutée dans `server.url` au moment de la compilation. Sans cette adresse, l’application Android servirait seulement les fichiers statiques et la connexion ne fonctionnerait pas.

La compilation demande Android Studio, un JDK, Android SDK Platform, Build-Tools et Platform-Tools, avec `ANDROID_HOME` défini. Exemple sous Windows CMD, une fois le site en ligne :

```bat
cd C:\Users\User\ma-residance
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap add android
npx cap sync android
cd android
gradlew.bat assembleDebug
```

L’APK de debug se trouve ensuite dans `android\app\build\outputs\apk\debug\`. Avant `cap sync`, renseigner `server.url` avec l’origine HTTPS du site déployé et garder `cleartext` à `false`. En production, `SESSION_SECURE_COOKIE=true`.

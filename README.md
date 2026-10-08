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

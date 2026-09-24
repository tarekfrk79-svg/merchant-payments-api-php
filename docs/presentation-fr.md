# Présentation orale — environ 60 secondes

« J’ai développé MerchantPay API, un projet pédagogique indépendant en PHP 8.4 et Symfony 7.4, sans affiliation avec Lemonway. Il simule des paiements de marchands, sans paiement réel ni donnée bancaire.

L’API permet de créer un marchand, d’initier un paiement, puis de consulter son statut. Les montants sont stockés en centimes avec des entiers pour éviter les erreurs d’arrondi.

Le point central est l’idempotence : si un client répète une requête avec la même clé, il récupère le même paiement. S’il change le contenu, l’API renvoie un conflit. Une contrainte unique PostgreSQL empêche les doublons, y compris lorsque deux insertions entrent en concurrence.

J’ai séparé les contrôleurs, la validation et la logique métier. Le service dépend d’une interface de traitement, injectée avec un simulateur. Les écritures sont protégées par une clé API.

Le projet est testé avec PHPUnit, analysé avec PHPStan et déployé sur Railway depuis GitHub. C’est un MVP démontrable ; l’authentification par marchand et le traitement asynchrone sont les prochaines étapes. »

## Cinq sujets à expliquer

1. Entiers en centimes : précision et validation jusqu’à la base.
2. Idempotence : empreinte canonique, 201/200/409 et contrainte unique.
3. Injection de dépendances : interface du processeur et simulateur déterministe.
4. Responsabilités : HTTP, DTO/Validator, service métier et repositories.
5. Qualité et livraison : tests HTTP/PostgreSQL, PHPStan et déploiement Railway.

## Démonstration en deux minutes

Ouvrir la page et `/health`, lancer `scripts/demo.sh` avec la clé fournie en privé, montrer le même UUID au rejeu puis le 409. Terminer sur `PaymentService.php`, l’index unique de la migration et le résultat GitHub Actions. Ne pas présenter le prototype comme une infrastructure financière de production.

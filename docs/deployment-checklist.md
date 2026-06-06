# Deployment Checklist

Petit aide-memo pour deployer ce projet Symfony sans rien oublier.

## Before Deploy

1. `git status` doit etre propre.
2. `php bin/phpunit` doit passer en local.
3. Verifier les variables d'environnement de prod.
4. Verifier que `DATABASE_URL` pointe vers la bonne base.
5. Verifier que `APP_ADMIN_PASSWORD_HASH` est bien defini si l'acces admin est active.

## Deploy Steps

1. Mettre a jour le code source.
2. Installer les dependances PHP en prod:

```bash
composer install --no-dev --optimize-autoloader
```

3. Appliquer les migrations Doctrine:

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

4. Compiler les assets maps:

```bash
php bin/console asset-map:compile --env=prod
```

5. Vider et rechauffer le cache:

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## After Deploy

1. Ouvrir la page d'accueil.
2. Ouvrir une page d'extrait.
3. Tester la connexion admin.
4. Tester une recherche simple.
5. Si l'import CSV est actif, faire un import de controle avec une petite ligne de test.

## Quick Rollback

1. Revenir au commit precedent.
2. Rejouer `composer install --no-dev --optimize-autoloader`.
3. Refaire `php bin/console doctrine:migrations:migrate --no-interaction --env=prod` seulement si la migration precedente n'a pas casse le schema.
4. Revider le cache avec `php bin/console cache:clear --env=prod`.

# Installation

```bash
docker-compose up -d --build
```

Inside of PHP container:

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

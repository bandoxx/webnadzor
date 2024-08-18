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

## User permission rules

Possible user permissions are:

- Korisnik => 1
- Moderator => 2
- Admin => 3
- Root => 4

Korisnik permissions - For this permission, user is assigned to one client, and selected locations inside of that client

Moderator permissions - It has all locations inside of one client

Admin - It has all locations and can have more then 1 client assigned to it

Root - It has access to all locations and all clients, with the possibility to edit data
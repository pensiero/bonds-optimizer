Bonds optimizer
========================

Optimize your strategy of bonds buy and selling

### How to setup

Copy distribution files
```
cp .env.dist .env
cp docker-compose.override.yml.dist docker-compose.override.yml
cp config/docker/web/.bash_history.dist config/docker/web/.bash_history
```

Start docker containers
```
docker-compose up -d
```

Enter the web container
```
docker-compose exec web bash
```

Install packages and setup db (exec one command at time)
```
yarn install
composer install
./bin/console doctrine:database:create
./bin/console doctrine:schema:create
```

### How to crawl bonds

```
./bin/console app:crawl
```

### How to see crawled bonds

Go to https://www.bonds.dev
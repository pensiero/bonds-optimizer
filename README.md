Bonds optimizer
========================

Optimize your strategy of bonds buy and selling

### How to setup

Generate a self-signed SSL certificate
```
openssl req -x509 -out bonds.dev.crt -keyout bonds.dev.key \
  -newkey rsa:2048 -nodes -sha256 \
  -subj '/CN=bonds.dev' -extensions EXT -config <( \
   printf "[dn]\nCN=bonds.dev,*.bonds.dev\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:bonds.dev,DNS:*.bonds.dev\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth")
```
and move its files to the `config/docker/nginx-proxy/certs/` folder
```
mv bonds.dev.* config/docker/nginx-proxy/certs/
```
_Note: You need to trust your self-signed certificates. Check how to do it on OSX ([add certificate](https://support.apple.com/guide/keychain-access/add-certificates-to-a-keychain-kyca2431/mac), [trust certificate](https://support.apple.com/guide/keychain-access/change-the-trust-settings-of-a-certificate-kyca11871/mac))._

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
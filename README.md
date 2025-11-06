# ohmyhousehold

## Docker Compose setup
for local development (uses `compose.yaml` and `compose.override.yaml` automatically):
```shell
docker compose build --pull --no-cache
docker compose run --rm web /setup.sh
# docker compose run --rm web doctrine:migrations:migrate
docker compose up -d
docker compose exec -u www-data -it web php bin/console doctrine:migrations:migrate
```

Load demo data (use `--append` or you'll lose pre-defined data and break this app):
```shell
docker compose exec -u www-data -it web php bin/console doctrine:fixtures:load --append --group=demo
docker compose exec -u www-data -it web php bin/console doctrine:fixtures:load --append --group=demo_supplies
```

for production-mode during development:
```shell
docker compose -f compose.yaml -f compose.prod_ready.yaml build --pull --no-cache
docker compose -f compose.yaml -f compose.prod_ready.yaml up -d
```

## To run Composer and Yarn during development:
```shell
 docker compose run web /setup.sh 
```

## Jobs
### Sending queued (async) e-mails:
```shell
php bin/console messenger:consume async -vv
```

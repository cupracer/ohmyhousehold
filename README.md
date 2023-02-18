# ohmyhousehold2

## Docker Compose setup
for local development (uses `compose.yaml` and `compose.override.yaml` automatically):
```shell
docker compose build --pull --no-cache
docker compose up -d
```

for production-mode during development:
```shell
docker compose -f compose.yaml -f compose.prod_ready.yaml build --pull --no-cache
docker compose -f compose.yaml -f compose.prod_ready.yaml up -d
```

## To run Composer and Yarn during development:
```shell
 docker compose run -e XDEBUG_MODE=off web /setup.sh 
```

## Jobs
### Sending queued (async) e-mails:
```shell
php bin/console messenger:consume async -vv
```

# ohmyhousehold2

## Docker Compose setup
for local development (uses `compose.yaml` and `compose.override.yaml` automatically):
```
docker compose build --pull --no-cache
docker compose up -d
```

for production:
```
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

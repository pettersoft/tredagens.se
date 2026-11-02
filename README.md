Källkoden till https://tredagens.se/

En enkel statisk hemsida. 

## Utveckling

```sh
docker compose up --build
```

Sidan körs på http://localhost:8090, med `src/` monterad och xdebug på port 9003.

## Produktion

Pushar till `main` bygger imagen `ghcr.io/pettersoft/tredagens-web`, pushar den till GHCR
och deployar `docker-compose.prod.yml` till servern via SSH (se `.github/workflows/ci-cd.yml`).

Containern `tredagens-web` lyssnar på 8080 i det externa nätverket `proxy` och publicerar
inga portar på hosten. Den delade Caddy-instansen når den som `tredagens-web:8080`.

Servern sätts upp på samma sätt som brfking2 (se `brfking2/DEPLOYMENT.md`).
Repo-secrets som krävs:

| Secret           | Värde                                                 |
|------------------|-------------------------------------------------------|
| `DEPLOY_HOST`    | VPS:ens IP                                            |
| `DEPLOY_USER`    | `administrator`                                       |
| `DEPLOY_SSH_KEY` | privata nyckeln i `~/.ssh/github_deploy` på servern   |
| `DEPLOY_PATH`    | `/opt/tredagens` (ägs av `administrator`)             |

Caddy-blocket i `/opt/caddy/Caddyfile`:

```
tredagens.se {
	encode zstd gzip
	reverse_proxy tredagens-web:8080
}
www.tredagens.se {
	redir https://tredagens.se{uri} permanent
}
```

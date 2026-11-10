Källkoden till https://tredagens.se/

En enkel statisk hemsida. 

## Utveckling

```sh
docker compose up --build
```

Sidan körs på http://localhost:8090, med `src/` monterad och xdebug på port 9003.
Statistiken finns på http://localhost:8090/admin/ (lösenord `admin` lokalt).

## Besöksstatistik

Varje sidvisning sparas i SQLite (`/var/www/data/stats.db`, Docker-volymen `tredagens-stats`).
Inga cookies och inga IP-adresser sparas: en besökare identifieras med en hash av IP + user agent
och ett slumpat salt som byts varje dygn, så unika besökare kan räknas per dag men inte följas
mellan dagar. Botar filtreras bort på user agent. Statistiken visas på `/admin/`.

Lösenordet till `/admin/` sätts som en hash i `ADMIN_PASSWORD_HASH` i `.env` på servern. Skapa hashen
(lösenordet syns inte i shell-historiken):

```sh
docker run --rm -it php:8.3-cli php -r 'echo password_hash(readline("Lösenord: "), PASSWORD_DEFAULT), "\n";'
```

och lägg in den i `/opt/tredagens/.env` **inom enkla citattecken** (annars tolkar compose `$`-tecknen):

```
ADMIN_PASSWORD_HASH='$2y$10$...'
```

## Kontaktformulär

Formuläret på `/boka.php` sparar meddelanden i `/var/www/data/contact.db` (samma volym). De granskas
på `/admin/meddelanden.php`, där de markeras som äkta, hanterade eller spam. Spam raderas automatiskt
efter 30 dagar och övriga meddelanden efter ett år.

Skydd mot bottar:

- **Honeypot:** ett dolt fält. Är det ifyllt låtsas sidan att allt gick bra, men inget sparas.
- **Signerad formulärtoken:** CSRF-skydd utan cookies. Inskick som kommer snabbare än 3 sekunder
  eller med en token som är äldre än 2 timmar avvisas.
- **Rate limiting:** högst 3 meddelanden per 10 minuter och 10 per dygn från samma klient,
  och högst 50 per dygn totalt.
- **[ALTCHA](https://altcha.org/) proof-of-work:** körs helt på egen server. Widgeten ligger med fast
  version i `src/static/js/` (MIT-licens). Servern delar ut utmaningar via `/altcha-challenge.php`
  och verifierar dem i `src/lib/altcha.php`.
- **Misstänkta meddelanden** sparas med status `misstänkt` så att du kan bedöma dem själv.
  Det gäller till exempel meddelanden med länkar, främmande skrift, samma text som tidigare
  eller som fyllts i mycket snabbt.

Formuläret kräver `CONTACT_SECRET` i `.env` på servern. Den ska vara minst 32 tecken och kan skapas med:

```sh
openssl rand -hex 32
```

Backup av databaserna (byt `stats.db` mot `contact.db` för meddelandena):

```sh
docker exec tredagens-web php -r '$d=new PDO("sqlite:/var/www/data/stats.db"); @unlink("/var/www/data/backup.db"); $d->exec("VACUUM INTO " . $d->quote("/var/www/data/backup.db"));'
docker cp tredagens-web:/var/www/data/backup.db ./stats-backup.db
docker exec tredagens-web rm /var/www/data/backup.db
```

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

# Playbook — Intégrer le frontend dockerisé dans le boilerplate

> Pour un agent (Sonnet 4.6) chargé d'intégrer **par défaut** le setup full-stack Docker dans
> `D:\Me\Projects\mine\boileplates\symfony-nextjs-starter`, pour que tout nouveau projet généré
> démarre déjà avec le frontend dockerisé et un deploy Docker (sans PM2).

Ce fichier ne réécrit pas les templates : ils sont **identiques** à ceux du playbook de migration
`DOCKERIZE-FRONTEND-MIGRATION.md` (même `frontend/Dockerfile`, `.dockerignore`, 3 compose racine,
`deploy.sh`, `first-deploy.sh`). Lis-le d'abord. Ici on ne décrit que ce qui DIFFÈRE pour le
boilerplate.

## Principe

Dans le boilerplate, les valeurs concrètes sont des placeholders (convention déjà utilisée :
`infra/nginx/[project].domain.com`). Donc en intégrant les templates :

- `<PROJECT>` → le placeholder du boilerplate (ex. `[project]` / `[PROJECT]` — vérifie la
  convention exacte du repo et reste cohérent).
- Ports : garde les mêmes placeholders que ceux déjà présents dans le boilerplate pour le frontend
  prod, le backend prod et le backend local. S'ils sont en dur dans le starter actuel, conserve
  ces valeurs.
- `<PNPM_VERSION>` → pin une version pnpm compatible avec le `lockfileVersion` du lockfile du
  starter (ou ajoute aussi `"packageManager"` au `package.json` template).
- `<BACKEND_PROD_ENV_BLOCK>` → le bloc `environment:` backend prod déjà présent dans le starter.

## Étapes spécifiques au boilerplate

### 1. Appliquer les templates

Applique les mêmes opérations que le playbook de migration (étapes 1 à 7) sur l'arborescence du
boilerplate, en utilisant les placeholders du repo. Donc :

- `frontend/next.config.ts` → `output: "standalone"`.
- Créer `frontend/Dockerfile`, `frontend/.dockerignore`.
- `frontend/package.json` → `"packageManager": "pnpm@<PNPM_VERSION>"`.
- Déplacer les 3 compose de `backend/` vers la racine (garder `name:` = placeholder projet),
  ajouter le service `frontend`, préfixer les chemins par `./backend`.
- Réécrire `infra/deploy.sh` (build-avant-swap, `--env-file ./backend/.env`, sans PM2) et
  `infra/first-deploy.sh` (sans `pnpm build` / `pm2`).

### 2. Purger toute trace de PM2 / "frontend non-dockerisé" dans le boilerplate

C'est LE travail propre au boilerplate. Grep et corrige **partout** (y compris les docs et le
script d'init du starter, pas seulement le code) :

```
pm2 | PM2 | "frontend-" | pnpm build | "cd backend" | backend/docker-compose
```

À traiter notamment :

- Le **script / la skill d'initialisation** du boilerplate (celui qui scaffolde un nouveau
  projet) : s'il installe/configure PM2, génère un nom de process PM2, ou lance `pnpm dev` hors
  Docker → adapter pour le flux Docker.
- Les **templates de docs** du boilerplate : `.context/infra.md`, `.context/architecture.md`,
  `.context/coding-conventions/global.md`, `README.md` — mêmes mises à jour qu'en migration
  (étape 8), mais en version template (placeholders). Le `README` doit montrer
  `docker compose up` depuis la racine comme commande de dev unique.
- Tout `CHANGELOG.md` / specs **historiques** du boilerplate : ne pas réécrire l'historique.

### 3. PAS de section "cutover PM2" ni de "gotcha du commit de transition"

Ces deux points n'existent que lors d'une **migration** d'un projet déjà déployé sous PM2. Pour un
projet **neuf** généré depuis le boilerplate, le tout premier `first-deploy.sh` part de zéro en
Docker : il n'y a jamais eu de PM2 ni d'ancien `deploy.sh` à remplacer. Donc :

- l'encart "One-time cutover from PM2" de `infra.md` n'est **pas** repris dans le template ;
- en revanche, garde une note courte dans le `infra.md` template rappelant que `nginx` (hôte)
  proxifie vers le port frontend publié par le conteneur, et que `first-deploy.sh` doit être lancé
  une fois avant `infra/nginx/setup.sh`.

### 4. Mettre à jour le memo de migration

Ajoute (ou demande à l'utilisateur d'ajouter) dans la doc du boilerplate une mention que les
**projets déjà générés avant ce changement** doivent suivre `DOCKERIZE-FRONTEND-MIGRATION.md` pour
rattraper le nouveau standard.

## Vérification

Comme le boilerplate est paramétrable, vérifie sur une **instanciation jetable** (génère un projet
de test via le script d'init, ou substitue les placeholders à la main dans une copie) :

1. `docker compose config -q` (local) et la variante prod avec `--env-file ./backend/.env` passent.
2. `docker compose up -d frontend` puis `docker logs <project>_frontend` → `✓ Ready`,
   `Network: http://0.0.0.0:3000`, et `curl http://localhost:3000/` renvoie un code HTTP.
   (Rappel : c'est lancer le conteneur dev, pas seulement le builder, qui valide le CMD dev.)

## Résultat attendu

Tout nouveau projet issu du boilerplate :
- tourne en local via un seul `docker compose up` (backend + frontend, hot reload),
- se déploie sur Contabo entièrement en Docker (frontend standalone), sans PM2,
- avec downtime minimal (build-avant-swap) et un build prod déterministe (`NEXT_PUBLIC_*` gravés
  depuis le `.env` commité, `.env.local` exclu).

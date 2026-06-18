# --- base: node + pnpm ---
FROM node:22-alpine AS base
RUN npm install -g pnpm@10.14.0
WORKDIR /app

# --- deps: install dependencies (cached on the lockfile) ---
FROM base AS deps
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

# --- dev: hot-reload server (source bind-mounted by docker-compose.override.yml) ---
FROM deps AS dev
COPY . .
EXPOSE 3000
CMD ["pnpm", "exec", "next", "dev", "--webpack", "-H", "0.0.0.0"]

# --- builder: produce the standalone production build ---
# NEXT_PUBLIC_* vars are baked in here: next build auto-loads frontend/.env
# (committed prod values). .env.local is excluded via .dockerignore so prod builds are deterministic.
FROM deps AS builder
COPY . .
RUN pnpm build

# --- runner: minimal production image (Next.js standalone output) ---
FROM node:22-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV HOSTNAME=0.0.0.0
ENV PORT=3000
RUN addgroup -g 1001 -S nodejs \
    && adduser -u 1001 -S nextjs -G nodejs
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
USER nextjs
EXPOSE 3000
CMD ["node", "server.js"]

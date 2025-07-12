FROM node:lts-alpine AS base

# Stage 1: Install dependencies
FROM base AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci


#  Stage 2: Build the application
FROM base AS builder

ARG ENVIRONMENT=qa

RUN echo "ENVIRONMENT: ${ENVIRONMENT}"

WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Create .env file only if environment file exists
RUN if [ -f "${ENVIRONMENT}" ]; then cp "${ENVIRONMENT}" ./.env; else echo "No environment file found, using defaults"; fi

ENV NODE_ENV=production
RUN npm run build

# Stage 3: Production server
FROM base AS runner
WORKDIR /app

COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public

EXPOSE 3000
CMD ["node", "server.js"]
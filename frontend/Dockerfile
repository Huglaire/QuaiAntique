# Étape 1 : construction du frontend avec Node.js
FROM node:22-alpine AS build

# Répertoire de travail du frontend
WORKDIR /app

# Copie des fichiers de dépendances
COPY package.json package-lock.json ./

# Installation des dépendances
RUN npm ci

# Copie du frontend
COPY . .

# Construction du frontend pour la production
RUN npm run build


# Étape 2 : serveur web Nginx
FROM nginx:alpine

# Copie du build Vite dans le répertoire servi par Nginx
COPY --from=build /app/dist /usr/share/nginx/html

# Configuration Nginx pour le SPA
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Port HTTP du serveur frontend
EXPOSE 80
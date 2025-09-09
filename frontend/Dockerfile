# Stage 1: Build the frontend
FROM node:20-alpine AS builder
WORKDIR /usr/src/app

# Copy package files first (for better caching)
COPY package*.json ./
RUN npm ci --silent

# Copy the rest of the frontend code
COPY . .

# Build the app (index.html is in this folder)
RUN npm run build

# Stage 2: Serve with nginx
FROM nginx:alpine
COPY --from=builder /usr/src/app/dist /usr/share/nginx/html

# Optional: custom nginx config
# COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

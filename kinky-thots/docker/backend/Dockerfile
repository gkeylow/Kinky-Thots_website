FROM node:20-alpine

# Install build dependencies for sharp
RUN apk add --no-cache \
    python3 \
    make \
    g++ \
    vips-dev \
    pkgconfig

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install build tools for sharp (required for building from source)
RUN npm install node-addon-api node-gyp --ignore-scripts

# Install dependencies
RUN npm ci --omit=dev

# Copy application code
COPY . .

# Create directories for uploads
RUN mkdir -p /var/www/kinky-thots/uploads \
    && mkdir -p /var/www/kinky-thots/config

EXPOSE 3001

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:3001/health || exit 1

CMD ["node", "server.js"]

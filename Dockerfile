FROM wordpress:latest

# Install tools and dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    php-xml \
    php-zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Set working directory - the volume will be mounted here via docker-compose.yml
WORKDIR /var/www/html/wp-content/plugins/wp-movie-collector/

# Note: We don't need to COPY files since we're using a volume mount in docker-compose.yml
# Plugin files will be mounted from the local directory during development

# Set proper ownership for the directory where the volume will be mounted
RUN mkdir -p /var/www/html/wp-content/plugins/wp-movie-collector/ && \
    chown -R www-data:www-data /var/www/html/wp-content/plugins/
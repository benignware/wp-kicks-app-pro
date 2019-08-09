#!/usr/bin/env sh

# docker-compose run --rm wp-cli install-wp

# command: sh -c "sleep 20 && wp core install --path=/var/www/html --url=localhost --title=test --admin_user=test --admin_password=test --admin_email=test@example.com"

# Install WordPress.
wp core install \
  --title="My Wordpress Site" \
  --admin_user="wordpress" \
  --admin_password="wordpress" \
  --admin_email="admin@example.com" \
  --url="http://localhost:8080" \
  --skip-email

# Update permalink structure.
wp option update permalink_structure "/%year%/%monthnum%/%postname%/" --skip-themes --skip-plugins

# Activate plugins.
wp plugin activate wordpress-importer

# Activate theme.
wp theme activate kicks-app-pro

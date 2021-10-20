envsubst < /var/www/gatewayapi/.env.prod > /var/www/gatewayapi/.env;
php /var/www/gatewayapi/artisan migrate;
php /var/www/gatewayapi/artisan db:seed;
echo done > done.txt;
apache2-foreground;
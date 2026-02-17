#!/bin/sh

echo "🎬 entrypoint.sh: [$(whoami)] [PHP $(php -r 'echo phpversion();')]"

composer dump-autoload --no-interaction --no-dev --optimize

echo "🎬 artisan commands"

# link storage
# php artisan storage:link

# php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
# php artisan vendor:publish --provider "OwenIt\Auditing\AuditingServiceProvider" --tag="config"
# php artisan vendor:publish --provider "OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"

# 💡 Group into a custom command e.g. php artisan app:on-deploy
# php artisan migrate --no-interaction --force

echo "🎬 start supervisord"

supervisord -c $LARAVEL_PATH/.deploy/config/supervisor.conf

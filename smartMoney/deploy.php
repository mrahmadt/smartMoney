<?php
namespace Deployer;

require 'recipe/laravel.php';
require 'contrib/php-fpm.php';
require 'contrib/npm.php';
require 'contrib/rsync.php';
require 'recipe/deploy/vendors.php';
require 'contrib/supervisord-monitor.php';

set('repository', '');

add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// Hosts


host('personal')
    ->set('remote_user', 'root')
    ->set('hostname', 'personal')
    ->set('deploy_path', '/var/www/smartmoney')
    ->set('rsync_src', __DIR__)
    ->set('rsync_dest','{{release_path}}')
    ->set('rsync',[
        'exclude'      => [
            '.git',
            'deploy.php',
            'node_modules',
            'vendor',
            '.DS_Store',
            'assets',
            'var',
            '.env.local',
            '.env.*.local',
            'tests',
            '.php_cs.dist',
            'docker-compose.yml',
            'docker-compose.override.yml',
            'phpstan.neon',
            'phpunit.xml.dist',
            // 'postcss.config.js',
            // 'tailwind.config.js',
            // 'webpack.config.js',
            'yarn.lock',
            '.php-version',
        ],
        'exclude-file' => false,
        'include'      => [],
        'include-file' => false,
        'filter'       => [],
        'filter-file'  => false,
        'filter-perdir'=> false,
        'flags'        => 'rz', // Recursive, with compress
        'options'      => ['delete'],
        'timeout'      => 60,
    ]);

    
// Hooks
set('composer_action', 'update');

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --apcu-autoloader ');

task('deploy', [
    // 'deploy:prepare',
    'deploy:info', 
    'supervisor:stop',
    'deploy:setup', 
    'deploy:lock', 
    'deploy:release', 
    'rsync',
    // 'deploy:update_code', 
    'deploy:shared',  
    'deploy:writable',  
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'artisan:migrate',
    'npm:install',
    'npm:run:prod',
    'deploy:publish',
    'php-fpm:reload',
    'supervisor:start',
]);

task('supervisor:stop', function () {
    run('/etc/init.d/supervisor stop');
});
task('supervisor:start', function () {
    run('/etc/init.d/supervisor start');
});

task('npm:run:prod', function () {
    cd('{{release_or_current_path}}');
    run('npm run build');
});

set('keep_releases', 5);

set('env', [
    'COMPOSER_ALLOW_SUPERUSER=1' => '1',
]);

set('writable_mode', 'chown');
set('http_user', 'www-data');
set('http_group', 'www-data');

after('deploy:failed', 'deploy:unlock');

task('deploy:update_code')->disable();
after('deploy:update_code', 'rsync');
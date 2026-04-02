<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'https://github.com/mrahmadt/smartMoney.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('personal')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/smartMoney');

// Hooks

after('deploy:failed', 'deploy:unlock');

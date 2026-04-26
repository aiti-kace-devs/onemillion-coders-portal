<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

require 'recipe/laravel.php';
require 'prod-deploy/web.php';
require 'prod-deploy/queue.php';


if (file_exists(__DIR__ . '/.env.deploy')) {
    $lines = file(__DIR__ . '/.env.deploy', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (!str_starts_with(trim($line), '#')) {
            putenv($line);
        }
    }
}

// GitHub repo via personal access token (HTTPS instead of SSH)
set('repository', 'https://' . getenv('GITHUB_TOKEN') . '@github.com/aiti-kace-devs/onemillion-coders-portal.git');
set('branch', getenv('DEPLOY_BRANCH'));
set('git_tty', false); // Must be false for token-based auth

// ─── Shared config ───────────────────────────────────────────────────────────
set('application', 'omcp-app');
set('git_tty', false);
set('keep_releases', 5);
set('writable_mode', 'chmod');

set('shared_files', ['.env']);
set('shared_dirs', [
    // Laravel
    'storage',
    // Statamic
    'content',
    'users',
    'node_modules',
    // Backpack & public uploads
    'public/uploads',
]);

set('writable_dirs', [
    // Laravel
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    // Statamic
    'content',
    'users',
    'resources/blueprints',
    'resources/fieldsets',
    'resources/forms',
    // Backpack
    'public/uploads',
]);
set('writable_mode', 'chmod');
set('writable_chmod_mode', '775');
set('writable_use_sudo', true);
set('sub_directory', 'backend');

set('writable_mode', 'chmod');
set('writable_chmod_mode', '775');

// Options


// ─── Hosts ───────────────────────────────────────────────────────────────────
host('web')
    ->setHostname(getenv('DEPLOY_WEB_PRIVATE_IP'))
    ->setRemoteUser(getenv('DEPLOY_USER'))
    ->setPort((int) getenv('DEPLOY_SSH_PORT'))
    ->setIdentityFile('~/.ssh/deployer_key')
    ->set('deploy_path', getenv('DEPLOY_PATH'))
    ->set('bin/php', 'php')
    ->set('labels', ['role' => 'web']);

host('queue')
    ->setHostname(getenv('DEPLOY_QUEUE_PRIVATE_IP'))
    ->setRemoteUser(getenv('DEPLOY_USER'))
    ->setPort((int) getenv('DEPLOY_SSH_PORT'))
    ->setIdentityFile('~/.ssh/deployer_key')
    ->set('deploy_path', getenv('DEPLOY_PATH'))
    ->set('bin/php', 'php')
    ->set('labels', ['role' => 'queue']);

// ─── Deployment pipeline ─────────────────────────────────────────────────────

task('deploy:update-env-if-needed', function () {
    $localEnvFile = '.env.prod';

    if (file_exists($localEnvFile)) {
        writeln("<info>Uploading .env file to servers...</info>");
        upload($localEnvFile, '{{deploy_path}}/shared/.env');
        writeln("<info>✓ .env file updated</info>");
    } else {
        writeln("<comment>No .env.prod found locally, skipping .env update.</comment>");
    }
});

// Set umask so deploy creates group-readable files
task('deploy:set-umask', function () {
    run('umask 0002');
})->select('role=web');

task('deploy:prepare-storage', function () {
    run('cd {{release_path}} && mkdir -p storage/framework/{sessions,views,cache}');
    run('cd {{release_path}} && mkdir -p bootstrap/cache');
    run('sudo chmod -R 775 {{deploy_path}}/shared/storage');
    run('sudo chmod -R 775 {{release_path}}/bootstrap/cache');
});

task('deploy:update-env', function () {
    $localEnvFile = '.env.prod';

    if (!file_exists($localEnvFile)) {
        throw new \Exception('.env.prod file not found locally');
    }

    // Upload new .env to all servers
    upload($localEnvFile, '{{deploy_path}}/shared/.env');

    // Recache config on all servers
    run('cd {{current_path}} && {{bin/php}} artisan config:cache');

    writeln("<info>✓ .env updated and config cached on all servers</info>");

    // Restart services based on role
    $role = get('labels')['role'] ?? '';
    if ($role === 'web') {
        run('sudo systemctl reload php-fpm');
        writeln("<info>✓ Web server restarted</info>");
    } elseif ($role === 'queue') {
        run('sudo supervisorctl restart all');
        writeln("<info>✓ Queue workers restarted</info>");
    }
});

task('deploy:take-ownership', function () {
    $sharedPath = '{{deploy_path}}/shared';

    run("sudo chown -R deploy:deploy {$sharedPath}/storage");
    run("sudo chown -R deploy:deploy {$sharedPath}/content");
    run("sudo chown -R deploy:deploy {$sharedPath}/users");
    run("sudo chown -R deploy:deploy {$sharedPath}/public/uploads");
    run("sudo chmod -R 775 {$sharedPath}/storage");
})->select('role=web');

task('deploy:handoff-permissions', function () {
    $sharedPath = '{{deploy_path}}/shared';
    $currentPath = '{{current_path}}';
    $releasePath = '{{release_path}}';

    // Hand storage to laravelapp
    run("sudo chown -R laravelapp:laravelapp {$sharedPath}/storage");
    run("sudo chown -R laravelapp:laravelapp {$sharedPath}/content");
    run("sudo chown -R laravelapp:laravelapp {$sharedPath}/users");
    run("sudo chown -R laravelapp:laravelapp {$sharedPath}/public/uploads");
    run("sudo chmod -R 775 {$sharedPath}/storage");
    run("sudo chown -R laravelapp:laravelapp {$currentPath}/bootstrap/cache");
    run("sudo chmod -R 775 {$currentPath}/bootstrap/cache");

    // Make release files readable by nginx and php-fpm
    run("sudo find {$releasePath} -type f -exec chmod 664 {} \;");
    run("sudo find {$releasePath} -type d -exec chmod 775 {} \;");

    run("sudo chmod -R 755 {$releasePath}");

    run("sudo restorecon -Rv {$releasePath}");
    run("sudo restorecon -Rv {$sharedPath}/storage");
})->select('role=web');

task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:update-env-if-needed',
    'deploy:prepare-storage',
    'deploy:vendors',
    'deploy:writable',
    'artisan:storage:link',
    'artisan:config:cache',
    'artisan:route:cache',
    'artisan:view:cache',
    'artisan:event:cache',
    'deploy:web',
    'deploy:queue',
    'deploy:publish',
    'deploy:unlock',
    'deploy:cleanup',
]);

before('deploy:shared', 'deploy:take-ownership');

before('deploy:update_code', 'deploy:set-umask');

after('deploy:publish', 'deploy:handoff-permissions');

after('deploy:failed', 'deploy:unlock');

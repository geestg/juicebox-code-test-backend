<?php

use Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

chdir(dirname(__DIR__));

if (! is_file('.env')) {
    $example = file_get_contents('.env.example');
    $password = bin2hex(random_bytes(16));
    $environment = preg_replace('/^DB_PASSWORD=[^\r\n]*/m', 'DB_PASSWORD='.$password, $example, 1, $count);

    if ($count !== 1 || file_put_contents('.env', $environment) === false) {
        throw new RuntimeException('Could not create .env. Check DB_PASSWORD in .env.example.');
    }
}

$environment = Dotenv::parse(file_get_contents('.env'));

if (($environment['DB_CONNECTION'] ?? '') !== 'mysql' || empty($environment['DB_PASSWORD'])) {
    throw new RuntimeException('Set DB_CONNECTION=mysql and a local DB_PASSWORD in .env.');
}

run(['docker', 'compose', 'up', '-d', '--wait']);
run([PHP_BINARY, 'artisan', 'config:clear']);

if (empty($environment['APP_KEY'])) {
    run([PHP_BINARY, 'artisan', 'key:generate', '--no-interaction']);
}

run([PHP_BINARY, 'artisan', 'migrate', '--no-interaction']);

function run(array $command): void
{
    $process = new Process($command);
    $process->setTimeout(300);
    $process->mustRun(fn (string $type, string $output) => print $output);
}

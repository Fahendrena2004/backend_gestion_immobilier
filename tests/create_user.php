<?php
$basePath = dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
$app = require_once $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'admin2@test.com';
$name = $argv[2] ?? 'Admin2';
$cin = $argv[3] ?? 'CIN-ADMIN2';

DB::table('users')->insert([
    'name' => $name,
    'email' => $email,
    'password' => Hash::make('Password1!'),
    'telephone' => '03400000000',
    'cin' => $cin,
    'role' => 'admin',
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo 'OK';

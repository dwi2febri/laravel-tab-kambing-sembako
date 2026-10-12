<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupLocalDatabase extends Command
{
    protected $signature = 'app:setup-local-db';

    protected $description = 'Create a dedicated local MySQL database without changing existing data';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Hanya untuk pengembangan lokal.');

            return self::FAILURE;
        }
        try {
            $db = new \PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $db->exec('CREATE DATABASE IF NOT EXISTS tabung_kambing_sembako CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $env = file_get_contents(base_path('.env'));
            foreach (['DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_DATABASE' => 'tabung_kambing_sembako', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => ''] as $key => $value) {
                $env = preg_match('/^#? ?'.preg_quote($key, '/').'=/m', $env) ? preg_replace('/^#? ?'.preg_quote($key, '/').'=.*$/m', $key.'='.$value, $env) : $env."\n".$key.'='.$value;
            }
            file_put_contents(base_path('.env'), $env);
            $this->info('Database MySQL tabung_kambing_sembako siap. Jalankan migrate --seed.');

            return self::SUCCESS;
        } catch (\PDOException $exception) {
            $this->warn('MySQL lokal belum tersedia. SQLite tetap digunakan.');

            return self::FAILURE;
        }
    }
}

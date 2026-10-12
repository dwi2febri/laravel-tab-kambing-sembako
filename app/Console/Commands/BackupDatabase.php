<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup {--days=14}';

    protected $description = 'Create a private transaction-consistent database snapshot with retention';

    public function handle(): int
    {
        $tables = ['users', 'categories', 'products', 'product_images', 'product_items', 'saving_plans', 'user_savings', 'installments', 'payment_methods', 'payments', 'handovers', 'notifications', 'settings', 'activity_logs'];
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        $data = DB::transaction(function () use ($tables) {
            $snapshot = [];
            foreach ($tables as $table) {
                $snapshot[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }

return $snapshot;
        });
        $path = 'backups/database-'.now()->format('Y-m-d-His').'.json';
        Storage::disk('local')->put($path, json_encode(['version' => 1, 'created_at' => now()->toIso8601String(), 'tables' => $data], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $cutoff = now()->subDays(max(1, (int) $this->option('days')))->timestamp;
        foreach (Storage::disk('local')->files('backups') as $file) {
            if (Storage::disk('local')->lastModified($file) < $cutoff) {
                Storage::disk('local')->delete($file);
            }
        }
        $this->info('Backup privat: '.$path);

        return self::SUCCESS;
    }
}

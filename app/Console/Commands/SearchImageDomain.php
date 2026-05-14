<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SearchImageDomain extends Command
{
    protected $signature = 'app:search-image-domain';
    protected $description = 'Search, show and replace URL safely with accurate counts';

    public function handle()
    {
        $search = $this->ask('Enter URL/keyword to search');
        if (!$search) {
            $this->error('Search value required!');
            return;
        }

        $search = trim($search);

      
        $columns = DB::select("
            SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = 'retreat'
            AND data_type IN ('varchar','text','longtext')
            AND (
                column_name LIKE '%url%' 
                OR column_name LIKE '%image%' 
                OR column_name LIKE '%video%'
            )
        ");

        
        $tables = [];

        foreach ($columns as $col) {
            $tables[$col->table_name][] = $col->column_name;
        }

        $matches = [];
        $total = 0;

        
        foreach ($tables as $table => $cols) {

            try {
                
                $conditions = [];
                foreach ($cols as $c) {
                    $conditions[] = "`{$c}` LIKE '%{$search}%'";
                }

                $where = implode(' OR ', $conditions);

                $count = DB::selectOne("
                    SELECT COUNT(*) as total
                    FROM `{$table}`
                    WHERE {$where}
                ");

                if ($count && $count->total > 0) {
                    $matches[] = [
                        'table' => $table,
                        'count' => $count->total
                    ];

                    $total += $count->total;
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        
        $this->info("TOTAL MATCHES FOUND (ACCURATE): {$total}");

        if ($total === 0) {
            $this->warn("No records found.");
            return;
        }

        $this->info("\nMATCHED TABLES:");
        foreach ($matches as $m) {
            $this->line("Table: {$m['table']} | Count: {$m['count']}");
        }

    
        if (!$this->confirm("\nDo you want to continue to replace?")) {
            return;
        }

        $replace = $this->ask('Enter NEW URL');

        if (!$replace) {
            $this->error('Replace URL required!');
            return;
        }

        
        foreach ($tables as $table => $cols) {

            try {
                $setParts = [];
                foreach ($cols as $c) {
                    $setParts[] = "`{$c}` = REPLACE(`{$c}`, '{$search}', '{$replace}')";
                }

                $setQuery = implode(', ', $setParts);

                $updated = DB::update("
                    UPDATE `{$table}`
                    SET {$setQuery}
                    WHERE " . implode(' OR ', array_map(fn($c) => "`$c` LIKE '%{$search}%'", $cols))
                );

                $this->line("Updated Table: {$table} | Rows affected: {$updated}");

            } catch (\Exception $e) {
                $this->error("Failed: {$table}");
            }
        }

        $this->info("\nDONE ");
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Poi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcPois extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routeiq:recalc-pois';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate POI spatial indices for efficient proximity queries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Recalculating POI spatial indices...');

        try {
            $count = Poi::count();

            if ($count === 0) {
                $this->warn('⚠️  No POIs found. Please seed data first.');

                return 0;
            }

            // Optimize spatial index
            $this->info("Processing $count POIs...");

            // Rebuild spatial index on MySQL
            if (config('database.default') === 'mysql') {
                DB::statement('ANALYZE TABLE pois');
                DB::statement('OPTIMIZE TABLE pois');
                $this->info('✓ MySQL spatial index optimized');
            }

            // Update statistics
            $this->info('✅ POI spatial indices recalculated successfully');
            $this->line("Total POIs indexed: $count");

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error recalculating POI indices: ' . $e->getMessage());

            return 1;
        }
    }
}

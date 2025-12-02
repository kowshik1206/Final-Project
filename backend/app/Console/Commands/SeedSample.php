<?php

namespace App\Console\Commands;

use Database\Seeders\RouteIQSeeder;
use Illuminate\Console\Command;

class SeedSample extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routeiq:seed-sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed sample users, vehicles, trips, and POIs for RouteIQ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌱 Seeding RouteIQ sample data...');

        try {
            $this->call('db:seed', ['--class' => RouteIQSeeder::class]);
            $this->info('✅ Sample data seeded successfully');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error seeding data: ' . $e->getMessage());

            return 1;
        }
    }
}

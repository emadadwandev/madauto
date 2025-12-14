<?php

namespace App\Console\Commands;

use App\Models\Menu;
use App\Services\CareemMenuTransformer;
use Illuminate\Console\Command;

class GenerateCareemPayload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'careem:generate-payload {menu_id : The ID of the menu} {--save= : Save to file path} {--format=json : Output format (json or pretty)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the Careem API payload for a menu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $menuId = $this->argument('menu_id');
        $format = $this->option('format');
        $savePath = $this->option('save');

        // Find menu
        $menu = Menu::with(['items.modifierGroups.modifiers', 'locations'])->find($menuId);

        if (!$menu) {
            $this->error("Menu with ID {$menuId} not found.");
            return 1;
        }

        $this->info("Generating Careem payload for menu: {$menu->name}");
        $this->line('');

        // Transform menu
        $transformer = new CareemMenuTransformer();
        $payload = $transformer->transform($menu);

        // Format output
        if ($format === 'pretty') {
            $output = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $output = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }

        // Display payload
        $this->line($output);
        $this->line('');

        // Display statistics
        $this->info('Payload Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Categories', count($payload['categories'] ?? [])],
                ['Items', count($payload['items'] ?? [])],
                ['Modifier Groups', count($payload['modifier_groups'] ?? [])],
                ['Total Modifiers', collect($payload['modifier_groups'] ?? [])->sum(fn($g) => count($g['modifiers']))],
            ]
        );

        // Save to file if requested
        if ($savePath) {
            file_put_contents($savePath, $output);
            $this->info("Payload saved to: {$savePath}");
        }

        $this->line('');
        $this->comment('To sync this menu to Careem, run:');
        $this->line("php artisan queue:work --once");
        $this->line('Or dispatch the sync job from the dashboard.');

        return 0;
    }
}

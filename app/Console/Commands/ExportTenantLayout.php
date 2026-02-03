<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportTenantLayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'layout:export {tenant_id} {--name= : Custom name for the template file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a tenant\'s home_layout to a template file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->argument('tenant_id');
        $customName = $this->option('name');

        // Find the tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");
            return self::FAILURE;
        }

        // Check if tenant has home_layout data
        if (empty($tenant->home_layout)) {
            $this->error("Tenant {$tenant->name} (ID: {$tenantId}) has no home_layout data.");
            return self::FAILURE;
        }

        // Generate filename
        if ($customName) {
            $filename = $customName;
            // Add .json extension if not provided
            if (!str_ends_with($filename, '.json')) {
                $filename .= '.json';
            }
        } else {
            $filename = 'tenant_' . $tenantId . '_home_layout.json';
        }

        // Ensure templates directory exists
        $templatesPath = storage_path('app/templates');
        if (!is_dir($templatesPath)) {
            mkdir($templatesPath, 0755, true);
            $this->info("Created templates directory: {$templatesPath}");
        }

        // Convert layout data to JSON
        $jsonContent = json_encode($tenant->home_layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Save to file
        $filePath = $templatesPath . '/' . $filename;
        file_put_contents($filePath, $jsonContent);

        $this->info("✓ Successfully exported layout for tenant: {$tenant->name}");
        $this->info("✓ File saved to: {$filePath}");
        $this->newLine();
        $this->line("Template name: {$filename}");
        $this->line("Tenant: {$tenant->name} (ID: {$tenantId})");
        $this->line("File size: " . number_format(strlen($jsonContent)) . " bytes");

        return self::SUCCESS;
    }
}

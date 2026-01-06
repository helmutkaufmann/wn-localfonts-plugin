<?php namespace Mercator\LocalFonts\Console;

use Winter\Storm\Support\Facades\File;

class RemoveFonts extends BaseCommand
{
    protected $name = 'localfonts:remove';
    
    /**
     * Updated signature with new flags
     */
    protected $signature = 'localfonts:remove 
                            {target : Family name or "Family:SelectorID"} 
                            {--dir= : Custom directory} 
                            {--static : Only remove static files} 
                            {--variable : Only remove variable blocks} 
                            {--force : Skip confirmation}';

    public function handle()
    {
        try {
            $path = $this->getFontsPath($this->option('dir'));
            $manifest = $this->getManifest($path);
            $target = $this->argument('target');

            // 1. Wipe everything
            if ($target === 'all') {
                if ($this->option('force') || $this->confirm("Delete ALL fonts in $path?")) {
                    File::cleanDirectory($path);
                    return $this->info("Directory cleaned.");
                }
                return;
            }

            if (!isset($manifest[$target]) && !str_contains($target, ':')) {
                return $this->error("Target '$target' not found.");
            }

            $toRemove = [];

            // 2. Identify variants to delete based on flags
            foreach ($manifest as $family => $variants) {
                foreach ($variants as $id => $data) {
                    // Filter by family or specific selector
                    if ($target !== $family && $target !== "$family:$id") continue;

                    $isVariable = str_contains($id, '-900'); // Variable fonts contain the range

                    if ($this->option('static') && $isVariable) continue;
                    if ($this->option('variable') && !$isVariable) continue;

                    $toRemove[] = ['family' => $family, 'id' => $id, 'file' => $data['file']];
                }
            }

            if (empty($toRemove)) {
                return $this->info("No variants matched your filters.");
            }

            // 3. Confirmation
            if (!$this->option('force')) {
                if (!$this->confirm("Delete " . count($toRemove) . " variants?")) return;
            }

            // 4. Execution
            foreach ($toRemove as $item) {
                unset($manifest[$item['family']][$item['id']]);
                if (empty($manifest[$item['family']])) unset($manifest[$item['family']]);
                
                $this->safeDelete($path, $manifest, $item['file']);
                $this->line(" - Removed: {$item['family']}:{$item['id']}");
            }

            $this->saveManifest($path, $manifest);
            $this->info("Success!");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function safeDelete($path, $manifest, $filename)
    {
        $stillInUse = false;
        foreach ($manifest as $f) {
            foreach ($f as $v) {
                if ($v['file'] === $filename) $stillInUse = true;
            }
        }
        if (!$stillInUse) File::delete($path . '/' . $filename);
    }
}
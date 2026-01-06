<?php namespace Mercator\LocalFonts\Console;

class ListFonts extends BaseCommand
{
    protected $name = 'localfonts:list';
    
    // Added optional {family} argument
    protected $signature = 'localfonts:list {family? : The font family name to filter by} {--dir= : Custom directory to scan}';

    public function handle()
    {
        try {
            $path = $this->getFontsPath($this->option('dir'));
            $manifest = $this->getManifest($path);
            $filter = $this->argument('family');

            if (empty($manifest)) {
                return $this->info("No localized fonts found in: $path");
            }

            $foundCount = 0;
            foreach ($manifest as $family => $variants) {
                // Apply filter if provided (case-insensitive)
                if ($filter && strtolower($family) !== strtolower($filter)) {
                    continue;
                }

                $foundCount++;
                $this->info("\nFamily: $family");
                $this->line(sprintf("%-25s | %-10s | %-10s | %s", 'Selector ID', 'Weight', 'Style', 'Subset'));
                $this->line(str_repeat('-', 80));

                foreach ($variants as $id => $data) {
                    $this->line(sprintf(
                        "%-25s | %-10s | %-10s | %s", 
                        $id, 
                        $data['weight'], 
                        ucfirst($data['style']), 
                        $data['subset']
                    ));
                }
            }

            if ($filter && $foundCount === 0) {
                $this->error("No localized variants found for family: '$filter'");
            }
            
            $this->line("");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
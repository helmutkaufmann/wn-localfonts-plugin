<?php namespace Mercator\LocalFonts\Console;

use Winter\Storm\Support\Facades\File;
use Winter\Storm\Support\Facades\Http;
use Winter\Storm\Support\Facades\Config;

class AddFonts extends BaseCommand
{
    protected $name = 'localfonts:add';
    protected $signature = 'localfonts:add {font} {--force} {--dir=} {--full}';

    protected $fontRegex = [
        'blocks' => '/(?:\/\*\s*(.*?)\s*\*\/)?\s*@font-face\s*{(.*?)}/s',
        'family' => '/font-family:\s*([^;]+);/',
        'url'    => '/url\(([^)]+)\)/',
        'weight' => '/font-weight:\s*([^;]+);/',
        'style'  => '/font-style:\s*([^;]+);/',
    ];

    public function handle()
    {
        try {
            $fontsPath = $this->getFontsPath($this->option('dir'));
            $input = $this->argument('font');
            $preferredSubsets = Config::get('mercator.localfonts::subsets', []);

            $urls = $this->getDiscoveryUrls($input, $this->option('full'));
            $response = null;

            foreach ($urls as $source => $url) {
                $this->line("Searching $source...");
                
                $res = $this->fetchCss($url);

                // Fallback: If --full failed on this specific provider, try the simple version 
                // before giving up on the provider entirely.
                if (($res->code !== 200 || !str_contains($res->body, '@font-face')) && $this->option('full')) {
                    $fallbackUrl = $this->getFallbackUrl($source, $input);
                    if ($fallbackUrl) {
                        $this->line(" - $source full set not found, trying basic...");
                        $res = $this->fetchCss($fallbackUrl);
                    }
                }

                if ($res->code === 200 && str_contains($res->body, '@font-face')) {
                    $response = $res;
                    $this->info("Found on $source!");
                    break;
                }
            }

            if (!$response) return $this->error("Font not found or empty CSS response.");

            $manifest = $this->getManifest($fontsPath);
            preg_match_all($this->fontRegex['blocks'], $response->body, $matches);

            $count = 0;
            foreach ($matches[2] as $index => $blockBody) {
                $rawComment = !empty($matches[1][$index]) ? trim($matches[1][$index]) : 'all';
                $subset = strtolower($rawComment);
                
                $isAllowed = empty($preferredSubsets) || 
                             $subset === 'all' || 
                             in_array($subset, array_map('strtolower', $preferredSubsets)) ||
                             str_contains(strtolower($input), $subset);

                if (!$isAllowed) continue;

                preg_match($this->fontRegex['family'], $blockBody, $f);
                preg_match($this->fontRegex['url'], $blockBody, $u);

                if (!$f || !$u) continue;

                $family = trim($f[1], "'\"");
                $fontUrl = trim($u[1], "'\"");

                if (str_starts_with($fontUrl, '//')) $fontUrl = "https:" . $fontUrl;
                elseif (str_starts_with($fontUrl, './')) $fontUrl = "https://cdn.fontshare.com" . ltrim($fontUrl, '.');

                $fileName = basename(parse_url($fontUrl, PHP_URL_PATH));
                $weight = preg_match($this->fontRegex['weight'], $blockBody, $wm) ? trim($wm[1]) : '400';
                $style = preg_match($this->fontRegex['style'], $blockBody, $sm) ? trim($sm[1]) : 'normal';
                
                $cleanWeight = str_replace([' ', '"', "'"], '-', $weight);
                $fontId = "{$cleanWeight}-{$style}-" . substr(md5($blockBody), 0, 6);

                if ($this->option('force') || !File::exists($fontsPath . '/' . $fileName)) {
                    $this->line(" - Downloading: $family $weight ($rawComment)");
                    $fileData = Http::get($fontUrl);
                    if ($fileData->code === 200) {
                        File::put($fontsPath . '/' . $fileName, $fileData->body);
                        $count++;
                    }
                } else { $count++; }

                $localBlock = "@font-face {\n" . preg_replace($this->fontRegex['url'], "url('./$fileName')", $blockBody) . "\n}";
                $manifest[$family][$fontId] = [
                    'weight' => $weight, 'style' => $style, 'subset' => $rawComment, 
                    'file' => $fileName, 'css' => $localBlock
                ];
            }

            $this->saveManifest($fontsPath, $manifest);
            $this->info("Success! $count variants managed in: $fontsPath");
        } catch (\Exception $e) { $this->error($e->getMessage()); }
    }

    protected function fetchCss($url)
    {
        return Http::get($url, function($http) {
            $http->setOption(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
            $http->setOption(CURLOPT_FOLLOWLOCATION, true);
        });
    }

    protected function getFallbackUrl($source, $input)
    {
        $slug = strtolower(str_replace(' ', '-', $input));
        $plus = str_replace(' ', '+', $input);

        return match ($source) {
            'Google' => "https://fonts.googleapis.com/css2?family={$plus}&display=swap",
            'Bunny' => "https://fonts.bunny.net/css?family={$slug}&display=swap",
            'Fontshare' => "https://api.fontshare.com/v2/css?f[]={$slug}@400,401&display=swap",
            default => null
        };
    }

    protected function getDiscoveryUrls($input, $full = false)
    {
        if (filter_var($input, FILTER_VALIDATE_URL)) return ['Direct' => $input];
        $slug = strtolower(str_replace(' ', '-', $input));
        $plus = str_replace(' ', '+', $input);
        
        return [
            'Google' => $full ? "https://fonts.googleapis.com/css2?family={$plus}:ital,wght@0,100..900;1,100..900&display=swap" : "https://fonts.googleapis.com/css2?family={$plus}&display=swap",
            'Bunny' => "https://fonts.bunny.net/css?family={$slug}" . ($full ? ":italic,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900" : "") . "&display=swap",
            'Fontshare' => "https://api.fontshare.com/v2/css?f[]={$slug}@" . ($full ? "1,2,300,301,400,401,500,501,600,601,700,701,800,801,900,901" : "400,401") . "&display=swap"
        ];
    }
}
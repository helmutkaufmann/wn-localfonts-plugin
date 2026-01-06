<?php namespace Mercator\LocalFonts\Console;

use Illuminate\Console\Command;
use Cms\Classes\Theme;
use Winter\Storm\Support\Facades\File;

abstract class BaseCommand extends Command
{
    /**
     * Protected regex patterns to prevent config-based syntax corruption
     */
    protected $fontRegex = [
        'blocks'  => '/(?:\/\*\s*([^*]+)\s*\*\/)?\s*@font-face\s*\{([^}]+)\}/is',
        'family'  => '/font-family\s*:\s*[\'"]?([^\'";]+)[\'"]?/i',
        'url'     => '/url\s*\(\s*[\'"]?([^\'"]+?\.(?:woff2|woff|ttf|otf)[^\'"]*?)[\'"]?\s*\)/i',
        'weight'  => '/font-weight\s*:\s*([^;]+)/i',
        'style'   => '/font-style\s*:\s*([^;]+)/i'
    ];

    protected function getFontsPath($subDir = null)
    {
        if (!$subDir) {
            $activeTheme = Theme::getActiveTheme();
            if (!$activeTheme) throw new \Exception("No active theme detected.");
            $path = themes_path($activeTheme->getDirName() . '/assets/src/fonts');
        } else {
            $path = preg_match('/^([a-zA-Z]:\\\\|\\\\|\/)/', $subDir) ? $subDir : base_path($subDir);
        }

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }

        return realpath($path);
    }

    protected function getManifest($path)
    {
        $file = $path . '/manifest.json';
        return File::exists($file) ? json_decode(File::get($file), true) : [];
    }

    protected function saveManifest($path, $manifest)
    {
        File::put($path . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $this->rebuildLess($path, $manifest);
    }

    protected function rebuildLess($path, $manifest)
    {
        $fullCss = "/* GENERATED FONTS FILE - DO NOT EDIT */\n\n";
        foreach ($manifest as $family => $variants) {
            foreach ($variants as $id => $data) {
                $sub = $data['subset'] ?? 'all';
                $fullCss .= "/* $family [$sub] - Variant $id */\n" . $data['css'] . "\n\n";
            }
        }
        File::put($path . '/fonts.less', $fullCss);
    }
}
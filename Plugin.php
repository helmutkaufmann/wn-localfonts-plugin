<?php namespace Mercator\LocalFonts;

use System\Classes\PluginBase;

/**
 * LocalFonts Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'LocalFonts',
            'description' => 'Localize and manage fonts from Google, Bunny, and Fontshare.',
            'author'      => 'Mercator',
            'icon'        => 'icon-font',
            'homepage'    => 'https://github.com/mercator/wn-localfonts-plugin'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        // This method registers the CLI commands into the Artisan kernel
        $this->registerConsoleCommand(
            'localfonts:add', 
            \Mercator\LocalFonts\Console\AddFonts::class
        );

        $this->registerConsoleCommand(
            'localfonts:list', 
            \Mercator\LocalFonts\Console\ListFonts::class
        );

        $this->registerConsoleCommand(
            'localfonts:remove', 
            \Mercator\LocalFonts\Console\RemoveFonts::class
        );
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        // Potential future implementation for automatic theme font detection
    }
}
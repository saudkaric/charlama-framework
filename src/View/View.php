<?php declare(strict_types=1);

namespace Charlama\View;

use Charlama\File\File;
use Charlama\Session\Session;
use Exception;
//use Jenssegers\Blade\Blade;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class View
{
    public function __construct() {}

    public static function render(string $path, array $data = [], ?string $type = null): string
    {
        $data = array_merge($data, [
            'errors'    => Session::flash('errors'),
            'old'       => Session::flash('old')
        ]);

        $type = ($type != null) ? $type . 'Render' : 'bladeRender';
        return static::$type($path, $data);
    }

    public static function bladeRender(string $path, array $data = []): string
    {
        //$blade = new Blade(File::path('views'), File::path('storage/cache'));
        //return $blade->make($path, $data)->render();

        // Configuration
        // Note that you can set several directories where your templates are located
        $pathsToTemplates = [File::path('views')];
        $pathToCompiledTemplates = File::path('storage/cache');

        // Dependencies
        $filesystem = new Filesystem;
        $eventDispatcher = new Dispatcher(new Container);

        // Create View Factory capable of rendering PHP and Blade templates
        $viewResolver = new EngineResolver;
        $bladeCompiler = new BladeCompiler($filesystem, $pathToCompiledTemplates);

        $viewResolver->register('blade', function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        $viewResolver->register('php', function () {
            return new PhpEngine;
        });

        $viewFinder = new FileViewFinder($filesystem, $pathsToTemplates);
        $viewFactory = new Factory($viewResolver, $viewFinder, $eventDispatcher);

        // Render template
        return $viewFactory->make($path, $data)->render();
    }

    public static function viewRender(string $path, array $data = []): string
    {
        $path = 'views' . File::ds() . str_replace(['/', '\\', '.', '@', '|'], File::ds(), $path) . '.php';

        if (!file_exists(File::path($path))) {
            throw new Exception(sprintf("The view file '%s' dones nost exists", $path));
        }

        ob_start();
        extract($data);
        include File::path($path);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }


}

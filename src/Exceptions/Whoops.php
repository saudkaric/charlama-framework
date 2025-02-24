<?php declare(strict_types=1);

namespace Charlama\Exceptions;

use Spatie\Ignition\Ignition;

class Whoops 
{
    public function __construct() {}
    
    public static function handle(string $environment = '') 
    {
        
        switch ($environment) {
            case 'local':
            case 'dev':
            case 'test':
                $inEnvironment = true;
                break;

            default:
                $inEnvironment = false;
                break;
        }
        
        Ignition::make()
                ->applicationPath(ROOT_DIR)
                ->setTheme('dark')
                ->shouldDisplayException($inEnvironment)
                ->register();
    }
}

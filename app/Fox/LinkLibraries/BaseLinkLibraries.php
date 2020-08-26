<?php

namespace App\Fox\LinkLibraries;

use App\Fox\Helpers\NetworkHelper;
use App\Fox\LinkLibraries\DataType\Command;

abstract class BaseLinkLibraries {
    const GLOBAL_NPM_DIRECTORY_LOCATION = '/usr/local/lib/node_modules/@foxcorp/';
    const REMOVE_MODULES_AND_LOCK       = 'rm -rf node_modules/ package-lock.json';

    protected $networkName;

    public function __construct( string $networkName ) {
        $this->networkName = $networkName;
    }

    private function getLambdaContainer(): string {
        return NetworkHelper::getNetworkCode($this->networkName) . '-lambdanode';
    }

    protected function getDockerCommand( string $cmd ): string {
        return sprintf("docker exec -it %s bash -c '%s'", $this->getLambdaContainer(), $cmd);
    }

    protected function getLocalLibraries( string $path ): array {
        $localLibraries = shell_exec( $this->getDockerCommand("cd {$path} && printf \"%s\n\" ./*") );
        $libDirs        = explode("\n", $localLibraries);

        return array_map(
            function($link) {
                return preg_replace('/(.\/|\s)/', "", $link);
            },
            array_filter($libDirs)
        );
    }

    abstract public function commands(): array;
}

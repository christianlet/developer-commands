<?php

namespace App\Fox\LinkLibraries;

use App\Fox\LinkLibraries\DataType\Command;

class NpmLinkLibraries extends BaseLinkLibraries {
    private $localLibraries;

    public function __construct( string $networkName ) {
        parent::__construct( $networkName );

        $this->localLibraries = $this->getLocalLibraries( './local-libraries/@foxcorp/' );
    }

    public function commands(): array {
        return array_merge(
            $this->globalLinkCommands(),
            $this->endpointLinkCommands('read'),
            $this->endpointLinkCommands('write'),
            $this->dependencyLinkCommands()
        );
    }

    private function globalLinkCommands(): array {
        $globalLink = array_map(
            function($library) {
                return new Command(
                    $this->getDockerCommand( "cd ./local-libraries/@foxcorp/{$library} && npm link" ),
                    $library,
                    'global'
                );
            },
            $this->localLibraries
        );

        return $globalLink;
    }

    private function dependencyLinkCommands(): array {
        // * Link dependencies
        $dependenciesLink = [];

        foreach ($this->localLibraries as $library) {
            $dependencies = array_map(
                function($dependency) {
                    return "npm link @foxcorp/{$dependency}";
                },
                $this->getDependencies( "./local-libraries/@foxcorp/{$library}" )
            );

            if( !empty( $dependencies ) ) {
                $dependenciesLink = array_merge(
                    $dependenciesLink,
                    [
                        new Command(
                            $this->getDockerCommand( "cd ./local-libraries/@foxcorp/{$library} && " . implode(' && ', $dependencies) ),
                            $library,
                            'local'
                        )
                    ]
                );
            }
        }

        return $dependenciesLink;
    }

    private function endpointLinkCommands( string $endpoint ): array {
        $buildEndpoint = [
            new Command(
                $this->getDockerCommand( "cd ./{$endpoint}_endpoints && " . self::REMOVE_MODULES_AND_LOCK . " ; ./build.sh ; npm i" ),
                "{$endpoint}_endpoints",
                "file"
            )
        ];

        $globalNpmLinks = array_intersect(
            $this->localLibraries,
            $this->getLocalLibraries( self::GLOBAL_NPM_DIRECTORY_LOCATION ),
            $this->getDependencies( "./{$endpoint}_endpoints" )
        );

        $commands = array_map(
            function($library) use($endpoint) {
                return new Command(
                    $this->getDockerCommand( "cd ./{$endpoint}_endpoints && npm link @foxcorp/{$library}" ),
                    $library,
                    "{$endpoint}_endpoints"
                );
            },
            $globalNpmLinks
        );

        return array_merge(
            $buildEndpoint,
            $commands
        );
    }

    private function getDependencies( string $path ): array {
        $PACKAGE_JSON_PATH = sprintf('%s/package.json', $path);

        $packageFileContents = json_decode(
            shell_exec( $this->getDockerCommand("cat {$PACKAGE_JSON_PATH}") )
        );

        $dependencies = array_filter(
            (array) $packageFileContents->dependencies ?? [],
            function( $package ){
                return preg_match('/foxcorp/', $package);
            },
            ARRAY_FILTER_USE_KEY
        );

        return array_map(
            function($package) {
                return str_replace('@foxcorp/', '', $package);
            },
            array_keys($dependencies)
        );
    }
}

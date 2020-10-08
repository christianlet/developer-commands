<?php

namespace App\Fox\LinkLibraries;

use App\Fox\LinkLibraries\DataType\Command;

class FileLinkLibraries extends BaseLinkLibraries {
    public function commands(): array {
        return array_merge(
            $this->fileLinkCommands(),
            $this->endpointLinkCommands('read'),
            $this->endpointLinkCommands('write')
        );
    }

    private function fileLinkCommands(): array {
        $libraryPath = "~/Sites/api.{$this->networkName}.com/local-libraries/@foxcorp/%s";

        $libraries = array_map(
            function($library) use($libraryPath) {
                $libraryPath = sprintf($libraryPath, $library);

                $this->modifyLocalLibraryDependencies($libraryPath);

                return new Command(
                    $this->getDockerCommand( "cd ./local-libraries/@foxcorp/{$library} && " . self::REMOVE_MODULES_AND_LOCK ),
                    $library,
                    'local'
                );
            },
            $this->getLocalLibraries( './local-libraries/@foxcorp/' )
        );

        return $libraries;
    }

    private function endpointLinkCommands(string $endpoint): array {
        $libraryPath = sprintf('~/Sites/api.%s.com/%s_endpoints', $this->networkName, $endpoint);

        $this->modifyLocalLibraryDependencies($libraryPath, '../local-libraries/\@foxcorp/');

        return  [
            new Command(
                $this->getDockerCommand("cd ./{$endpoint}_endpoints && " . self::REMOVE_MODULES_AND_LOCK . " ; ./build.sh && npm i"),
                "{$endpoint}_endpoints",
                'file'
            )
        ];
    }

    private function modifyLocalLibraryDependencies( string $path, string $packagePath = '../' ): void {
        static $LOCAL_LIBRARY_DEPENDENCIES_FILE = 'dependencies.json';
        static $PACKAGE_DIR_NAME = '@foxcorp';
        $PACKAGE_JSON_PATH = sprintf('%s/package.json', $path);

        $this->createBackupPackageFile( $path );

        shell_exec("cp {$PACKAGE_JSON_PATH} {$LOCAL_LIBRARY_DEPENDENCIES_FILE}");

        $packageJson = json_decode(file_get_contents($LOCAL_LIBRARY_DEPENDENCIES_FILE));

        foreach ($packageJson->dependencies as $package => $version) {
            if( preg_match('/^(' . $PACKAGE_DIR_NAME . '\/)/', $package) ){
                $library = str_replace("{$PACKAGE_DIR_NAME}/", '', $package);

                $packageJson->dependencies->$package = "file:{$packagePath}{$library}";
            }
        }

        file_put_contents($LOCAL_LIBRARY_DEPENDENCIES_FILE, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        shell_exec("mv -f {$LOCAL_LIBRARY_DEPENDENCIES_FILE} {$PACKAGE_JSON_PATH}");
    }

    private function createBackupPackageFile( string $path ): void {
        $backupExists = shell_exec( sprintf("[ -e %s/package.json.bak ] && echo 1 || echo 0", $path) );

        if( (int)$backupExists ) {
            return;
        }

        shell_exec( sprintf('cd %s && cp package.json package.json.bak', $path) );
    }
}

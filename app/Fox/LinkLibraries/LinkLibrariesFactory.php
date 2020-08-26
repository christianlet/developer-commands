<?php

namespace App\Fox\LinkLibraries;

use ReflectionClass;

class LinkLibrariesFactory {
    const LINK_LIBRARIES = [
        'npm'  => NpmLinkLibraries::class,
        'file' => FileLinkLibraries::class
    ];

    public static function getLinkage( string $type, string $network ): BaseLinkLibraries {
        if( empty($type) || !isset(self::LINK_LIBRARIES[$type]) ){
            throw new \Exception("Type '{$type}' does not exist", 1);
        }

        $reflection = new ReflectionClass( self::LINK_LIBRARIES[$type] );

        return $reflection->newInstance( $network );
    }
}

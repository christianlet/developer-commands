<?php

namespace App\Fox\Helpers;

class NetworkHelper {
    const NETWORKS = [
        'fbn' => 'foxbusiness',
        'fnc' => 'foxnews',
        'fs'  => 'foxsports',
        'fts' => 'fts-foxnews'
    ];

    public static function getNetworkName( string $networkCode ): string {
        if( empty($networkCode) || !isset(self::NETWORKS[$networkCode]) ){
            throw new \Exception("Error: Invalid network code '{$networkCode}'", 1);
        }

        return self::NETWORKS[$networkCode];
    }

    public static function getNetworkCode( string $networkName ): string {
        if( !$networkCode = array_search($networkName, self::NETWORKS) ){
            throw new \Exception("Error: Invalid network name '{$networkName}'", 1);
        }

        return $networkCode;
    }
}

<?php

namespace App\Commands\Helpers;

class CommandHelper {
    public static function mapChoices( array $choices ): array {
        $newArray = [];

        foreach ($choices as $key => $choice) {
            $newArray[++$key] = $choice;
        }

        return $newArray;
    }
}

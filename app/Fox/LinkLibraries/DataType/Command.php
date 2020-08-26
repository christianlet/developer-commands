<?php

namespace App\Fox\LinkLibraries\DataType;

class Command {
    private $command;
    private $library;
    private $type;

    public function __construct( string $command, string $library, string $type ) {
        $this->command = $command;
        $this->library = $library;
        $this->type    = $type;
    }



    /**
    * Get the value of command
    *
    * @return  mixed
    */
    public function getCommand() {
        return $this->command;
    }

    /**
    * Get the value of library
    *
    * @return  mixed
    */
    public function getLibrary() {
        return $this->library;
    }

    /**
    * Get the value of type
    *
    * @return  mixed
    */
    public function getType() {
        return $this->type;
    }
}

<?php

namespace App\Services;

use App\KeyWords\Command;
use App\KeyWords\Error;
use App\KeyWords\Lottery;
use App\KeyWords\MonteCarlo;
use App\KeyWords\QiGua;
use App\KeyWords\ShengSheQian;

class CommandRegistry
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    public function register(Command $command): void
    {
        $this->commands[] = $command;
    }

    public function findCommand(string $message): Command
    {
        foreach ($this->commands as $command) {
            if ($command->canHandle($message)) {
                return $command;
            }
        }

        return new Error();
    }

    public function getAllCommands(): array
    {
        return $this->commands;
    }

    private function registerDefaultCommands(): void
    {
        $this->register(new QiGua());
        $this->register(new ShengSheQian());
        $this->register(new Lottery());
        $this->register(new MonteCarlo());
    }
}
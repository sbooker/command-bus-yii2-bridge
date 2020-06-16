<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use Sbooker\CommandBus\Command;

interface CommandHydrator
{
    /**
     * Extracts Command to flat array
     * @param Command $command
     * @return array
     */
    public function extract(Command $command): array;

    /**
     * Converts flat array to Command
     */
    public function hydrate(array $array): Command;
}
<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Registry\Containerized;

final class Yii2DiAdapter implements CommandServiceRegistry
{
    /** @var string|null */
    private $defaultServiceId;

    /** @var array [string $name => string $id] */
    private $idMap;

    public function __construct(?string $defaultServiceId, array $idMap)
    {
        $this->defaultServiceId = $defaultServiceId;
        $this->idMap = $idMap;
    }

    public function get(string $commandName): ?object
    {
        $id = $this->getIdFromMap($this->idMap, $commandName, $this->defaultServiceId);
        if (null === $id) {
            return null;
        }

        return \Yii::$container->get($id);
    }

    private function getIdFromMap(array $map, string $name, ?string $default = null): ?string
    {
        return isset($map[$name]) ? $map[$name] : $default;
    }
}
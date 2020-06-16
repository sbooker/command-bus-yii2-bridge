<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\ReadStorage;

final class ActiveRecordReadRepository implements ReadStorage
{
    /** @var CommandHydrator */
    private $hydrator;

    public function __construct(CommandHydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    public function get(UuidInterface $id): ?Command
    {
        $record =
            CommandRecord::find()
                ->andWhere(['id' => $id->toString()])
                ->one();

        return $record === null ? null : $this->hydrator->hydrate($record->getAttributes());
    }
}
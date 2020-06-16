<?php

declare(strict_types=1);


namespace Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use Sbooker\CommandBus\Command;
use Sbooker\TransactionManager\Yii2ActiveRecord\EntityActiveRecordMapper;
use yii\db\ActiveRecord;

final class CommandActiveRecordMapper implements EntityActiveRecordMapper
{
    /** @var CommandHydrator */
    private $hydrator;

    public function __construct(?CommandHydrator $hydrator = null)
    {
        $this->hydrator = $hydrator ?? new LaminasCommandHydrator();
    }

    public function create(object $entity): ActiveRecord
    {
        if (!$entity instanceof Command) {
            throw new \InvalidArgumentException();
        }

        $attributes = $this->extractAttributes($entity);
        $record = new CommandRecord();

        $record->setAttributes($attributes, false);

        return $record;
    }

    public function update(object $entity, ActiveRecord $activeRecord): void
    {
        if (!$entity instanceof Command || !$activeRecord instanceof CommandRecord) {
            throw new \InvalidArgumentException();
        }

        $activeRecord->setAttributes($this->extractAttributes($entity), false);
    }

    public function hydrate(ActiveRecord $activeRecord): object
    {
        if (!$activeRecord instanceof CommandRecord) {
            throw new \InvalidArgumentException();
        }

        return $this->hydrator->hydrate($activeRecord->getAttributes());
    }

    private function extractAttributes(Command $command): array
    {
        return $this->hydrator->extract($command);
    }
}
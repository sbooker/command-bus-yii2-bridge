<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\Status;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\TransactionManager\Yii2ActiveRecord\UnitOfWork;
use yii\db\ActiveQuery;

final class ActiveRecordWriteRepository implements WriteStorage
{
    /** @var UnitOfWork */
    private $unitOfWork;

    /** @var CommandActiveRecordMapper */
    private $mapper;

    public function __construct(UnitOfWork $unitOfWork, CommandActiveRecordMapper $mapper)
    {
        $this->unitOfWork = $unitOfWork;
        $this->mapper = $mapper;
    }

    public function add(Command $command): void
    {
        $this->unitOfWork->persist($command, $this->mapper);
    }

    public function getAndLock(array $names, UuidInterface $id): ?Command
    {
        $query =
            CommandRecord::find()
                ->andWhere(['id' => $id->toString()])
                ->andWhere(['name' => $names]);

        return $this->getLockedCommand($query);
    }

    public function getFirstToProcessAndLock(array $names): ?Command
    {
        $query = CommandRecord::find()
                ->andWhere(['name' => $names])
                ->andWhere(['status' => [Status::pending()->getRawValue(), Status::created()->getRawValue()]])
                ->andWhere(['<', 'next_attempt_at', (new \DateTimeImmutable())->format('Y-m-d H:i:s')])
                ->orderBy(['next_attempt_at' => SORT_ASC])
        ;

        $record = $this->getCommandRecord($query);

        if (null === $record) {
            return null;
        }

        return $this->getAndLock($names, Uuid::fromString($record->id));
    }

    public function save(Command $command): void
    {
        $this->unitOfWork->scheduleForUpdate($command, $this->mapper);
    }

    private function getCommandRecord(ActiveQuery $query): ?CommandRecord
    {
        return CommandRecord::findBySql($query->limit(1)->createCommand()->getRawSql())->one();
    }

    private function getLockedCommand(ActiveQuery $query): ?Command
    {
        $record = CommandRecord::findBySql($query->limit(1)->createCommand()->getRawSql() . ' FOR UPDATE')->one();

        if (null === $record) {
            return null;
        }

        /** @var Command $entity */
        $entity = $this->unitOfWork->getForUpdate($record, $this->mapper);

        return $entity;
    }
}
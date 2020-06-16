<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Laminas\Hydrator\ReflectionHydrator;
use Ramsey\Uuid\Uuid;
use Sbooker\CommandBus\AttemptCounter;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Status;
use Sbooker\CommandBus\Workflow;

final class LaminasCommandHydrator implements CommandHydrator
{
    /** @var HydratorInterface  */
    private $hydrator;

    /** @var string */
    private $dateFormat;

    private const HYDRATED_PROPS = [
        'id',
        'name',
        'payload',
        'status',
        'changed_at',
        'count',
        'next_attempt_at',
        'result'
    ];

    public function __construct(?HydratorInterface $hydrator = null, string $dateFormat = 'Y-m-d H:i:s')
    {
        if (null === $hydrator) {
            $hydrator = new ReflectionHydrator();
            $hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
        }
        $this->hydrator = $hydrator;
        $this->dateFormat = $dateFormat;
    }

    public function extract(Command $command): array
    {
        $extracted = $this->hydrator->extract($command);
        $extracted['id'] = $extracted['id']->toString();
        $extractedWorkflow = $this->hydrator->extract($extracted['workflow']);
        unset($extracted['workflow']);
        $extractedWorkflow['status'] = $extractedWorkflow['status']->getRawValue();
        $extractedWorkflow['changed_at'] = $extractedWorkflow['changed_at']->format($this->dateFormat);
        $extractedAttemptCounter = $this->hydrator->extract($extracted['attempt_counter']);
        $extractedAttemptCounter['next_attempt_at'] = $extractedAttemptCounter['next_attempt_at']->format($this->dateFormat);
        unset($extracted['attempt_counter']);
        $extractedCommand = $this->hydrator->extract($extracted['normalized_command']);
        unset($extracted['normalized_command']);

        $extracted = array_merge($extracted, $extractedWorkflow, $extractedAttemptCounter, $extractedCommand);

        $extracted['payload'] = json_encode($extracted['payload']);
        $extracted['result'] = null === $extracted['result'] ? null : json_encode($extracted['result']);

        return $extracted;
    }

    public function hydrate(array $array): Command
    {
        if ([] !== array_diff(self::HYDRATED_PROPS, array_keys($array))) {
            throw new \RuntimeException();
        }

        $id = Uuid::fromString($array['id']);
        $workflow =
            $this->hydrator->hydrate(
                [
                    'status' => Status::getValueOf($array['status']),
                    'changed_at' => $this->hydrateDateTime($array['changed_at']),
                ],
                new Workflow()
            );
        $attemptCounter = $this->hydrator->hydrate(
            [
                'count' => $array['count'],
                'next_attempt_at' => $this->hydrateDateTime($array['next_attempt_at'])
            ],
            new AttemptCounter()
        );

        $reflectionCommand = new \ReflectionClass(Command::class);
        /** @var Command $command */
        $command = $this->hydrator->hydrate(
            [
                'id' => $id,
                'workflow' => $workflow,
                'attempt_counter' => $attemptCounter,
                'normalized_command' => new NormalizedCommand($array['name'], json_decode($array['payload'], true)),
                'result' => null === $array['result'] ? null : json_decode($array['result'], true)
            ],
            $reflectionCommand->newInstanceWithoutConstructor()
        );

        return $command;
    }

    private function hydrateDateTime(string $time): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat($this->dateFormat, $time);
    }
}
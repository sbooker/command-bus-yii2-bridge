<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\CommandHydrator;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\LaminasCommandHydrator;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\Status;

final class CommandHydratorTest extends TestCase
{
    public function test(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizedPayload = ["a" => "A", "b" => "B"];
        $commandName = 'command';
        $normalizedCommand = new NormalizedCommand($commandName, $normalizedPayload);
        $before = new \DateTimeImmutable();
        $command = new Command($commandId, $payload, $this->getNormalizer($normalizedCommand));
        $after = new \DateTimeImmutable();
        $dateFormat = 'Y-m-d\TH:i:s.uP';

        $hydrator = $this->getHydrator($dateFormat);
        $array = $hydrator->extract($command);

        $this->assertEquals($commandId->toString(), $array['id']);
        $this->assertEquals($commandName, $array['name']);
        $this->assertEquals(json_encode($normalizedPayload), $array['payload']);
        $this->assertEquals(Status::created()->getRawValue(), $array['status']);
        $this->assertEquals(0, $array['count']);
        $this->assertLessThanOrEqual($after, \DateTimeImmutable::createFromFormat($dateFormat, $array['changed_at']));
        $this->assertLessThanOrEqual($after, \DateTimeImmutable::createFromFormat($dateFormat, $array['next_attempt_at']));
        $this->assertGreaterThanOrEqual($before, \DateTimeImmutable::createFromFormat($dateFormat, $array['changed_at']));
        $this->assertGreaterThanOrEqual($before, \DateTimeImmutable::createFromFormat($dateFormat, $array['next_attempt_at']));
        $this->assertNull($array['result']);

        $hydratedCommand = $hydrator->hydrate($array);

        $this->assertEquals($command, $hydratedCommand);
    }

    private function getHydrator(string $dateFormat): CommandHydrator
    {
        return new LaminasCommandHydrator(null, $dateFormat);
    }

    private function getNormalizer(NormalizedCommand $normalizedCommand): Normalizer
    {
        $mock = $this->createMock(Normalizer::class);

        $mock->method('normalize')->willReturn($normalizedCommand);

        return $mock;
    }
}
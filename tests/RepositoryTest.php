<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\ActiveRecordReadRepository;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\ActiveRecordWriteRepository;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\CommandActiveRecordMapper;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\CommandHydrator;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\CommandRecord;
use Sbooker\CommandBus\Infrastructure\Persistence\Yii2\LaminasCommandHydrator;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\Yii2ActiveRecord\TransactionHandler;
use Sbooker\TransactionManager\Yii2ActiveRecord\UnitOfWork;
use Tests\Sbooker\CommandBus\Infrastructure\Persistence\StorageTest;
use Tests\Sbooker\CommandBus\Infrastructure\Persistence\TestDatabases;
use yii\console\ErrorHandler;
use yii\db\Connection;

final class RepositoryTest extends StorageTest
{
    /** @var CommandHydrator */
    private $hydrator;

    /** @var UnitOfWork */
    private $uow;

    /** @var CommandActiveRecordMapper */
    private $mapper;

    /** @var Connection | null */
    private $connection;

    public function dbs(): array
    {
        return [
            [ TestDatabases::mysql5() ],
        ];
    }

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__  . '/../vendor/yiisoft/yii2/Yii.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->hydrator = new LaminasCommandHydrator();
        $this->mapper = new CommandActiveRecordMapper($this->hydrator);
        $this->uow = new UnitOfWork();
    }

    protected function setUpDbDeps(TestDatabases $db): void
    {
        new TestApplication([
            'id' => 'Test app',
            'basePath' => dirname(__DIR__),
            'components' => [
                'errorHandler' => [ 'class' => ErrorHandler::class ],
                'db' => $this->getDbConfig($db),
            ],
        ]);

        $this->connection = \Yii::$app->getDb();

        $this->dropTable();
        $this->connection->createCommand($this->getMigrationSql($db))->execute();
        $this->connection->getTableSchema(CommandRecord::tableName(), true);
        $this->transactionManager = new TransactionManager(new TransactionHandler($this->connection, $this->uow));

    }

    protected function tearDownDbDeps(): void
    {
        $this->dropTable();
        $this->connection = null;
    }

    protected function getReadStorage(): ReadStorage
    {
        return new ActiveRecordReadRepository($this->getHydrator());
    }

    protected function getWriteStorage(): WriteStorage
    {
        return new ActiveRecordWriteRepository($this->uow, new CommandActiveRecordMapper($this->hydrator));
    }

    private function getHydrator(): CommandHydrator
    {
        return $this->hydrator;
    }

    private function getDbConfig(TestDatabases $db): array
    {
        return array_merge($this->getDefaultDbConfig(), $this->getCustomDbConfig($db));
    }

    private function getMigrationSql(TestDatabases $db): string
    {
        return file_get_contents(__DIR__  . "/../vendor/sbooker/command-bus/tests/Infrastructure/Persistence/resources/{$db->getRawValue()}.sql");
    }

    private function getCustomDbConfig(TestDatabases $db): array
    {
        switch ($db) {
            case TestDatabases::postgresql12():
                return ['dsn' => 'pgsql:host=pgsql12;port=5432;dbname=test',];
            case TestDatabases::mysql5():
                return ['dsn' => 'mysql:host=mysql5;port=3306;dbname=test',];
            case TestDatabases::mysql8():
                return ['dsn' => 'mysql:host=mysql8;port=3306;dbname=test',];
            default:
                throw new \RuntimeException();
        }
    }

    private function getDefaultDbConfig(): array
    {
        return [
            'class' => Connection::class,
            'username' => 'user',
            'password' => 'password',
            'charset' =>  'utf8',
        ];
    }

    private function dropTable(): void
    {
        $connection = $this->connection;
        if (null !== $connection->getTableSchema(CommandRecord::tableName(), true)) {
            $connection->createCommand()->dropTable(CommandRecord::tableName())->execute();
        }
        $connection->getTableSchema(CommandRecord::tableName(), true);
    }
}
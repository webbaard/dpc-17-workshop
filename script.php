<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\SchemaException;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter;
use Prooph\EventStore\Adapter\Doctrine\Schema\EventStoreSchema;
use Prooph\EventStore\Adapter\PayloadSerializer\JsonPayloadSerializer;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\EventStoreBusBridge\TransactionManager;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\Bernard\BernardMessageProducer;
use Prooph\ServiceBus\Message\Bernard\BernardSerializer;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;

require_once __DIR__ . '/vendor/autoload.php';

// Connection and schema setup
$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    'dbname' => 'workshop',
    'user' => 'root',
    'password' => 'root',
    'host' => '127.0.0.1',
    'port' => 32769,
    'driver' => 'pdo_mysql',
);

$connection = DriverManager::getConnection($connectionParams, $config);

$schema = $connection->getSchemaManager()->createSchema();

try {
    EventStoreSchema::createSingleStream($schema, 'event_stream', true);

    foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
        $connection->exec($sql);
    }
} catch (SchemaException $e) {

}

// Event bus and event store setup
$eventBus = new EventBus();
$eventStore = new EventStore(
    new DoctrineEventStoreAdapter(
        $connection,
        new FQCNMessageFactory(),
        new NoOpMessageConverter(),
        new JsonPayloadSerializer()
    ),
    new ProophActionEventEmitter()
);

$eventRouter = new EventRouter();

$eventRouter->attach($eventBus->getActionEventEmitter());

(new EventPublisher($eventBus))->setUp($eventStore);

// Repository
$prepaidCardRepository = new PrepaidCardRepository(
    new AggregateRepository(
        $eventStore,
        AggregateType::fromAggregateRootClass(PrepaidCard::class),
        new AggregateTranslator()
    )
);

// Command bus setup
$commandBus = new CommandBus();
$transactionManager = new TransactionManager();

$transactionManager->setUp($eventStore);
$commandBus->utilize($transactionManager);

$commandRouter = new CommandRouter();

$commandRouter->attach($commandBus->getActionEventEmitter());

// Events and commands routing setup

// Command dispatching

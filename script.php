<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\SchemaException;
use Money\Currency;
use Money\Money;
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
use Rhumsaa\Uuid\Uuid;
use Workshop\Domain\Command\LoadMoney;
use Workshop\Domain\Command\RegisterPrepaidCard;
use Workshop\Domain\Events\MoneyLoaded;
use Workshop\Domain\PrepaidCard;
use Workshop\Infrastructure\ESPrepaidCardRepository;

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
$prepaidCardRepository = new ESPrepaidCardRepository(
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
$commandRouter
    ->route(RegisterPrepaidCard::class)
    ->to(function (RegisterPrepaidCard $command) use ($prepaidCardRepository) {
        $card = PrepaidCard::register($command->number(), $command->currency());

        $prepaidCardRepository->save($card);
    });

$commandRouter
    ->route(LoadMoney::class)
    ->to(function (LoadMoney $command) use ($prepaidCardRepository) {
        $card = $prepaidCardRepository->get(Uuid::fromString($command->cardId()));

        $card->load(new Money($command->amount(), new Currency($command->currency())));

        $prepaidCardRepository->save($card);
    });

$eventRouter
    ->route(MoneyLoaded::class)
    ->to(function (MoneyLoaded $event) {
       echo sprintf('Money loaded: ' . $event->amount() . ' ' . $event->currency());
       echo PHP_EOL;
    });

// Command dispatching
$cardId = '7217974b-4455-4d98-8ce7-a19376f6f362';

$command = RegisterPrepaidCard::from('12345-12345-12345', 'EUR');

$commandBus->dispatch($command);
$commandBus->dispatch(LoadMoney::from($cardId, 10, 'EUR'));
$commandBus->dispatch(LoadMoney::from($cardId, 20, 'EUR'));
$commandBus->dispatch(LoadMoney::from($cardId, 20, 'EUR'));

// Iteration about the history of all events
foreach ($eventStore->load(new StreamName('event_stream'))->streamEvents() as $event) {
    $eventBus->dispatch($event);
}

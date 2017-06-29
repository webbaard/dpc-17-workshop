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
use Workshop\Domain\Command\BlockCard;
use Workshop\Domain\Command\ChangeLimit;
use Workshop\Domain\Command\LoadMoney;
use Workshop\Domain\Command\PayMoney;
use Workshop\Domain\Command\RegisterPrepaidCard;
use Workshop\Domain\Command\UnblockCard;
use Workshop\Domain\Events\LimitChanged;
use Workshop\Domain\Events\MoneyLoaded;
use Workshop\Domain\Events\MoneyPayed;
use Workshop\Domain\Exceptions\CardIsBlocked;
use Workshop\Domain\Exceptions\LowBalance;
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
    'port' => 3306,
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
        try{
            $card->load(new Money($command->amount(), new Currency($command->currency())));
        } catch (Exception $exception) {
            echo sprintf('wrong');
            echo PHP_EOL;
        }

        $prepaidCardRepository->save($card);
    });

$commandRouter
    ->route(PayMoney::class)
    ->to(function (PayMoney $command) use ($prepaidCardRepository) {
        $card = $prepaidCardRepository->get(Uuid::fromString($command->cardId()));
        try{
            $card->pay(new Money($command->amount(), new Currency($command->currency())));
        } catch (Exception $exception) {
            echo sprintf('wrong');
            echo PHP_EOL;
        }
        $prepaidCardRepository->save($card);
    });

$commandRouter
    ->route(BlockCard::class)
    ->to(function (BlockCard $command) use ($prepaidCardRepository) {
        $card = $prepaidCardRepository->get(Uuid::fromString($command->cardId()));
        try{
            $card->block();
        } catch (Exception $exception) {
            echo sprintf('wrong');
            echo PHP_EOL;
        }
        $prepaidCardRepository->save($card);
    });

$commandRouter
    ->route(UnblockCard::class)
    ->to(function (UnblockCard $command) use ($prepaidCardRepository) {
        $card = $prepaidCardRepository->get(Uuid::fromString($command->cardId()));

        $card->unblock();

        $prepaidCardRepository->save($card);
    });

$commandRouter
    ->route(ChangeLimit::class)
    ->to(function (ChangeLimit $command) use ($prepaidCardRepository) {
        $card = $prepaidCardRepository->get(Uuid::fromString($command->cardId()));
        try {
            $card->changeDailyLimit(new Money($command->limit(), new Currency('EUR')));
        } catch (Exception $exception) {
            echo sprintf('wrong');
            echo PHP_EOL;
        }
        $prepaidCardRepository->save($card);
    });

$eventRouter
    ->route(MoneyLoaded::class)
    ->to(function (MoneyLoaded $event) {
       echo sprintf('Money loaded: ' . $event->amount() . ' ' . $event->currency());
       echo PHP_EOL;
    });

$eventRouter
    ->route(MoneyPayed::class)
    ->to(function (MoneyPayed $event) {
       echo sprintf('Money payed: ' . $event->amount() . ' ' . $event->currency());
       echo PHP_EOL;
    });


// Command dispatching
$cardId = '7217974b-4455-4d98-8ce7-a19376f6f362';

//$command = RegisterPrepaidCard::from('12345-12345-12345', 'EUR');

//$commandBus->dispatch($command);

try {
    $commandBus->dispatch(LoadMoney::from($cardId, 100, 'EUR'));
    $commandBus->dispatch(BlockCard::from($cardId));
    $commandBus->dispatch(PayMoney::from($cardId, 20, 'EUR'));
    $commandBus->dispatch(UnblockCard::from($cardId));
    $commandBus->dispatch(PayMoney::from($cardId, 200, 'EUR'));
    $commandBus->dispatch(ChangeLimit::from($cardId, 200));
} catch (CardIsBlocked $exception) {
    echo sprintf('Card Blocked');
    echo PHP_EOL;
} catch (LowBalance $exception) {
    echo sprintf('Low Amount');
    echo PHP_EOL;
}

//// Iteration about the history of all events
//foreach ($eventStore->load(new StreamName('event_stream'))->streamEvents() as $event) {
//    $eventBus->dispatch($event);
//}

var_dump($prepaidCardRepository->get(Uuid::fromString($cardId)));

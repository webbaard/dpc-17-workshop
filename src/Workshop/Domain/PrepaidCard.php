<?php

namespace Workshop\Domain;

use Money\Currency;
use Money\Money;
use Prooph\EventSourcing\AggregateRoot;
use Rhumsaa\Uuid\Uuid;
use Workshop\Domain\Events\MoneyLoaded;
use Workshop\Domain\Events\PrepaidCardRegistered;

class PrepaidCard extends AggregateRoot
{
    /**
     * @var Uuid
     */
    private $id;

    /**
     * @var string
     */
    private $number;

    /**
     * @var Money
     */
    private $balance;

    public static function register(string $number, string $currency)
    {
        $self = new self();

        $self->recordThat(
            PrepaidCardRegistered::from(
                Uuid::fromString('7217974b-4455-4d98-8ce7-a19376f6f362'),
                $number,
                $currency
            )
        );

        return $self;
    }

    public function load(Money $moneyToLoad)
    {
        $this->recordThat(
            MoneyLoaded::from(
                $this->id,
                $moneyToLoad->getAmount(),
                $moneyToLoad->getCurrency()
            )
        );
    }

    public function pay(/* ... */)
    {
        // @todo Record event...
    }

    public function block(/* ... */)
    {
        // @todo Record event...
    }

    public function unblock(/* ... */)
    {
        // @todo Record event...
    }

    public function changeDailyLimit(/* ... */)
    {
        // @todo Record event...
    }

    public function unregister(/* ... */)
    {
        // @todo Record event...
    }

    protected function aggregateId()
    {
        return (string)$this->id;
    }

    public function id()
    {
        return $this->aggregateId();
    }

    public function balance()
    {
        return $this->balance;
    }

    protected function whenPrepaidCardRegistered(PrepaidCardRegistered $event)
    {
        $this->id = $event->aggregateId();
        $this->number = $event->number();
        $this->balance = new Money(0, new Currency($event->currency()));
    }

    protected function whenMoneyLoaded(MoneyLoaded $event)
    {
        $this->balance = $this->balance->add(new Money(
            $event->amount(),
            new Currency($event->currency())
        ));
    }
}


//
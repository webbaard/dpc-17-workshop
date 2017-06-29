<?php

namespace Workshop\Domain;

use Money\Currency;
use Money\Money;
use Prooph\EventSourcing\AggregateRoot;
use Rhumsaa\Uuid\Uuid;
use Workshop\Domain\Events\CardBlocked;
use Workshop\Domain\Events\CardUnblocked;
use Workshop\Domain\Events\LimitChanged;
use Workshop\Domain\Events\MoneyLoaded;
use Workshop\Domain\Events\MoneyPayed;
use Workshop\Domain\Events\PrepaidCardRegistered;
use Workshop\Domain\Exceptions\CardIsBlocked;
use Workshop\Domain\Exceptions\LowBalance;

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

    /**
     * @var bool
     */
    private $blocked = false;

    /**
     * @var int
     */
    private $limit = 1000;

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
        if ($this->blocked === true) {
            throw new CardIsBlocked();
        }
        $this->recordThat(
            MoneyLoaded::from(
                $this->id,
                $moneyToLoad->getAmount(),
                $moneyToLoad->getCurrency()
            )
        );
    }

    public function pay(Money $moneyToPay)
    {
        if ($this->blocked === true) {
            throw new CardIsBlocked();
        }
        if ($this->balance->getAmount() < $moneyToPay->getAmount()) {
            throw new LowBalance();
        }
        $this->recordThat(
            MoneyPayed::from(
                $this->id,
                $moneyToPay->getAmount(),
                $moneyToPay->getCurrency()
            )
        );
    }

    public function block()
    {
        if ($this->blocked === true) {
            throw new CardIsBlocked();
        }

        $this->recordThat(
            CardBlocked::from(
                $this->id
            )
        );
    }

    public function unblock()
    {
        $this->recordThat(
            CardUnblocked::from(
                $this->id
            )
        );
    }

    public function changeDailyLimit(Money $limit)
    {
        if ($this->blocked === true) {
            throw new CardIsBlocked();
        }
        $this->recordThat(
            LimitChanged::from(
                $this->id,
                $limit->getAmount()
            )
        );
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

    protected function whenMoneyPayed(MoneyPayed $event)
    {
        $this->balance = $this->balance->subtract(new Money(
            $event->amount(),
            new Currency($event->currency())
        ));
    }

    protected function whenCardBlocked(CardBlocked $event)
    {
        $this->blocked = true;
    }

    protected function whenCardUnblocked(CardUnblocked $event)
    {
        $this->blocked = false;
    }

    protected function whenLimitChanged(LimitChanged $event)
    {
        $this->limit = $event->limit();
    }
}

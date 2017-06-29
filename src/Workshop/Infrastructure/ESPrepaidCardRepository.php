<?php

namespace Workshop\Infrastructure;

use Prooph\EventStore\Aggregate\AggregateRepository;
use Rhumsaa\Uuid\Uuid;
use Workshop\Domain\PrepaidCard;
use Workshop\Domain\PrepaidCardRepository;

class ESPrepaidCardRepository implements PrepaidCardRepository
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;

    /**
     * ESPrepaidCardRepository constructor.
     * @param AggregateRepository $aggregateRepository
     */
    public function __construct(AggregateRepository $aggregateRepository)
    {
        $this->aggregateRepository = $aggregateRepository;
    }

    public function get(Uuid $id): PrepaidCard
    {
        return $this->aggregateRepository->getAggregateRoot($id->toString());
    }

    public function save(PrepaidCard $prepaidCard)
    {
        $this->aggregateRepository->addAggregateRoot($prepaidCard);
    }
}
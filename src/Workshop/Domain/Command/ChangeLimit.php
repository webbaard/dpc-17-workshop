<?php

namespace Workshop\Domain\Command;

use Prooph\Common\Messaging\Command;

class ChangeLimit extends Command
{
    /**
     * @var string
     */
    private $cardId;

    /**
     * @var int
     */
    private $limit;

    private function __construct(string $cardId, int $limit)
    {
        $this->init();

        $this->cardId = $cardId;
        $this->limit = $limit;
    }

    public static function from(string $cardId, int $limit)
    {
        return new self($cardId, $limit);
    }

    public function cardId()
    {
        return $this->cardId;
    }

    public function limit()
    {
        return $this->limit;
    }

    public function payload()
    {
        return [
            'cardId' => $this->cardId,
            'limit' => $this->limit,
        ];
    }

    protected function setPayload(array $payload)
    {
        $this->cardId = $payload['cardId'];
        $this->limit = $payload['limit'];
    }
}
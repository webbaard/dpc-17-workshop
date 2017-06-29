<?php

namespace Workshop\Domain\Command;

use Prooph\Common\Messaging\Command;

class RegisterPrepaidCard extends Command
{
    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $currency;

    private function __construct(string $number, string $currency)
    {
        $this->init();

        $this->number =$number;
        $this->currency = $currency;
    }

    public static function from(string $number, string $currency)
    {
        return new self($number, $currency);
    }

    public function number()
    {
        return $this->number;
    }

    public function currency()
    {
        return $this->currency;
    }

    public function payload()
    {
        return [
            'number' => $this->number,
            'currency' => $this->currency,
        ];
    }

    protected function setPayload(array $payload)
    {
        $this->number = $payload['number'];
        $this->currency = $payload['currency'];
    }
}
<?php
declare(strict_types=1);

namespace Gokure\Utils\Amqp\Delay;

use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Amqp\Producer;

class DelayProducer extends Producer
{
    /**
     * The number of seconds before the job should be made available.
     *
     * @var int
     */
    public $delay;

    /**
     * Set the desired delay for the job.
     *
     * @param  int  $delay
     * @return $this
     */
    public function delay(int $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    public function delayProduce(ProducerMessageInterface $producerMessage, $delay = 0, int $timeout = 120, bool $confirm = false): bool
    {
        return $this->delay($delay)->produce($producerMessage, $confirm, $timeout);
    }

    public function produce(ProducerMessageInterface $producerMessage, bool $confirm = false, int $timeout = null): bool
    {
        if ($this->delay !== null && method_exists($producerMessage, 'delay')) {
            $producerMessage->delay($this->delay);
        }

        if ($timeout === null && property_exists($producerMessage, 'timeout')) {
            $timeout = $producerMessage->timeout;
        }

        return parent::produce($producerMessage, $confirm, $timeout ?? 5);
    }
}

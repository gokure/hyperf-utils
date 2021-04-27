<?php
declare(strict_types=1);

namespace Gokure\Utils\Amqp\Delay;

use Hyperf\Amqp\Message\ProducerMessage;
use PhpAmqpLib\Wire\AMQPTable;

class DelayProducerMessage extends ProducerMessage
{
    protected $type = 'x-delayed-message';

    /**
     * The number of seconds before the job should be made available.
     *
     * @var int
     */
    protected $delay;

    /**
     * The number of times to attempt a job.
     *
     * @return int|null
     */
    protected $tries;

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

    public function tries(int $tries)
    {
        $this->tries = $tries;
        return $this;
    }

    public function getProperties(): array
    {
        $properties = parent::getProperties();
        $table = ['x-delay' => $this->delay * 1000];
        if ($this->tries !== null) {
            $table['x-tries-limit'] = $this->tries;
        }
        $properties['application_headers'] = new AMQPTable($table);
        return $properties;
    }
}

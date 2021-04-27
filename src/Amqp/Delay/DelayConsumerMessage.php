<?php
declare(strict_types=1);

namespace Gokure\Utils\Amqp\Delay;

use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use PhpAmqpLib\Wire\AMQPTable;

class DelayConsumerMessage extends ConsumerMessage
{
    protected $type = 'x-delayed-message';

    public function getExchangeBuilder(): ExchangeBuilder
    {
        return parent::getExchangeBuilder()->setArguments(new AMQPTable(['x-delayed-type' => Type::DIRECT]));
    }
}

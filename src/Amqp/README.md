# Hyperf 延时重试消息队列

基于AMQP的消息队列，支持延时重试机制。

## 依赖

- Hyperf 2.0
- RabbitMQ 3.8.x
- [rabbitmq_delayed_message_exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange)

```sh
rabbitmq-plugins enable rabbitmq_delayed_message_exchange
```

## 示例

```php
/**
 * @Producer(exchange="demo", routingKey="Demo")
 */
class DemoDelayProducerMessage extends DelayProducerMessage
{
    public function __construct($payload)
    {
        $this->payload = $payload;
    }
}
```

```php
/**
 * @Consumer(exchange="demo", routingKey="Demo")
 */
class DemoDelayConsumer extends DelayConsumerMessage
{
    use Runnable;

    public function __construct()
    {
        $this->tries(3);
    }

    public function retriesIn(): int
    {
        return 2 ** $this->attempts();
    }

    /**
     * @param $data
     * @param AMQPMessage $message
     */
    public function run($data, AMQPMessage $message)
    {
        $user = Account::findOrFail($data['id']);
        // 业务逻辑处理
        ...
    }
}
```

```php
$message = new DemoDelayProducerMessage(['id' => 1]);
make(DelayProducer::class)->delay(10)->produce($message);
...
```

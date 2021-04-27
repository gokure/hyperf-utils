<?php

namespace Gokure\Utils\Amqp\Concerns;

use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

trait Runnable
{
    /**
     * @var AMQPMessage
     */
    protected $message;

    /**
     * @var int
     */
    protected $tries;

    public function tries(int $tries)
    {
        $this->tries = $tries;
        return $this;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return ($this->getMessageApplicationHeaders()->getNativeData()['x-retry'] ?? 0) + 1;
    }

    /**
     * Re-tries after seconds when the job failed.
     *
     * @return int
     */
    public function retriesIn()
    {
        return 1;
    }

    public function consumeMessage($data, AMQPMessage $message): string
    {
        $this->message = $message;
        $logger = make(\Hyperf\Logger\LoggerFactory::class)->get('amqp.runnable');

        try {
            $logger->info(sprintf(
                '[Runnable] To run the consume message: %s(%s)',
                $message->getConsumerTag(),
                $message->getBody(),
            ));
            $this->run($data, $message);
        } catch (Throwable $ex) {
            $headers = $this->getMessageApplicationHeaders();
            $maxTries = $headers->getNativeData()['x-tries-limit'] ?? $this->tries ?? 0;
            $attempts = $this->attempts();

            try {
                $this->handleFailedJob($ex);
            } catch (Throwable $_) {
                // do nothing.
            }

            if ($maxTries && $attempts >= $maxTries) {
                // Maximum tries.
                $logger->error(sprintf(
                    "[Runnable] The consume message has been attempted too many times: %s(%s) - %d - %s\n%s",
                    $message->getConsumerTag(),
                    $message->getBody(),
                    $attempts,
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                ));

                try {
                    $this->handleFinalFailedJob($ex);
                } catch (Throwable $_) {
                    // do nothing.
                }
            } else {
                // Re-trying.
                $delay = $this->retriesIn();
                $headers->set('x-delay', $delay * 1000);
                $headers->set('x-retry', $attempts);
                $message->set('application_headers', $headers);

                if ($channel = $message->getChannel()) {
                    $logger->info(sprintf('[Runnable] The consume message %s(%s) will be released after %s second(s).',
                        $message->getConsumerTag(),
                        $message->getBody(),
                        $delay
                    ));
                    $channel->basic_publish($message, $message->getExchange(), $message->getRoutingKey());
                }
            }
        }

        return Result::ACK;
    }

    protected function getMessageApplicationHeaders(): AMQPTable
    {
        return $this->message->has('application_headers') ? $this->message->get('application_headers') : new AMQPTable();
    }

    abstract public function run($data, AMQPMessage $message);

    /**
     * 处理每次任务失败回调，注意在该方法中抛异常将会被忽略
     *
     * @param Throwable $e
     */
    protected function handleFailedJob(Throwable $e)
    {
        //
    }

    /**
     * 处理任务失败后最大尝试次数尝试回调，注意在该方法中抛异常将会被忽略
     *
     * @param Throwable $e
     */
    protected function handleFinalFailedJob(Throwable $e)
    {
        //
    }
}

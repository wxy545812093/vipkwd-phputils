<?php
/**
 * @name RabbitMQ
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Vipkwd\Utils\Dev;

trait _trait__MQ{
    /**
     * @var
     */
    private static $instance;

    /**
     * @var int
     */
    private static $ackNumber = 0;

    /**
     * @var array
     */
    private static $config = [
        "host" => "127.0.0.1",
        "port" => "5672",
        "user" => "guest",
        "password" => "guest",
        "vhost" => "/",
        "channel_max_num" => 10,
        "connection_timeout" => 3.0,
        "read_write_timeout" => 130.0,
        "insist" => false,
        "login_method" => 'AMQPLAIN',
        "login_response" => null,
        "locale" => "en_US",
        "context" => null,
        "keepalive" => false,
        "heartbeat" => 60,
        "channel_rpc_timeout" => 0.0,
        "ssl_protocol" => null
    ];

    private static $connection;

    private static $channelPoolList = [];

    // 最大重试次数
    private static $maxRetryNumber = 5;

    /**
     * 打印实例化支持的参数列表
     *
     * @return void
     */
    static function showConfParams(){
        Dev::dump(self::$config);
    }

    /**
     * 实例化MQ
     * 
     * @param array $config
     * @return RabbitMq
     */
    public static function getInstance($config = [])
    {
        foreach (self::$config as $key) {
            if (isset($config[$key])) {
                self::$config[$key] = $config[$key];
            }
        }

        if (isset(self::$instance)) {
            return self::$instance;
        } else {
            return self::$instance = new RabbitMq();
        }
    }

    public function __destruct()
    {
        if (self::$connection instanceof AMQPStreamConnection) {
            self::$connection->close();
        }
        /**
         * @var $channel AMQPChannel
         */
        if (count(self::$channelPoolList) > 0) {
            foreach (self::$channelPoolList as $channel) {
                $channel->close();
            }
        }
    }

    /**
     * @return mixed
     * @todo一个进程之内获取的要是同一个channel
     */
    private function getChannel()
    {
        if (!isset(self::$connection)) {
            self::$connection = new AMQPStreamConnection(
                self::$config["host"],
                self::$config["port"],
                self::$config["user"],
                self::$config["password"],
                self::$config["vhost"],
                self::$config["insist"],
                self::$config["login_method"],
                self::$config["login_response"],
                self::$config["locale"],
                self::$config["connection_timeout"],
                self::$config["read_write_timeout"],
                self::$config["context"],
                self::$config["keepalive"],
                self::$config["heartbeat"],
                self::$config["channel_rpc_timeout"],
                self::$config["ssl_protocol"]
            );
        }

        if (count(self::$channelPoolList) < self::$config['channel_max_num']) {
            $channel = self::$connection->channel();
            $channel->confirm_select();
            $channel->set_nack_handler(function ($a) {
                
            });
            return self::$channelPoolList[] = $channel;
        } else {
            $index = array_rand(self::$channelPoolList, 1);
            return self::$channelPoolList[$index];
        }
    }

    /**
     * @param string $msg
     * @param string $delayQueueName
     * @param string $deadExchange
     * @param string $deadRoutingKey
     * @param int $delaySec
     */
    private function pushToDelayQueue(
        $msg="",
        $delayQueueName="",
        $deadExchange="",
        $deadRoutingKey="",
        $delaySec = 0
        )
    {
        $delayTime = $delaySec * 1000;
        if ($delayTime > PHP_INT_MAX) {
            $maxSec = (int)(PHP_INT_MAX / 1000);
            throw new EasyRabbitException(sprintf("delay seconds over the max : %s", $maxSec));
        }

        /**
         * @var $channel AMQPChannel
         */
        $channel = $this->getChannel();
        $amqpTable = new AMQPTable();
        $amqpTable->set('x-dead-letter-exchange', $deadExchange);
        $amqpTable->set('x-dead-letter-routing-key', $deadRoutingKey);
        $channel->queue_declare(
            $delayQueueName,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $auto_delete = true,
            $nowait = false,
            $arguments = $amqpTable,
            $ticket = null
        );
        $msgObj = new AMQPMessage();
        $msgObj->setBody($msg);
        $msgObj->set("expiration", $delaySec * 1000);
        $channel->basic_publish(
            $msgObj,
            "",
            $delayQueueName,
            $mandatory = false,
            $immediate = false,
            $ticket = null
        );

    }

    /**
     * 消费失败重试
     * 
     * 消费支持自动重试，最多尝试重试5次(具体次数由 self::$maxRetryNumber控制 默认5次)，每次消费失败后该消息将会被重新投入到消费队列中。
     * 重新的时间将会随着失败的次数增多逐渐推移,本客户端支持的重试延迟推移策略为 2^(n-1)次幂秒： 
     *     失败1次（1秒钟后会再被投递）, 
     *     失败2次（2秒钟后会再被投递）, 
     *     失败3次（4秒钟后会再被投递）, 
     *     失败4次（8秒钟后会再被投递）, 
     *     失败5次（16秒钟后会再被投递）    
     * 
     * @param object|null $msgObj
     * @param string $retryQueueName
     * @param string $deadRoutingKey
     * @param string $failedQueueName
     * @return bool
     */
    private function pushToRetryQueue(
        $msgObj = null,
        $retryQueueName="",
        $deadRoutingKey="",
        $failedQueueName=""
        )
    {
        $retryNumber = 1;
        if ($msgObj->has('application_headers')) {
            /* @var AMQPTable $properties */
            $properties = $msgObj->get('application_headers');
            $data = $properties->getNativeData();
            $retryNumber = isset($data['retry_nums']) && is_numeric($data['retry_nums']) ? $data['retry_nums'] : 1;
        }

        /**
         * @var $channel AMQPChannel
         */
        $channel = $this->getChannel();
        if ($retryNumber > self::$maxRetryNumber) {
            $channel->queue_declare($failedQueueName,
                $passive = false,
                $durable = true,
                $exclusive = false,
                $auto_delete = false
            );
            $channel->basic_publish(
                $msgObj,
                $exchange="",
                $failedQueueName
            );
        } else {
            $amqpTable = new AMQPTable();
            $amqpTable->set('x-dead-letter-exchange', "");
            $amqpTable->set('x-dead-letter-routing-key', $deadRoutingKey);
            $channel->queue_declare(
                $retryQueueName,
                $passive = false,
                $durable = true,
                $exclusive = false,
                $auto_delete = true,
                $nowait = false,
                $arguments = $amqpTable,
                $ticket = null
            );
            $headers = new AMQPTable(['retry_nums' => $retryNumber + 1]);
            $msgObj->set('application_headers', $headers);
            $msgObj->set('expiration', pow(2, $retryNumber - 1) * 1000);
            $channel->basic_publish(
                $msgObj,
                "",
                $retryQueueName,
                $mandatory = false,
                $immediate = false,
                $ticket = null
            );
            return true;
        }
    }

    /**
     * 推送消息到Fanout交换机(支持延迟)
     * 
     * @param string $msg 消息体内容
     * @param string $exchange 交换机名称
     * @param int $delaySec  延迟秒数 0表无延迟（即时推送）
     * @return bool
     */
    public function pushToFanout(string $msg, string $exchange, int $delaySec = 0)
    {
        $channel = $this->getChannel();
        $channel->exchange_declare(
            $exchange,
            AMQPExchangeType::FANOUT,
            $passive = false,
            $durable = true,
            $auto_delete = false,
            $internal = false,
            $nowait = false,
            $arguments = array(),
            $ticket = null
        );
        $msgObj = new AMQPMessage();
        $msgObj->setBody($msg);
        if ($delaySec > 0) {
            $delayQueueName = $exchange . "@" . "delay";
            $this->pushToDelayQueue($msg,
                $delayQueueName,
                $exchange,
                "",
                $delaySec);
        } else {
            $channel->basic_publish(
                $msgObj,
                $exchange,
                "",
                $mandatory = false,
                $immediate = false,
                $ticket = null
            );
        }

        self::$ackNumber++;
        if (self::$ackNumber > 100) {
            $channel->wait_for_pending_acks();
            self::$ackNumber = 0;
        }

        return true;
    }


    /**
     * 推送消息到Direct交换机(支持延迟)
     * 
     * @param string $msg 消息体内容
     * @param string $exchange 交换机名称
     * @param string $routingKey 消息的routingKey，consume(get) 方法到bingdingKey 要和routingKey保持一致
     * @param int $delaySec 延迟秒数 0表无延迟（即时推送）
     * @return bool
     */
    public function pushToDirect(string $msg, string $exchange, string $routingKey, int $delaySec = 0)
    {
        $channel = $this->getChannel();
        $msgObj = new AMQPMessage();
        $msgObj->setBody($msg);
        if (!empty($exchange)) {
            $channel->exchange_declare(
                $exchange,
                AMQPExchangeType::DIRECT,
                $passive = false,
                $durable = true,
                $auto_delete = false,
                $internal = false,
                $nowait = false,
                $arguments = array(),
                $ticket = null
            );
        }

        if ($delaySec > 0) {
            $delayQueueName = $exchange . "@" . "delay";
            $this->pushToDelayQueue($msg,
                $delayQueueName,
                $exchange,
                $routingKey,
                $delaySec);
        } else {
            $channel->basic_publish(
                $msgObj,
                $exchange,
                $routingKey,
                $mandatory = false,
                $immediate = false,
                $ticket = null
            );
        }

        self::$ackNumber++;
        if (self::$ackNumber > 100) {
            $channel->wait_for_pending_acks();
            self::$ackNumber = 0;
        }

        return true;
    }

    /**
     * 推送消息到Topic交换机(支持延迟)
     * 
     * @param string $msg 消息体内容
     * @param string $routingKey routingKey 要同consum(get)方法的bindingKey相匹配
	 *                           bindingKey支持两种特殊的字符"*"、“#”，用作模糊匹配, 其中"*"用于匹配一个单词、“#”用于匹配多个单词(也可以是0个)
	 *                           无论是bindingKey还是routingKey, 被"."分隔开的每一段独立的字符串就是一个单词, easy.topic.queue, 包含三个单词easy、topic、queue
	 * 
     * @param string $exchange 交换机名称
     * @param int $delaySec   延迟秒数 0表无延迟（即时推送）
     * @return bool
     */
    public function pushToTopic(string $msg,  string $exchange, string $routingKey, int $delaySec = 0)
    {
        $channel = $this->getChannel();
        $channel->exchange_declare(
            $exchange,
            AMQPExchangeType::TOPIC,
            $passive = false,
            $durable = true,
            $auto_delete = false,
            $internal = false,
            $nowait = false,
            $arguments = array(),
            $ticket = null
        );
        $msgObj = new AMQPMessage();
        $msgObj->setBody($msg);
        if ($delaySec > 0) {
            $delayQueueName = $exchange . "@" . "delay";
            $this->pushToDelayQueue($msg,
                $delayQueueName,
                $exchange,
                $routingKey,
                $delaySec);
        } else {
            $channel->basic_publish(
                $msgObj,
                $exchange,
                $routingKey,
                $mandatory = false,
                $immediate = false,
                $ticket = null
            );
        }

        self::$ackNumber++;
        if (self::$ackNumber > 100) {
            $channel->wait_for_pending_acks();
            self::$ackNumber = 0;
        }

        return true;
    }

    /**
     * 拉取模式下的可靠消费
     * 
     * @param string $queue 订阅的队列名称
     * @param string $exchange 交换机名称
     * @param string $bindingKey bindingkey，如果是直链交换机需要同routingKey保持一致
     * @param callable|null $callback  如果返回结果不绝对等于(===)布尔true,那么将触发消息重试机制
     * @param string $failedQueue 最大次数重试消费失败后，失败消息将会投递到的队列名称
     * @return bool
     */
    public function get(string $queue, string $exchange, string $bindingKey, $callback = null, string $failedQueue = 'v@failed')
    {
        $ret = true;
        $channel = $this->getChannel();

        $channel->queue_declare(
            $queue,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $auto_delete = false,
            $nowait = false,
            $arguments = array(),
            $ticket = null
        );

        if (!empty($exchange)) {
            $channel->queue_bind(
                $queue,
                $exchange,
                $bindingKey,
                $nowait = false,
                $arguments = array(),
                $ticket = null
            );
        }

        $message = $channel->basic_get(
            $queue,
            $no_ack = false,
            $ticket = null
        );
        if (isset($message)) {
            $ret = is_callable($callback) ? $callback($message) : true;
            $channel->basic_ack($message->delivery_info['delivery_tag']);
            if ($ret !== true) {
                $retryQueue = $queue . '@retry';
                $this->pushToRetryQueue(
                    $message,
                    $retryQueue,
                    $queue,
                    $failedQueue
                );
            }
        }
        return $ret;
    }

    /**
     * 订阅模式下的可靠消费
     * 
     * @param string $queue 订阅的队列名称
     * @param string $tag 消费标记
     * @param string $exchange 交换机名称
     * @param string $bindingKey 如果是直链交换机需要同routingKey保持一致
     * @param callable|null $callback  如果返回结果不绝对等于(===)布尔true,那么将触发消息重试机制
     * @param string $failedQueue 最大次数重试消费失败后，失败消息将会投递到的队列名称
     * @throws \ErrorException
     */
    public function consume(string $queue,string $tag, string $exchange, string $bindingKey, $callback = null, string $failedQueue = "v@failed")
    {
        $channel = $this->getChannel();

        $channel->queue_declare(
            $queue,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $auto_delete = false,
            $nowait = false,
            $arguments = array(),
            $ticket = null
        );

        if (!empty($exchange)) {
            $channel->queue_bind(
                $queue,
                $exchange,
                $bindingKey,
                $nowait = false,
                $arguments = array(),
                $ticket = null
            );
        }

        $channel->basic_consume($queue,
            $tag,
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($callback, $channel, $queue, $failedQueue) {
                $ret = is_callable($callback) ? $callback($message) : true;
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                if ($ret !== true) {
                    $retryQueue = $queue . '@retry';
                    $this->pushToRetryQueue(
                        $message,
                        $retryQueue,
                        $queue,
                        $failedQueue
                    );
                }

                if ($message->body === 'quit') {
                    $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
                }
            }
        );
        while (count($channel->callbacks)) {
            $channel->wait(null, false, self::$config["read_write_timeout"]);
        }
    }

}
class RabbitMQ {
    use _trait__MQ;
}
// class RabbitMq {
//     use _trait__MQ;
// }
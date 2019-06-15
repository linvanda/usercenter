<?php

namespace App\Http\Controllers\V1;

use App\Domain\Events\UserAddedEvent;
use App\Domain\User\IMerchantRepository;
use App\Domain\User\IUserRepository;
use App\Domain\User\Merchant;
use App\Domain\User\MergeUserService;
use App\Domain\User\User;
use App\Foundation\CacheFactory;
use App\Foundation\Client\Client;
use App\Foundation\Client\ClientFactory;
use WecarSwoole\Mailer;
use WecarSwoole\RedisFactory;
use WecarSwoole\Http\Controller;
use DI\Annotation\Inject;
use WecarSwoole\Container;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Swoole\Task\SuperClosure;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\Spl\SplBean;
use Elasticsearch\Endpoints\Cat\Tasks;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Swlib\Http\Uri;
use Swlib\Saber;
use Swlib\SaberGM;
use WecarSwoole\Transaction;

/**
 * 用户控制器
 * Class Users
 * @package App\Http\Controllers\V1
 */
class Users extends Controller
{
    /**
     * 添加用户
     */
    public function add()
    {
       $this->return($this->params());
    }

    /**
     * 修改用户信息
     */
    public function edit()
    {

    }

    /**
     * 获取用户信息
     */
    public function info()
    {
//        $uid = $this->params('uid');
//        $repos = new MySQLUserRepository();
//        $user = $repos->getById($uid);

//        $this->ok(['uid' => $user->getId(), 'name' => $user->name, 'nickname' => $user->nickname]);

//        $bean = new SplBean(['name' => '张三', 'age' => 34]);
//        var_export($bean);

//        Cache::instance()->set("testccc", ['name' => '林子']);
//        $this->ok(Cache::instance()->get('testccc'));

//        $client = new HttpClient();

//        echo SaberGM::get('http://httpbin.org/get');



//        $swoole = Saber::create([
//            'base_uri' => 'localhost:9501',
//            'uri' => '/v1/merchants/123/users',
//            'use_pool' => true,
//            'before' => [
//                'a' => function (Saber\Request $request) {
//                    echo "after one\n";
//                },
//                'b' => function () {
//                    echo "after two";
//                }
//            ]
//        ]);
////
//        $response = $swoole->psr()->withMethod('GET')->exec()->recv();
////        $this->ok($response->getBody()->read($response->getBody()->getSize()));
//        $this->response->write($response->getStatusCode());

//        $reqData = [
//            'uid' => time(),
//            'coupon_status' => 1,
//            'oilstation_ids' => [171073],
//            'overdue' => 1,
//        ];
//
//        $result = Client::call('wc:users.add', $reqData);
//        $this->return($result->getBody());
//        $user = $this->userRepos->getById(3);
//        $user = $this->session()->sid();

        // 投递异步任务
//        TaskManager::async(new Test(['name' => 'test async task']));

        // 缓存
//        $this->cache->get('testtt');

        // 发送邮件
//        $message = new \Swift_Message("测试邮件", "<span style='color:red;'>邮件征文</span>");
//        $message->setFrom(['robot@weicheche.cn' => '喂车测试邮件'])->setTo('songlin.zhang@weicheche.cn')->setContentType('text/html');
//        $this->mailer->send($message);

        // 日志
//        $this->logger->critical("严重错误日志，需要发送邮件", ['name' => '林子来了']);

        // 事件
//        $this->eventDispatcher->dispatch(new UserAddedEvent($user));

//        throw new \Exception("我是异常");

//        TaskManager::async($async);

        // redis
//        $redis = RedisFactory::build('main');
//        $redis->set('testredis', 'abcdef');
//        $result = $redis->get('testredis');

//        $repos1 = \WecarSwoole\Container::get(IUserRepository::class);
//        \DI\create(User::class);

        echo Container::make(User::class, ['name' => 'lisi'])->getName();

        $this->return([12, time()]);
    }

    public function delete()
    {
        $this->return(['uid' => 10000, 'request' => $this->params()]);
    }

    public function testDI()
    {
        echo "come to controller\n";
    }
}
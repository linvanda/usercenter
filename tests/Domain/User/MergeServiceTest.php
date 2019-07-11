<?php

namespace Test\Domain\User;

use App\Domain\Events\UserMergedEvent;
use App\Domain\User\IUserRepository;
use App\Domain\User\MergeService;
use App\Domain\User\Partner;
use App\Domain\User\User;
use App\DTO\User\UserDTO;
use App\Exceptions\InvalidMergeException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\EventDispatcher\EventDispatcherInterface;

class MergeServiceTest extends TestCase
{
    private $userRepository;
    private $eventDispatcher;

    public function setUp()
    {
        $this->userRepository = (new Prophet())->prophesize()->willImplement(IUserRepository::class);
        $this->eventDispatcher = (new Prophet())->prophesize()->willImplement(EventDispatcherInterface::class);
    }

    /**
     * 指定第一个作为 target，则将第二个合并到第一个
     */
    public function testMergeWhenGiveTheTarget()
    {
        $user1 = new User(new UserDTO(['phone' => 13099999999, 'uid' => 123, 'birthday' => '2009-01-01']));
        $user2 = new User(new UserDTO(['name' => '张三', 'uid' => 456, 'birthday' => '2019-01-01']));

        $this->userRepository->merge(Argument::type(User::class), Argument::type(User::class))->shouldBeCalled();
        $this->eventDispatcher->dispatch(Argument::type(UserMergedEvent::class))->shouldBeCalled();

        // 指定 target
        $user = $this->mergeService()->merge($user1, $user2, true, false);

        $this->assertEquals($user1->uid(), $user->uid());
        $this->assertEquals($user2->name, $user->name);
        $this->assertEquals($user1->birthday, $user->birthday);
    }

    public function testMergeWhenChoosePhoneAsTarget()
    {
        $user1 = new User(new UserDTO(['phone' => 13099999999, 'uid' => 123, 'birthday' => '2009-01-01']));
        $user2 = new User(new UserDTO(['name' => '张三', 'uid' => 456, 'birthday' => '2019-01-01']));

        $this->userRepository->merge(Argument::type(User::class), Argument::type(User::class))->shouldBeCalled();
        $this->eventDispatcher->dispatch(Argument::type(UserMergedEvent::class))->shouldBeCalled();

        $user = $this->mergeService()->merge($user1, $user2);

        $this->assertEquals($user1->uid(), $user->uid());
        $this->assertEquals($user2->name, $user->name);
        $this->assertEquals($user1->birthday, $user->birthday);
    }

    public function testMergeWhenPhoneDiverged()
    {
        $user1 = new User(new UserDTO(['phone' => 13099999999, 'uid' => 123, 'birthday' => '2009-01-01']));
        $user2 = new User(new UserDTO(['name' => '张三', 'uid' => 456, 'birthday' => '2019-01-01', 'phone' => 13888888888]));

        $this->expectException(InvalidMergeException::class);

        $this->mergeService()->merge($user1, $user2);
    }

    /**
     * partner 数量相同，取有微信大号的
     * @throws InvalidMergeException
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function testMergeChoosePartnerByWX()
    {
        $user1 = new User(new UserDTO(['uid' => 123, 'birthday' => '2009-01-01']));
        $user2 = new User(new UserDTO(['name' => '张三', 'uid' => 456, 'birthday' => '2019-01-01']));
        $partner1 = new Partner(1234, Partner::P_ALIPAY, 123);
        $partner2 = new Partner(3456, Partner::P_WEIXIN, 345);
        $user1->addPartner($partner1);
        $user2->addPartner($partner2);

        $this->eventDispatcher->dispatch(Argument::type(UserMergedEvent::class))->shouldBeCalled();
        $this->userRepository->merge(Argument::type(User::class), Argument::type(User::class))->shouldBeCalled();

        $user = $this->mergeService()->merge($user1, $user2);

        $this->assertEquals($user2->uid(), $user->uid());
        $this->assertEquals($user2->name, $user->name);
        $this->assertEquals($user2->birthday, $user->birthday);
    }

    /**
     * 根据 partner取数量多多那个
     * @throws InvalidMergeException
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function testMergeChoosePartnerByNumber()
    {
        $user1 = new User(new UserDTO(['uid' => 123, 'birthday' => '2009-01-01']));
        $user2 = new User(new UserDTO(['name' => '张三', 'uid' => 456, 'birthday' => '2019-01-01']));
        $partner1 = new Partner(1234, Partner::P_ALIPAY, 123);
        $partner2 = new Partner(3456, Partner::P_WEIXIN, 345);
        $partner3 = new Partner(445, Partner::P_OTHER, 554);
        $user1->addPartner($partner1);
        $user1->addPartner($partner3);
        $user2->addPartner($partner2);

        $this->eventDispatcher->dispatch(Argument::type(UserMergedEvent::class))->shouldBeCalled();
        $this->userRepository->merge(Argument::type(User::class), Argument::type(User::class))->shouldBeCalled();

        // 取数量多的
        $user = $this->mergeService()->merge($user1, $user2);

        $this->assertEquals($user1->uid(), $user->uid());
        $this->assertEquals($user2->name, $user->name);
        $this->assertEquals($user1->birthday, $user->birthday);
    }

    /**
     * 取最早注册的
     */
    public function testMergeChooseByRegtime()
    {
        $user1 = new User(new UserDTO(['uid' => 123, 'regtime' => 1239999998, 'birthday' => '2009-01-01']));
        $user2 = new User(new UserDTO(['name' => '张三', 'uid' => 456, 'regtime' => 1239999999, 'birthday' => '2019-01-01']));

        $this->eventDispatcher->dispatch(Argument::type(UserMergedEvent::class))->shouldBeCalled();
        $this->userRepository->merge(Argument::type(User::class), Argument::type(User::class))->shouldBeCalled();

        $user = $this->mergeService()->merge($user1, $user2);

        $this->assertEquals($user1->uid(), $user->uid());
        $this->assertEquals($user2->name, $user->name);
        $this->assertEquals($user1->birthday, $user->birthday);
    }

    private function mergeService()
    {
        return new MergeService(
            $this->userRepository->reveal(),
            $this->eventDispatcher->reveal()
        );
    }
}

<?php

namespace Test\Domain\User;

use App\Domain\Events\UserAddedEvent;
use App\Domain\Events\UserUpdatedEvent;
use App\Domain\User\DivergeService;
use App\Domain\User\IUserRepository;
use App\Domain\User\MergeService;
use App\Domain\User\User;
use App\Domain\User\UserService;
use App\DTO\User\UserDTO;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Prophecy\Prophet;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserServiceTest extends TestCase
{
    /**
     * @var ProphecyInterface
     */
    private $userRepository;
    /**
     * @var ProphecyInterface
     */
    private $divergeService;
    /**
     * @var ProphecyInterface
     */
    private $eventDispatcher;

    public function setUp()
    {
        // 设置外部依赖
        $this->userRepository = (new Prophet())->prophesize()->willImplement(IUserRepository::class);
        $this->divergeService = (new Prophet())->prophesize(DivergeService::class);
        $this->eventDispatcher = (new Prophet())->prophesize()->willImplement(EventDispatcherInterface::class);
    }

    /**
     * 新用户没有提供 phone 和 partner 信息，期望直接添加
     */
    public function testAddWithNoPhoneAndPartner()
    {
        $userDTO = new UserDTO(['name' => '张三']);

        // Mock:期望 add 被调用一次并且返回新建的 User 对象
        $this->mockAdd(12345);

        $this->assertEquals(12345, $this->userService()->addUser($userDTO)->userId->getUid());
    }

    /**
     * 新用户提供了 phone 或 partner，但查询都没有记录，期望直接添加
     */
    public function testAddWhenCheckNoRecord()
    {
        $userDTO = new UserDTO(['phone' => '13900000000']);

        // stub
        $this->userRepository->getUserByPartner(Argument::any())->willReturn(null);
        $this->userRepository->getUserByPhone(Argument::any())->willReturn(null);

        // Mock:期望 add 被调用一次并且返回新建的 User 对象
        $this->mockAdd(123456);
        $this->assertEquals(123456, $this->userService()->addUser($userDTO)->userId->getUid());
    }

    /**
     * 根据新用户的 partner 和 phone 查到两条记录
     * 两个是同一个人，需要更新
     */
    public function testAddWhenTwoRecordsAsTheSameUser()
    {
        $userDTO = new UserDTO(['phone' => '13900000000', 'name' => '张三']);
        $theSameUser = new User(new UserDTO(['uid' => 1456]));

        // stub
        $this->userRepository->getUserByPartner(Argument::any())->willReturn($theSameUser);
        $this->userRepository->getUserByPhone(Argument::any())->willReturn($theSameUser);

        // mock
        $this->userRepository->update(Argument::type(User::class))->shouldBeCalled();
        $this->eventDispatcher->dispatch(Argument::type(UserUpdatedEvent::class))->shouldBeCalled();

        $newUser = $this->userService()->addUser($userDTO);

        $this->assertEquals(true, $theSameUser->equal($newUser));
        $this->assertEquals($userDTO->phone, $newUser->phone());
        $this->assertEquals($userDTO->name, $newUser->name);
    }

    /**
     * 根据新用户的 partner 和 phone 查到两条记录
     * 两个不是同一个人，需要处理分歧
     */
    public function testAddWhenTwoRecordsAreDiff()
    {
        $userDTO = new UserDTO(['phone' => '13900000000', 'name' => '张三']);
        $user1 = new User(new UserDTO(['uid' => 1456]));
        $user2 = new User(new UserDTO(['uid' => 7896]));

        // stub
        $this->userRepository->getUserByPartner(Argument::any())->willReturn($user1);
        $this->userRepository->getUserByPhone(Argument::any())->willReturn($user2);

        // mock。期望此方法被调用。此处不对此方法做详细断言，由对应的测试类完成
        $this->divergeService->dealDivergence(
            Argument::type(UserDTO::class),
            Argument::type(User::class),
            Argument::type(User::class)
        )->shouldBeCalled();

        $user = $this->userService()->addUser($userDTO);
        $this->assertInstanceOf(User::class, $user);
    }

    private function userService(): UserService
    {
        return new UserService(
            $this->userRepository->reveal(),
            $this->divergeService->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    /**
     * Mock:期望 add 被调用一次并且返回新建的 User 对象
     */
    private function mockAdd($uid)
    {
        $this->userRepository
            ->add(Argument::type(User::class))
            ->shouldBeCalled()
            ->will(function ($args) use ($uid) {
                $args[0]->userId->setUid($uid);
                return $args[0];
            });

        // 期望事件发布
        $this->eventDispatcher->dispatch(Argument::type(UserAddedEvent::class))->shouldBeCalled();
    }
}

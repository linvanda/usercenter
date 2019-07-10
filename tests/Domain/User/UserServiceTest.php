<?php

namespace Test\Domain\User;

use App\Domain\User\DivergeService;
use App\Domain\User\IUserRepository;
use App\Domain\User\MergeService;
use App\Domain\User\PartnerUser;
use App\Domain\User\PartnerUserMap;
use App\Domain\User\User;
use App\Domain\User\UserService;
use App\DTO\User\UserDTO;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Prophecy\Prophet;
use Prophecy\Argument;
use Prophecy\Promise\CallbackPromise;

class UserServiceTest extends TestCase
{
    /**
     * @var ProphecyInterface
     */
    private $userRepository;
    /**
     * @var ProphecyInterface
     */
    private $mergeService;
    /**
     * @var ProphecyInterface
     */
    private $divergeService;

    public function setUp()
    {
        // 设置外部依赖
        $this->userRepository = (new Prophet())->prophesize()->willImplement(IUserRepository::class);
        $this->mergeService = (new Prophet())->prophesize(MergeService::class);
        $this->divergeService = (new Prophet())->prophesize(DivergeService::class);
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
     * 此处分几种子场景
     */
    public function testAddWhenHasTwoRecord()
    {
        $userDTO = new UserDTO(['phone' => '13900000000', 'name' => '张三']);

        /**
         * 场景1：查到两条记录是同一个人
         * 需要更新该用户
         */
        $theSameUser = new User(new UserDTO(['uid' => 1456]));
        // stub
        $this->userRepository->getUserByPartner(Argument::any())->willReturn($theSameUser);
        $this->userRepository->getUserByPhone(Argument::any())->willReturn($theSameUser);
        // mock
        $this->userRepository->update(Argument::type(User::class))->shouldBeCalled();

        $newUser = $this->userService()->addUser($userDTO);

        $this->assertEquals($theSameUser->userId->getUid(), $newUser->userId->getUid());
//        $this->assertEquals($userDTO->phone, $newUser->userId->getPhone());
//        $this->assertEquals($userDTO->name, $newUser->name);
    }

    private function userService(): UserService
    {
        return new UserService(
            $this->userRepository->reveal(),
            $this->mergeService->reveal(),
            $this->divergeService->reveal()
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
    }
}

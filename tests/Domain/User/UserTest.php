<?php

namespace Test\Domain\User;

use App\Domain\User\DivergeService;
use App\Domain\User\IUserRepository;
use App\Domain\User\MergeService;
use App\Domain\User\PartnerUser;
use App\Domain\User\PartnerUserMap;
use App\Domain\User\User;
use App\DTO\User\UserDTO;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Prophecy\Prophet;
use Prophecy\Argument;

class UserTest extends TestCase
{
    private $userDTO;
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
        // 设置 DTO 信息
        $this->userDTO = new UserDTO(['phone' => '13989888888']);
        $this->userDTO->partnerUsers = new PartnerUserMap([
            new PartnerUser('12345', PartnerUser::P_WEIXIN, '8988dddfd')
        ]);

        // 设置外部依赖
        $this->userRepository = (new Prophet())->prophesize()->willImplement(IUserRepository::class);
        $this->mergeService = (new Prophet())->prophesize(MergeService::class);
        $this->divergeService = (new Prophet())->prophesize(DivergeService::class);
    }

    /**
     * 全新用户（partner和phone都不存在记录的，期望注册成功）
     */
    public function testRegisterNormal()
    {
        $this->userRepository->getUserByPartner($this->userDTO->partnerUsers->first())->willReturn(null);
        $this->userRepository->getUserByPhone($this->userDTO->phone)->willReturn(null);

        // 期望 add 被调用一次并且返回uid
        $this->userRepository->add(Argument::any())->shouldBeCalled()->willReturn(123);

        $this->assertEquals(123, $this->createUser()->register());
    }

    /**
     * 测试phone和partner都有记录的情况
     */
    public function testRegisterWhenPhoneAndPartnerExists()
    {

    }

    private function createUser(): User
    {
        return new User(
            $this->userRepository->reveal(),
            $this->mergeService->reveal(),
            $this->divergeService->reveal(),
            $this->userDTO
        );
    }
}

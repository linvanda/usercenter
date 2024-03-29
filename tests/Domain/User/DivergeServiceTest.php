<?php

namespace Test\Domain\User;

use App\Domain\User\DivergeService;
use App\Domain\User\Partner;
use App\Domain\User\User;
use App\DTO\User\UserDTO;
use App\Exceptions\UserRegisterConflictException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Prophecy\Prophecy\ProphecyInterface;
use App\Domain\User\IUserRepository;
use App\Domain\User\MergeService;

class DivergeServiceTest extends TestCase
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
     * @var User
     */
    private $userOfPhone;
    /**
     * @var User
     */
    private $userOfPartner;

    /**
     * @var UserDTO
     */
    private $userDTO;

    public function setUp()
    {
        // 设置外部依赖
        $this->userRepository = (new Prophet())->prophesize()->willImplement(IUserRepository::class);
        $this->mergeService = (new Prophet())->prophesize(MergeService::class);

        $partner = new Partner(980, Partner::P_WEIXIN, 1234);
        $this->userOfPartner = new User(new UserDTO(['uid' => 123]));
        $this->userOfPhone = new User(new UserDTO(['uid' => 456]));
        $this->userDTO = new UserDTO(['phone' => '13099999999']);

        $this->userOfPartner->userId->addPartner($partner);
        $this->userDTO->partners->add($partner);
    }

    /**
     * 当 userOfPartner 有 phone 时应当抛异常
     * @throws UserRegisterConflictException
     */
    public function testDivergeWhenPartnerHasPhone()
    {
        $this->userOfPartner->userId->modify(null, '18909090909');

        $this->expectException(UserRegisterConflictException::class);

        $this->divergeService()->dealDivergence($this->userDTO, $this->userOfPhone, $this->userOfPartner);
    }

    /**
     * 当手机查出的用户有同类型的 partner 信息，说明 partner 出现分歧
     */
    public function testDivergeWhenPhoneUserHasSamePartnerType()
    {
        /** @var Partner $newPartner */
        $newPartner = $this->userDTO->partners->first();
        $phonePartner = new Partner(18378492, $newPartner->type(), $newPartner->flag());
        $this->userOfPhone->userId->addPartner($phonePartner);

        $this->expectException(UserRegisterConflictException::class);

        $this->divergeService()->dealDivergence($this->userDTO, $this->userOfPhone, $this->userOfPartner);
    }

    /**
     * 正常情况下，需要合并两个用户
     * @throws UserRegisterConflictException
     * @throws \App\Exceptions\InvalidMergeException
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function testDivergeNormal()
    {
        $this->mergeService
            ->merge(
                Argument::type(User::class),
                Argument::type(User::class),
                Argument::type('bool'),
                Argument::type('bool')
            )->shouldBeCalled();

        $user = $this->divergeService()->dealDivergence($this->userDTO, $this->userOfPhone, $this->userOfPartner);
        $this->assertInstanceOf(User::class, $user);
    }

    private function divergeService(): DivergeService
    {
        return new DivergeService(
            $this->userRepository->reveal(),
            $this->mergeService->reveal()
        );
    }
}

<?php

namespace Test\Domain\User;

use App\Domain\User\Partner;
use App\Domain\User\User;
use App\DTO\User\UserDTO;
use App\Exceptions\BirthdayException;
use App\Exceptions\InvalidPhoneException;
use App\Exceptions\PartnerException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;
use App\Domain\User\IUserRepository;
use Prophecy\Prophet;
use WecarSwoole\Exceptions\Exception;

class UserTest extends TestCase
{
    /**
     * @var ProphecyInterface
     */
    private $userRepository;
    /**
     * @var User
     */
    private $user1;
    /**
     * @var User
     */
    private $user2;

    /**
     * @var User
     */
    private $user3;
    /**
     * @var UserDTO
     */
    private $userDTO;

    public function setUp()
    {
        // 设置外部依赖
        $this->userRepository = (new Prophet())->prophesize()->willImplement(IUserRepository::class);
        $this->userRepository->isPhoneBeUsed(Argument::any())->willReturn(false);

        $this->user1 = new User(new UserDTO([
            'uid' => 12345,
            'phone' => '',
            'name' => '李四',
            'gender' => 0,
        ], true, false));

        $this->user2 = new User(new UserDTO([
            'uid' => 8978,
            'phone' => '18988998899',
            'name' => '王五',
            'gender' => 2,
        ], true, false));

        $this->user3 = new User(new UserDTO([
            'uid' => 8978,
            'phone' => '18988998890',
            'name' => '貂蝉',
            'gender' => 1,
            'birthday' => '2009-01-01',
            'birthday_change' => 1,
        ], true, false));

        $this->userDTO = new UserDTO(
            [
                'phone' => '13090000000',
                'name' => '张三',
                'gender' => 1,
                'carNumbers' => ['粤B898832'],
                'birthday' => '2010-01-12',
            ],
            true,
            false
        );
    }

    /**
     * 更新策略：仅更新空值
     * @throws Exception
     * @throws \App\Exceptions\InvalidPhoneException
     */
    public function testUpdateOnlyNull()
    {
        $this->user1->updateFromDTO($this->userDTO, $this->userRepository->reveal(), User::UPDATE_ONLY_NULL);

        $this->assertEquals('13090000000', $this->user1->phone());
        $this->assertEquals('李四', $this->user1->name);
        $this->assertEquals('1', $this->user1->gender);
        $this->assertEquals('1', $this->user1->birthdayChange);
    }

    /**
     * 更新策略：全更新成新的，同时未指定强制更新phone
     */
    public function testUpdateNewNoForce()
    {
        $this->expectException(Exception::class);
        $this->user2->updateFromDTO($this->userDTO, $this->userRepository->reveal(), User::UPDATE_NEW);
    }

    /**
     * 强制更新，手机号在数据库没有记录
     * @throws Exception
     * @throws InvalidPhoneException
     */
    public function testUpdateNewWhenPhoneCheckValid()
    {
        $this->user2->updateFromDTO(
            $this->userDTO,
            $this->userRepository->reveal(),
            User::UPDATE_NEW,
            true
        );

        $this->assertEquals('13090000000', $this->user2->phone());
        $this->assertEquals('张三', $this->user2->name);
        $this->assertEquals('1', $this->user2->gender);
    }

    /**
     * 强制更新，手机号存在记录
     * @throws Exception
     * @throws InvalidPhoneException
     */
    public function testUpdateNewWhenPhoneCheckInvalid()
    {
        // 手机号已经存在，需要抛异常
        $this->userRepository->isPhoneBeUsed(Argument::any())->willReturn(true);

        $this->expectException(InvalidPhoneException::class);

        $this->user2->updateFromDTO(
            $this->userDTO,
            $this->userRepository->reveal(),
            User::UPDATE_NEW,
            true
        );
    }

    /**
     * 生日修改次数限制
     * @throws Exception
     * @throws InvalidPhoneException
     */
    public function testBirthdayChangeLimit()
    {
        $this->expectException(BirthdayException::class);

        $this->user3->updateFromDTO(
            $this->userDTO,
            $this->userRepository->reveal(),
            User::UPDATE_NEW,
            true
        );
    }

    public function testBirthdayChangeOk()
    {
        $this->user3->birthdayChange = 0;

        $this->user3->updateFromDTO(
            $this->userDTO,
            $this->userRepository->reveal(),
            User::UPDATE_NEW,
            true
        );

        $this->assertEquals($this->userDTO->birthday, $this->user3->birthday);
    }

    public function testUpdateWhenPartnerNoConflict()
    {
        $partner1 = new Partner('1235', Partner::P_WEIXIN, '111');
        $partner2 = new Partner('2989', Partner::P_ALIPAY, '123');
        $this->user1->userId->addPartner($partner1);
        $this->user1->userId->addPartner($partner2);
        $this->userDTO->partners->add($partner1);

        // 两者的partner 相同，不会出问题
        $this->user1->updateFromDTO(
            $this->userDTO,
            $this->userRepository->reveal(),
            User::UPDATE_NEW,
            true
        );

        $this->assertEquals($partner1, $this->user1->userId->getPartner($partner1->type(), $partner1->flag()));
        $this->assertEquals(2, count($this->user1->userId->getPartners()));
    }

    /**
     * partner 冲突
     * @throws Exception
     * @throws InvalidPhoneException
     */
    public function testUpdateWhenPartnerConflict()
    {
        $partner1 = new Partner('1235', Partner::P_WEIXIN, '111');
        $partner2 = new Partner('2989', Partner::P_WEIXIN, '111');
        $this->user1->userId->addPartner($partner1);
        $this->userDTO->partners->add($partner2);

        $this->expectException(PartnerException::class);

        $this->user1->updateFromDTO(
            $this->userDTO,
            $this->userRepository->reveal(),
            User::UPDATE_NEW,
            true
        );
    }
}

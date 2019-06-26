<?php

namespace App\Domain\User;

use Swoole\Exception;
use WecarSwoole\Exceptions\PropertyNotFoundException;
use WecarSwoole\Util\AutoProperty;

/**
 * 用户标识
 * Class UserId
 * @package App\Domain\User
 */
class UserId
{
    use AutoProperty;

    public const FLAG_UID = 1;
    public const FLAG_REL_UIDS = 2;
    public const FLAG_PHONE = 3;
    public const FLAG_PARTNER = 4;

    protected $uid;
    protected $relUids;
    protected $phone;
    /**
     * @var Partner
     */
    protected $partner;

    /**
     * UserId constructor.
     * @param int|null $uid
     * @param string|null $phone
     * @param array $relUids
     * @param Partner|null $partner
     */
    public function __construct(int $uid = null, string $phone = null, array $relUids = [], Partner $partner = null)
    {
        $this->setProperties(func_get_args());
    }

    /**
     * 设置用户标识
     * @param $flag
     * @param int $type
     * @throws Exception
     */
    public function setFLag($flag, int $type = self::FLAG_UID)
    {
        switch ($type) {
            case self::FLAG_UID:
                $this->uid = $flag;
                break;
            case self::FLAG_REL_UIDS:
                $this->relUids = is_array($flag) ? $flag : [$flag];
                break;
            case self::FLAG_PHONE:
                $this->phone = $flag;
                break;
            case self::FLAG_PARTNER:
                if ($flag instanceof Partner) {
                    $this->partner = $flag;
                } elseif (is_array($flag) && count($flag) == 2) {
                    $this->partner = new Partner($flag[0], $flag[1]);
                } else {
                    throw new Exception("invalid partner id for user");
                }
                break;
            default:
                throw new Exception("invalid flag type for user");
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws PropertyNotFoundException
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new PropertyNotFoundException(get_called_class(), $name);
        }

        return $this->{$name};
    }
}

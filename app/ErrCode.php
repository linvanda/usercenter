<?php

namespace App;

use WecarSwoole\ErrCode as BaseErrCode;

/**
 * Class ErrCode
 * 200 表示 OK
 * 500 以下为框架保留错误码，项目中不要用，项目中从 501 开始
 * @package App
 */
class ErrCode extends BaseErrCode
{
    public const PHONE_ALREADY_EXIST = 600; // 手机号已经存在用户
    public const USER_DATA_ANOMALY = 601; // 用户数据异常
    public const USER_NOT_EXIST = 602; // 用户不存在
    public const BIRTHDAY_CHANGE_LIMIT = 603; // 生日修改次数限制
    public const PARTNER_CANNOT_BE_CHANGED = 604;
    public const MERGE_FAIL = 605;
}

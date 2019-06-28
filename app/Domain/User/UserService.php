<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;

class UserService
{
    /**
     * 添加用户
     * @param UserDTO $userDTO
     */
    public function addUser(UserDTO $userDTO)
    {
        $user = new User($userDTO);
    }
}

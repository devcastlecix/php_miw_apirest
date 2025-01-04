<?php

namespace App\Repository;

use App\Entity\User;

/**
 * Interface UserRepositoryInterface
 *
 * @package App\Repository
 */
interface UserRepositoryInterface
{

    /**
     * Encuentra el “User” en base de su email,
     *
     * @param string        $email
     *
     * @return User|null
     */
    public function findByEmail(string $email): User|null;

}

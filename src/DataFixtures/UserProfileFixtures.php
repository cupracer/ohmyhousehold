<?php

/*
 * Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserProfileFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const ADMIN_USER_PROFILE_REFERENCE = 'admin-user-profile';

    public function load(ObjectManager $manager): void
    {
        $adminUserProfile = new UserProfile();
        $adminUserProfile
            ->setForename('Admin')
            ->setSurname('User')
            ->setLocale('de')
            ->setUser($this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class));
        $manager->persist($adminUserProfile);

        $manager->flush();

        $this->addReference(self::ADMIN_USER_PROFILE_REFERENCE, $adminUserProfile);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['demo'];
    }
}

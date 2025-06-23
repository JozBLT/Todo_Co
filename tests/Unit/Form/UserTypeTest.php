<?php

namespace App\Tests\Unit\Form;

use App\Entity\User;
use App\Form\UserType;
use ReflectionClass;
use Symfony\Component\Form\Test\TypeTestCase;

class UserTypeTest extends TypeTestCase
{

    public function testDefaultFieldsForCreation(): void
    {
        $form = $this->factory->create(UserType::class, null, [
            'is_edit' => false,
            'current_user' => null,
        ]);

        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));
        $this->assertTrue($form->has('roles'));
    }

    public function testFieldsForEditSameUser(): void
    {
        $user = new User();
        $user->setUsername('admin')->setEmail('admin@oc.com');
        $this->setPrivateId($user, 1);

        $form = $this->factory->create(UserType::class, $user, [
            'is_edit' => true,
            'current_user' => $user,
        ]);

        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('email'));
        $this->assertFalse($form->has('password'));
        $this->assertFalse($form->has('roles'));
    }

    public function testFieldsForEditOtherUser(): void
    {
        $current = new User();
        $current->setUsername('admin')->setEmail('admin@oc.com');
        $this->setPrivateId($current, 1);

        $other = new User();
        $other->setUsername('user')->setEmail('user@oc.com');
        $this->setPrivateId($other, 2);

        $form = $this->factory->create(UserType::class, $other, [
            'is_edit' => true,
            'current_user' => $current,
        ]);

        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('email'));
        $this->assertFalse($form->has('password'));
        $this->assertTrue($form->has('roles'));
    }

    private function setPrivateId(User $user, int $id): void
    {
        $reflection = new ReflectionClass(User::class);
        $property = $reflection->getProperty('id');
        $property->setValue($user, $id);
    }
}

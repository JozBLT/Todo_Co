<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TaskVoter extends Voter
{
    public const DELETE = 'TASK_DELETE';
    public const EDIT = 'TASK_EDIT';
    public const VIEW = 'TASK_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE], true)
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Task $task */
        $task = $subject;
        $author = $task->getAuthor();

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return $author instanceof User && $author->getId() === $user->getId();
    }
}

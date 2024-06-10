<?php
/**
 * User voter.
 */

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserVoter.
 */
class UserVoter extends Voter
{
    /**
     * List permission.
     *
     * @const string
     */
    private const LIST = 'LIST';
    /**
     * Edit permission.
     *
     * @const string
     */
    private const EDIT = 'EDIT';

    /**
     * View permission.
     *
     * @const string
     */
    private const VIEW = 'VIEW';

    /**
     * Delete permission.
     *
     * @const string
     */
    private const DELETE = 'DELETE';

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool Result
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::LIST])
            && $subject instanceof User;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute Permission name
     * @param mixed          $subject   Object
     * @param TokenInterface $token     Security token
     *
     * @return bool Vote result
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if (!$subject instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::LIST => $this->canList($subject, $user),
            default => false,
        };
    }

    /**
     * Checks if viever can list users.
     *
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canList(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Checks if viever can edit user.
     *
     * @param User          $viever User entity
     * @param UserInterface $user   User
     *
     * @return bool Result
     */
    private function canEdit(User $viever, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Checks if viever can view user.
     *
     * @param User          $viever User entity
     * @param UserInterface $user   User
     *
     * @return bool Result
     */
    private function canView(User $viever, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Checks if viever can delete user.
     *
     * @param User          $viever User entity
     * @param UserInterface $user   User
     *
     * @return bool Result
     */
    private function canDelete(User $viever, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

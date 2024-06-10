<?php
/**
 * Category voter.
 */

namespace App\Security\Voter;

use App\Entity\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class CategoryVoter.
 */
class CategoryVoter extends Voter
{
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
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Category;
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
        if (!$subject instanceof Category) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    /**
     * Checks if user can edit category.
     *
     * @param Category      $category Category entity
     * @param UserInterface $user     User
     *
     * @return bool Result
     */
    private function canEdit(Category $category, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Checks if user can view category.
     *
     * @param Category      $category Category entity
     * @param UserInterface $user     User
     *
     * @return bool Result
     */
    private function canView(Category $category, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Checks if user can delete category.
     *
     * @param Category      $category Category entity
     * @param UserInterface $user     User
     *
     * @return bool Result
     */
    private function canDelete(Category $category, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

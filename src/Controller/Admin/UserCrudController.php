<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\UserEntity;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Override;

/**
 * @extends AbstractCrudController<UserEntity>
 */
class UserCrudController extends AbstractCrudController
{
    #[Override]
    public static function getEntityFqcn(): string
    {
        return UserEntity::class;
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('phone'),
            TextField::new('firstName'),
            TextField::new('lastName'),
            TextField::new('email'),
            ArrayField::new('roles'),
        ];
    }
}

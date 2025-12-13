<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BookingEntity;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Override;

/**
 * @extends AbstractCrudController<BookingEntity>
 */
class BookingCrudController extends AbstractCrudController
{
    #[Override]
    public static function getEntityFqcn(): string
    {
        return BookingEntity::class;
    }
    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('house');
        yield AssociationField::new('user')->setLabel('User (phone)');
        yield DateField::new('dateFrom');
        yield DateField::new('dateTo');
        yield TextareaField::new('comment');
        yield DateTimeField::new('createdAt')->onlyOnIndex();
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('house'))
            ->add(EntityFilter::new('user'))
            ->add(DateTimeFilter::new('dateFrom'))
            ->add(DateTimeFilter::new('dateTo'))
            ->add(DateTimeFilter::new('createdAt'))
            ->add(TextFilter::new('comment'));
    }
}

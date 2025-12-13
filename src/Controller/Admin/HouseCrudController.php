<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\HouseEntity;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Override;

/**
 * @extends AbstractCrudController<HouseEntity>
 */
class HouseCrudController extends AbstractCrudController
{
    #[Override]
    public static function getEntityFqcn(): string
    {
        return HouseEntity::class;
    }
    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name');
        yield TextField::new('type');
        yield NumberField::new('beds');
        yield NumberField::new('rowFromSea');
        yield NumberField::new('pricePerNightRub');
        yield DateTimeField::new('createdAt')->onlyOnIndex();
    }
    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('type'))
            ->add(TextFilter::new('pricePerNightRub'));
    }
}

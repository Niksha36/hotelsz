<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BookingEntity;
use App\Entity\HouseEntity;
use App\Entity\UserEntity;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    #[Override]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        // перенаправляем на список Booking по умолчанию
        return $this->redirect($adminUrlGenerator->setController(BookingCrudController::class)->generateUrl());
    }
    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkToCrud('Bookings', 'fas fa-calendar', BookingEntity::class);
        yield MenuItem::linkToCrud('Houses', 'fas fa-building', HouseEntity::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-user', UserEntity::class);
    }
}

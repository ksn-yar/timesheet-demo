<?php

declare(strict_types=1);

use App\Identity\Application\Port\ListGroupsOutputPortInterface;
use App\Identity\Application\Port\ListUsersOutputPortInterface;
use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Application\Port\PasswordVerifierInterface;
use App\Identity\Application\Port\TicketExistenceCheckerInterface;
use App\Identity\Domain\Event\GroupCreated;
use App\Identity\Domain\Event\GroupDeleted;
use App\Identity\Domain\Event\GroupUpdated;
use App\Identity\Domain\Event\UserAssignedToGroup;
use App\Identity\Domain\Event\UserCreated;
use App\Identity\Domain\Event\UserDeactivated;
use App\Identity\Domain\Event\UserDeleted;
use App\Identity\Domain\Event\UserRemovedFromGroup;
use App\Identity\Domain\Event\UserUpdated;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Infrastructure\Presenter\HttpListGroupsPresenter;
use App\Identity\Infrastructure\Presenter\HttpListUsersPresenter;
use App\Identity\Infrastructure\Repository\GroupRepository;
use App\Identity\Infrastructure\Repository\UserRepository;
use App\Identity\Infrastructure\Security\SymfonyPasswordHasher;
use App\Identity\Infrastructure\Security\SymfonyPasswordVerifier;
use App\ProjectManagement\Application\Port\ListChangeRequestsOutputPortInterface;
use App\ProjectManagement\Application\Port\ListClientsOutputPortInterface;
use App\ProjectManagement\Application\Port\ListProjectsOutputPortInterface;
use App\ProjectManagement\Application\Port\ListTasksOutputPortInterface;
use App\ProjectManagement\Domain\Event\ChangeRequestCreated;
use App\ProjectManagement\Domain\Event\ChangeRequestDeleted;
use App\ProjectManagement\Domain\Event\ChangeRequestUpdated;
use App\ProjectManagement\Domain\Event\ClientCreated;
use App\ProjectManagement\Domain\Event\ClientDeleted;
use App\ProjectManagement\Domain\Event\ClientUpdated;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\ProjectManagement\Domain\Event\ProjectDeleted;
use App\ProjectManagement\Domain\Event\ProjectUpdated;
use App\ProjectManagement\Domain\Event\TaskCreated;
use App\ProjectManagement\Domain\Event\TaskDeleted;
use App\ProjectManagement\Domain\Event\TaskUpdated;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\Repository\TaskRepositoryInterface;
use App\ProjectManagement\Infrastructure\EventListener\AuditLogEventListener;
use App\ProjectManagement\Infrastructure\Presenter\HttpListChangeRequestsPresenter;
use App\ProjectManagement\Infrastructure\Presenter\HttpListClientsPresenter;
use App\ProjectManagement\Infrastructure\Presenter\HttpListProjectsPresenter;
use App\ProjectManagement\Infrastructure\Presenter\HttpListTasksPresenter;
use App\ProjectManagement\Infrastructure\Repository\ChangeRequestRepository;
use App\ProjectManagement\Infrastructure\Repository\ClientRepository;
use App\ProjectManagement\Infrastructure\Repository\ProjectRepository;
use App\ProjectManagement\Infrastructure\Repository\TaskRepository;
use App\Reporting\Application\Port\GetReportOutputPortInterface;
use App\Reporting\Application\Port\ListExportedReportsOutputPortInterface;
use App\Reporting\Application\Port\ListReportsOutputPortInterface;
use App\Reporting\Application\Port\ReportFileGeneratorInterface;
use App\Reporting\Application\Port\TicketQueryServiceInterface;
use App\Reporting\Domain\Event\ReportAdded;
use App\Reporting\Domain\Event\ReportsExported;
use App\Reporting\Domain\Repository\ReportExportRepositoryInterface;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use App\Reporting\Infrastructure\Presenter\HttpGetReportPresenter;
use App\Reporting\Infrastructure\Presenter\HttpListExportedReportsPresenter;
use App\Reporting\Infrastructure\Presenter\HttpListReportsPresenter;
use App\Reporting\Infrastructure\Repository\ReportExportRepository;
use App\Reporting\Infrastructure\Repository\ReportRepository;
use App\Reporting\Infrastructure\Service\DoctrineTicketQueryService;
use App\Reporting\Infrastructure\Service\ReportFileGeneratorFactory;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use App\Timesheet\Application\Port\EmployeeResolverInterface;
use App\Timesheet\Application\Port\ExternalDataFetcherInterface;
use App\Timesheet\Application\Port\ListImportPoliciesOutputPortInterface;
use App\Timesheet\Application\Port\ListTicketsOutputPortInterface;
use App\Timesheet\Application\Port\RateProviderInterface;
use App\Timesheet\Application\Port\RunImportOutputPortInterface;
use App\Timesheet\Application\Port\TaskExistenceCheckerInterface;
use App\Timesheet\Application\Port\TaskResolverInterface;
use App\Timesheet\Application\Port\WorkExistenceCheckerInterface;
use App\Timesheet\Application\Port\WorkResolverInterface;
use App\Timesheet\Domain\Event\ImportPolicyCreated;
use App\Timesheet\Domain\Event\ImportPolicyUpdated;
use App\Timesheet\Domain\Event\TicketsAdded;
use App\Timesheet\Domain\Event\TicketsImported;
use App\Timesheet\Domain\Event\TicketUpdated;
use App\Timesheet\Domain\Repository\ImportPolicyRepositoryInterface;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use App\Timesheet\Infrastructure\Port\DoctrineEmployeeResolver;
use App\Timesheet\Infrastructure\Port\DoctrineRateProvider;
use App\Timesheet\Infrastructure\Port\DoctrineTaskExistenceChecker;
use App\Timesheet\Infrastructure\Port\DoctrineTaskResolver;
use App\Timesheet\Infrastructure\Port\DoctrineWorkExistenceChecker;
use App\Timesheet\Infrastructure\Port\DoctrineWorkResolver;
use App\Timesheet\Infrastructure\Port\NullExternalDataFetcher;
use App\Timesheet\Infrastructure\Port\SecurityCurrentUserProvider;
use App\Timesheet\Infrastructure\Presenter\HttpListImportPoliciesPresenter;
use App\Timesheet\Infrastructure\Presenter\HttpListTicketsPresenter;
use App\Timesheet\Infrastructure\Presenter\HttpRunImportPresenter;
use App\Timesheet\Infrastructure\Repository\ImportPolicyRepository;
use App\Timesheet\Infrastructure\Repository\TicketRepository;
use App\WorkCatalog\Application\Port\ListRatesOutputPortInterface;
use App\WorkCatalog\Application\Port\ListRolesOutputPortInterface;
use App\WorkCatalog\Application\Port\ListWorksOutputPortInterface;
use App\WorkCatalog\Application\Port\RateAppliedToTicketCheckerInterface;
use App\WorkCatalog\Application\Port\TicketExistenceByWorkCheckerInterface;
use App\WorkCatalog\Application\Port\UserExistenceByRoleCheckerInterface;
use App\WorkCatalog\Domain\Event\RateCreated;
use App\WorkCatalog\Domain\Event\RateDeleted;
use App\WorkCatalog\Domain\Event\RateUpdated;
use App\WorkCatalog\Domain\Event\RoleCreated;
use App\WorkCatalog\Domain\Event\RoleDeleted;
use App\WorkCatalog\Domain\Event\WorkCreated;
use App\WorkCatalog\Domain\Event\WorkDeleted;
use App\WorkCatalog\Domain\Repository\RateRepositoryInterface;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use App\WorkCatalog\Infrastructure\Port\RateAppliedToTicketChecker;
use App\WorkCatalog\Infrastructure\Port\TicketExistenceByWorkChecker;
use App\WorkCatalog\Infrastructure\Port\UserExistenceByRoleChecker;
use App\WorkCatalog\Infrastructure\Presenter\HttpListRatesPresenter;
use App\WorkCatalog\Infrastructure\Presenter\HttpListRolesPresenter;
use App\WorkCatalog\Infrastructure\Presenter\HttpListWorksPresenter;
use App\WorkCatalog\Infrastructure\Repository\RateRepository;
use App\WorkCatalog\Infrastructure\Repository\RoleRepository;
use App\WorkCatalog\Infrastructure\Repository\WorkRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->load('App\\', __DIR__ . '/../src/');

    // --- Project Management: привязка доменных интерфейсов к реализациям ---

    $services->alias(
        ClientRepositoryInterface::class,
        ClientRepository::class,
    );

    $services->alias(
        ProjectRepositoryInterface::class,
        ProjectRepository::class,
    );

    $services->alias(
        ChangeRequestRepositoryInterface::class,
        ChangeRequestRepository::class,
    );

    $services->alias(
        TaskRepositoryInterface::class,
        TaskRepository::class,
    );

    // --- Project Management: привязка OutputPort интерфейсов к Presenter ---

    $services->alias(
        ListClientsOutputPortInterface::class,
        HttpListClientsPresenter::class,
    );

    $services->alias(
        ListProjectsOutputPortInterface::class,
        HttpListProjectsPresenter::class,
    );

    $services->alias(
        ListChangeRequestsOutputPortInterface::class,
        HttpListChangeRequestsPresenter::class,
    );

    $services->alias(
        ListTasksOutputPortInterface::class,
        HttpListTasksPresenter::class,
    );

    // --- Project Management: Presenter как non-shared (новый экземпляр на каждый запрос) ---

    $services->set(HttpListClientsPresenter::class)->share(false);
    $services->set(HttpListProjectsPresenter::class)->share(false);
    $services->set(HttpListChangeRequestsPresenter::class)->share(false);
    $services->set(HttpListTasksPresenter::class)->share(false);

    // --- Project Management: AuditLog Event Listener ---

    $services->set(AuditLogEventListener::class)
        ->tag('kernel.event_listener', ['event' => ClientCreated::class, 'method' => 'onClientCreated'])
        ->tag('kernel.event_listener', ['event' => ClientUpdated::class, 'method' => 'onClientUpdated'])
        ->tag('kernel.event_listener', ['event' => ClientDeleted::class, 'method' => 'onClientDeleted'])
        ->tag('kernel.event_listener', ['event' => ProjectCreated::class, 'method' => 'onProjectCreated'])
        ->tag('kernel.event_listener', ['event' => ProjectUpdated::class, 'method' => 'onProjectUpdated'])
        ->tag('kernel.event_listener', ['event' => ProjectDeleted::class, 'method' => 'onProjectDeleted'])
        ->tag('kernel.event_listener', ['event' => ChangeRequestCreated::class, 'method' => 'onChangeRequestCreated'])
        ->tag('kernel.event_listener', ['event' => ChangeRequestUpdated::class, 'method' => 'onChangeRequestUpdated'])
        ->tag('kernel.event_listener', ['event' => ChangeRequestDeleted::class, 'method' => 'onChangeRequestDeleted'])
        ->tag('kernel.event_listener', ['event' => TaskCreated::class, 'method' => 'onTaskCreated'])
        ->tag('kernel.event_listener', ['event' => TaskUpdated::class, 'method' => 'onTaskUpdated'])
        ->tag('kernel.event_listener', ['event' => TaskDeleted::class, 'method' => 'onTaskDeleted'])
    ;

    // --- Identity: привязка доменных интерфейсов к реализациям ---

    $services->alias(
        UserRepositoryInterface::class,
        UserRepository::class,
    );

    $services->alias(
        GroupRepositoryInterface::class,
        GroupRepository::class,
    );

    // --- Identity: привязка Port интерфейсов к реализациям ---

    $services->alias(
        PasswordHasherInterface::class,
        SymfonyPasswordHasher::class,
    );

    $services->alias(
        PasswordVerifierInterface::class,
        SymfonyPasswordVerifier::class,
    );

    $services->alias(
        TicketExistenceCheckerInterface::class,
        UserRepository::class,
    );

    // --- Identity: привязка OutputPort интерфейсов к Presenter ---

    $services->alias(
        ListUsersOutputPortInterface::class,
        HttpListUsersPresenter::class,
    );

    $services->alias(
        ListGroupsOutputPortInterface::class,
        HttpListGroupsPresenter::class,
    );

    // --- Identity: Presenter как non-shared (новый экземпляр на каждый запрос) ---

    $services->set(HttpListUsersPresenter::class)->share(false);
    $services->set(HttpListGroupsPresenter::class)->share(false);

    // --- Identity: AuditLog Event Listener ---

    $services->set(App\Identity\Infrastructure\EventListener\AuditLogEventListener::class)
        ->tag('kernel.event_listener', ['event' => UserCreated::class, 'method' => 'onUserCreated'])
        ->tag('kernel.event_listener', ['event' => UserUpdated::class, 'method' => 'onUserUpdated'])
        ->tag('kernel.event_listener', ['event' => UserDeactivated::class, 'method' => 'onUserDeactivated'])
        ->tag('kernel.event_listener', ['event' => UserDeleted::class, 'method' => 'onUserDeleted'])
        ->tag('kernel.event_listener', ['event' => UserAssignedToGroup::class, 'method' => 'onUserAssignedToGroup'])
        ->tag('kernel.event_listener', ['event' => UserRemovedFromGroup::class, 'method' => 'onUserRemovedFromGroup'])
        ->tag('kernel.event_listener', ['event' => GroupCreated::class, 'method' => 'onGroupCreated'])
        ->tag('kernel.event_listener', ['event' => GroupUpdated::class, 'method' => 'onGroupUpdated'])
        ->tag('kernel.event_listener', ['event' => GroupDeleted::class, 'method' => 'onGroupDeleted'])
    ;

    // --- Work Catalog: привязка доменных интерфейсов к реализациям ---

    $services->alias(
        WorkRepositoryInterface::class,
        WorkRepository::class,
    );

    $services->alias(
        RoleRepositoryInterface::class,
        RoleRepository::class,
    );

    $services->alias(
        RateRepositoryInterface::class,
        RateRepository::class,
    );

    // --- Work Catalog: привязка Port интерфейсов к реализациям ---

    $services->alias(
        TicketExistenceByWorkCheckerInterface::class,
        TicketExistenceByWorkChecker::class,
    );

    $services->alias(
        UserExistenceByRoleCheckerInterface::class,
        UserExistenceByRoleChecker::class,
    );

    $services->alias(
        RateAppliedToTicketCheckerInterface::class,
        RateAppliedToTicketChecker::class,
    );

    // --- Work Catalog: привязка OutputPort интерфейсов к Presenter ---

    $services->alias(
        ListWorksOutputPortInterface::class,
        HttpListWorksPresenter::class,
    );

    $services->alias(
        ListRolesOutputPortInterface::class,
        HttpListRolesPresenter::class,
    );

    $services->alias(
        ListRatesOutputPortInterface::class,
        HttpListRatesPresenter::class,
    );

    // --- Work Catalog: Presenter как non-shared (новый экземпляр на каждый запрос) ---

    $services->set(HttpListWorksPresenter::class)->share(false);
    $services->set(HttpListRolesPresenter::class)->share(false);
    $services->set(HttpListRatesPresenter::class)->share(false);

    // --- Work Catalog: AuditLog Event Listener ---

    $services->set(App\WorkCatalog\Infrastructure\EventListener\AuditLogEventListener::class)
        ->tag('kernel.event_listener', ['event' => WorkCreated::class, 'method' => 'onWorkCreated'])
        ->tag('kernel.event_listener', ['event' => WorkDeleted::class, 'method' => 'onWorkDeleted'])
        ->tag('kernel.event_listener', ['event' => RoleCreated::class, 'method' => 'onRoleCreated'])
        ->tag('kernel.event_listener', ['event' => RoleDeleted::class, 'method' => 'onRoleDeleted'])
        ->tag('kernel.event_listener', ['event' => RateCreated::class, 'method' => 'onRateCreated'])
        ->tag('kernel.event_listener', ['event' => RateUpdated::class, 'method' => 'onRateUpdated'])
        ->tag('kernel.event_listener', ['event' => RateDeleted::class, 'method' => 'onRateDeleted'])
    ;

    // --- Timesheet: привязка доменных интерфейсов к реализациям ---

    $services->alias(
        TicketRepositoryInterface::class,
        TicketRepository::class,
    );

    $services->alias(
        ImportPolicyRepositoryInterface::class,
        ImportPolicyRepository::class,
    );

    // --- Timesheet: привязка Application Port интерфейсов к реализациям ---

    $services->alias(
        TaskExistenceCheckerInterface::class,
        DoctrineTaskExistenceChecker::class,
    );

    $services->alias(
        WorkExistenceCheckerInterface::class,
        DoctrineWorkExistenceChecker::class,
    );

    $services->alias(
        RateProviderInterface::class,
        DoctrineRateProvider::class,
    );

    $services->alias(
        CurrentUserProviderInterface::class,
        SecurityCurrentUserProvider::class,
    );

    $services->alias(
        ExternalDataFetcherInterface::class,
        NullExternalDataFetcher::class,
    );

    $services->alias(
        EmployeeResolverInterface::class,
        DoctrineEmployeeResolver::class,
    );

    $services->alias(
        TaskResolverInterface::class,
        DoctrineTaskResolver::class,
    );

    $services->alias(
        WorkResolverInterface::class,
        DoctrineWorkResolver::class,
    );

    // --- Timesheet: привязка OutputPort интерфейсов к Presenter ---

    $services->alias(
        ListTicketsOutputPortInterface::class,
        HttpListTicketsPresenter::class,
    );

    $services->alias(
        ListImportPoliciesOutputPortInterface::class,
        HttpListImportPoliciesPresenter::class,
    );

    $services->alias(
        RunImportOutputPortInterface::class,
        HttpRunImportPresenter::class,
    );

    // --- Timesheet: Presenter как non-shared ---

    $services->set(HttpListTicketsPresenter::class)->share(false);
    $services->set(HttpListImportPoliciesPresenter::class)->share(false);
    $services->set(HttpRunImportPresenter::class)->share(false);

    // --- Timesheet: AuditLog Event Listener ---

    $services->set(App\Timesheet\Infrastructure\EventListener\AuditLogEventListener::class)
        ->tag('kernel.event_listener', ['event' => TicketsAdded::class, 'method' => 'onTicketsAdded'])
        ->tag('kernel.event_listener', ['event' => TicketUpdated::class, 'method' => 'onTicketUpdated'])
        ->tag('kernel.event_listener', ['event' => TicketsImported::class, 'method' => 'onTicketsImported'])
        ->tag('kernel.event_listener', ['event' => ImportPolicyCreated::class, 'method' => 'onImportPolicyCreated'])
        ->tag('kernel.event_listener', ['event' => ImportPolicyUpdated::class, 'method' => 'onImportPolicyUpdated'])
    ;

    // --- Reporting: привязка доменных интерфейсов к реализациям ---

    $services->alias(
        ReportRepositoryInterface::class,
        ReportRepository::class,
    );

    $services->alias(
        ReportExportRepositoryInterface::class,
        ReportExportRepository::class,
    );

    // --- Reporting: привязка Port интерфейсов к реализациям ---

    $services->alias(
        TicketQueryServiceInterface::class,
        DoctrineTicketQueryService::class,
    );

    $services->alias(
        ReportFileGeneratorInterface::class,
        ReportFileGeneratorFactory::class,
    );

    // --- Reporting: привязка OutputPort интерфейсов к Presenter ---

    $services->alias(
        GetReportOutputPortInterface::class,
        HttpGetReportPresenter::class,
    );

    $services->alias(
        ListReportsOutputPortInterface::class,
        HttpListReportsPresenter::class,
    );

    $services->alias(
        ListExportedReportsOutputPortInterface::class,
        HttpListExportedReportsPresenter::class,
    );

    // --- Reporting: Presenter как non-shared (новый экземпляр на каждый запрос) ---

    $services->set(HttpGetReportPresenter::class)->share(false);
    $services->set(HttpListReportsPresenter::class)->share(false);
    $services->set(HttpListExportedReportsPresenter::class)->share(false);

    // --- Reporting: AuditLog Event Listener ---

    $services->set(App\Reporting\Infrastructure\EventListener\AuditLogEventListener::class)
        ->tag('kernel.event_listener', ['event' => ReportAdded::class, 'method' => 'onReportAdded'])
        ->tag('kernel.event_listener', ['event' => ReportsExported::class, 'method' => 'onReportsExported'])
    ;
};

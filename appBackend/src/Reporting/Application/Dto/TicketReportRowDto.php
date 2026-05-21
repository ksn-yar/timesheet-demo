<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/**
 * Проекция тикета для формирования отчёта.
 * Содержит денормализованные данные из Timesheet BC, необходимые для агрегации.
 */
final readonly class TicketReportRowDto
{
    public function __construct(
        public string $ticketId,
        public string $employeeId,
        public string $employeeName,
        public string $groupId,
        public string $groupName,
        public string $taskId,
        public string $taskName,
        public string $crId,
        public string $crName,
        public string $projectId,
        public string $projectName,
        public string $workId,
        public string $workName,
        public string $date,
        public string $hours,
        public string $rateSnapshot,
    ) {}

    /** Создаёт экземпляр из ассоциативного массива результата Doctrine-запроса. */
    public static function fromArray(array $row): self
    {
        return new self(
            ticketId: (string) $row['ticketId'],
            employeeId: (string) $row['employeeId'],
            employeeName: (string) $row['employeeName'],
            groupId: (string) $row['groupId'],
            groupName: (string) $row['groupName'],
            taskId: (string) $row['taskId'],
            taskName: (string) $row['taskName'],
            crId: (string) $row['crId'],
            crName: (string) $row['crName'],
            projectId: (string) $row['projectId'],
            projectName: (string) $row['projectName'],
            workId: (string) $row['workId'],
            workName: (string) $row['workName'],
            date: (string) $row['date'],
            hours: (string) $row['hours'],
            rateSnapshot: (string) $row['rateSnapshot'],
        );
    }
}

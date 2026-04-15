<?php

declare(strict_types=1);

namespace App\Reporting\Application\Service;

use App\Reporting\Domain\Enum\GroupByDimension;
use App\Reporting\Domain\ValueObject\ReportData;
use App\Reporting\Domain\ValueObject\ReportGroupBy;

/**
 * Агрегирует проекции тикетов в строки отчёта по заданным измерениям группировки.
 * Stateless-сервис: не имеет зависимостей и может быть вызван напрямую.
 */
final class ReportAggregationService
{
    /**
     * Группирует тикеты по измерениям и вычисляет агрегированные показатели для каждой группы.
     *
     * @param array<int, array<string, mixed>> $tickets Проекции тикетов из TicketQueryServiceInterface
     */
    public function aggregate(array $tickets, ReportGroupBy $groupBy): ReportData
    {
        $dimensions = $groupBy->dimensions();
        $groups = [];

        foreach ($tickets as $ticket) {
            $key = $this->buildGroupKey($ticket, $dimensions);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'row' => $this->extractDimensionFields($ticket, $dimensions),
                    'totalHours' => 0.0,
                    'totalCost' => 0.0,
                    'ticketCount' => 0,
                ];
            }

            $hours = (float) ($ticket['hours'] ?? 0.0);
            $rate = (float) ($ticket['rateSnapshot'] ?? 0.0);

            $groups[$key]['totalHours'] += $hours;
            $groups[$key]['totalCost'] += $hours * $rate;
            ++$groups[$key]['ticketCount'];
        }

        $rows = array_map(
            static fn (array $group): array => array_merge(
                $group['row'],
                [
                    'totalHours' => $group['totalHours'],
                    'totalCost' => $group['totalCost'],
                    'ticketCount' => $group['ticketCount'],
                ],
            ),
            array_values($groups),
        );

        return new ReportData($rows);
    }

    /**
     * Строит строковый ключ группы из значений измерений для заданного тикета.
     *
     * @param array<string, mixed> $ticket
     * @param GroupByDimension[]   $dimensions
     */
    private function buildGroupKey(array $ticket, array $dimensions): string
    {
        $parts = [];

        foreach ($dimensions as $dimension) {
            $parts[] = match ($dimension) {
                GroupByDimension::Employee => 'employee:' . ($ticket['employeeId'] ?? ''),
                GroupByDimension::Group => 'group:' . ($ticket['groupId'] ?? ''),
                GroupByDimension::Project => 'project:' . ($ticket['projectId'] ?? ''),
                GroupByDimension::ChangeRequest => 'cr:' . ($ticket['crId'] ?? ''),
                GroupByDimension::Task => 'task:' . ($ticket['taskId'] ?? ''),
                GroupByDimension::Work => 'work:' . ($ticket['workId'] ?? ''),
            };
        }

        return implode('|', $parts);
    }

    /**
     * Извлекает поля ID и наименований для активных измерений из первого тикета группы.
     *
     * @param array<string, mixed> $ticket
     * @param GroupByDimension[]   $dimensions
     *
     * @return array<string, mixed>
     */
    private function extractDimensionFields(array $ticket, array $dimensions): array
    {
        $fields = [];

        foreach ($dimensions as $dimension) {
            $fields = array_merge($fields, match ($dimension) {
                GroupByDimension::Employee => [
                    'employeeId' => $ticket['employeeId'] ?? null,
                    'employeeName' => $ticket['employeeName'] ?? null,
                ],
                GroupByDimension::Group => [
                    'groupId' => $ticket['groupId'] ?? null,
                    'groupName' => $ticket['groupName'] ?? null,
                ],
                GroupByDimension::Project => [
                    'projectId' => $ticket['projectId'] ?? null,
                    'projectName' => $ticket['projectName'] ?? null,
                ],
                GroupByDimension::ChangeRequest => [
                    'crId' => $ticket['crId'] ?? null,
                    'crName' => $ticket['crName'] ?? null,
                ],
                GroupByDimension::Task => [
                    'taskId' => $ticket['taskId'] ?? null,
                    'taskName' => $ticket['taskName'] ?? null,
                ],
                GroupByDimension::Work => [
                    'workId' => $ticket['workId'] ?? null,
                    'workName' => $ticket['workName'] ?? null,
                ],
            });
        }

        return $fields;
    }
}

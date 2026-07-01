<?php

namespace App\Period;

class PeriodResponse
{
    public static function fromEntity(Period $item): array
    {
        return [
            'id' => $item->id(),
            'period_name' => $item->periodName(),
            'timetable_id' => $item->timetableId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Period $item): array => self::fromEntity($item), $items);
    }
}

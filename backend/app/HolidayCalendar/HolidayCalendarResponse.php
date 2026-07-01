<?php

namespace App\HolidayCalendar;

class HolidayCalendarResponse
{
    public static function fromEntity(HolidayCalendar $item): array
    {
        return [
            'id' => $item->id(),
            'holiday_name' => $item->holidayName(),
            'academic_session_id' => $item->academicSessionId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (HolidayCalendar $item): array => self::fromEntity($item), $items);
    }
}

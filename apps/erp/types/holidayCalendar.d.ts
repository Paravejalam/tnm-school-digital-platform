export type HolidayCalendarStatus = 'active' | 'inactive';
export type HolidayCalendarType = 'national' | 'regional' | 'school' | 'religious' | 'other';

export interface HolidayCalendarRecord {
  id: number;
  holiday_name: string | null;
  academic_session_id: number | null;
  status: HolidayCalendarStatus | string | null;
}

export interface HolidayCalendarListResponse {
  items: HolidayCalendarRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface HolidayCalendarFormValues {
  holiday_name: string;
  academic_session_id: string;
  holiday_date: string;
  holiday_type: HolidayCalendarType;
  is_recurring: boolean;
  description: string;
  status: HolidayCalendarStatus;
}

export interface HolidayCalendarCreateValues extends HolidayCalendarFormValues {}

export interface HolidayCalendarUpdateValues {
  holiday_name: string;
  academic_session_id: string;
  status: HolidayCalendarStatus;
}

export interface HolidayCalendarSessionOption {
  id: number;
  session_name: string | null;
  status: string | null;
}

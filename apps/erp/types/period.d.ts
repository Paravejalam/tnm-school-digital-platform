export type PeriodStatus = 'active' | 'inactive';
export type PeriodDay = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';

export interface PeriodRecord {
  id: number;
  period_name: string | null;
  timetable_id: number | null;
  status: PeriodStatus | string | null;
}

export interface PeriodListResponse {
  items: PeriodRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface PeriodFormValues {
  period_name: string;
  timetable_id: string;
  day_of_week: PeriodDay | '';
  start_time: string;
  end_time: string;
  period_order: string;
  status: PeriodStatus;
}

export interface PeriodCreateValues extends PeriodFormValues {}

/** Only response-backed fields are safe to populate during edit. */
export interface PeriodUpdateValues {
  period_name: string;
  timetable_id: string;
  status: PeriodStatus;
}

export type AttendanceRecordStatus = 'present' | 'absent' | 'late' | 'excused' | 'holiday';

/** Fields exposed by the AttendanceRecord API response. */
export interface AttendanceRecordItem {
  id: number;
  record_name: string | null;
  attendance_id: number | null;
  student_id: number | null;
  status: AttendanceRecordStatus | string | null;
}

export interface AttendanceRecordListResponse {
  items: AttendanceRecordItem[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface AttendanceRecordFormValues {
  record_name: string;
  attendance_id: string;
  student_id: string;
  status: AttendanceRecordStatus;
  note: string;
  recorded_by: string;
}

/** Partial update fields supported without overwriting hidden note metadata. */
export interface AttendanceRecordUpdateValues {
  record_name: string;
  status: AttendanceRecordStatus;
}

export interface AttendanceRecordReferenceOptions {
  attendance: import('./attendance').AttendanceRecord[];
  students: import('./student').StudentRecord[];
}

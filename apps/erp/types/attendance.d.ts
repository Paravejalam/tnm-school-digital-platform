export type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused' | 'holiday';

export interface AttendanceRecord {
  id: number;
  attendance_date: string | null;
  academic_session_id: number | null;
  class_id: number | null;
  section_id: number | null;
  student_id: number | null;
  status: AttendanceStatus | string | null;
  remarks?: string | null;
  marked_by?: number | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface AttendanceListResponse {
  items: AttendanceRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface AttendanceFormValues {
  attendance_date: string;
  academic_session_id: string;
  class_id: string;
  section_id: string;
  student_id: string;
  status: AttendanceStatus;
  remarks: string;
  marked_by: string;
}

/** Fields safe to send during edit because the backend read response exposes them. */
export interface AttendanceUpdateValues {
  attendance_date: string;
  academic_session_id: string;
  class_id: string;
  section_id: string;
  student_id: string;
  status: AttendanceStatus;
}

export interface AttendanceReferenceOptions {
  sessions: import('./academicSession').AcademicSessionRecord[];
  classes: import('./academicClass').AcademicClassRecord[];
  sections: import('./section').SectionRecord[];
  students: import('./student').StudentRecord[];
}

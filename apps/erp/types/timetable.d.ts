export type TimetableStatus = 'active' | 'inactive' | 'draft';

export interface TimetableRecord {
  id: number;
  timetable_name: string | null;
  academic_session_id: number | null;
  class_id: number | null;
  section_id: number | null;
  subject_id: number | null;
  teacher_id: number | null;
  status: TimetableStatus | string | null;
}

export interface TimetableListResponse {
  items: TimetableRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface TimetableFormValues {
  timetable_name: string;
  academic_session_id: string;
  class_id: string;
  section_id: string;
  subject_id: string;
  teacher_id: string;
  status: TimetableStatus;
}

export interface TimetableAcademicSessionOption {
  id: number;
  session_name: string | null;
  status: string | null;
}

export interface TimetableClassOption {
  id: number;
  class_name: string | null;
  code: string | null;
  academic_session_id: number | null;
  status: string | null;
}

export interface TimetableSectionOption {
  id: number;
  section_name: string | null;
  code: string | null;
  class_id: number | null;
  status: string | null;
}

export interface TimetableSubjectOption {
  id: number;
  subject_name: string | null;
  code: string | null;
  section_id: number | null;
  status: string | null;
}

export interface TimetableTeacherOption {
  id: number;
  employee_id: string | null;
  first_name: string | null;
  last_name: string | null;
  status: string | null;
}

export interface TimetableReferenceData {
  academicSessions: TimetableAcademicSessionOption[];
  classes: TimetableClassOption[];
  sections: TimetableSectionOption[];
  subjects: TimetableSubjectOption[];
  teachers: TimetableTeacherOption[];
}

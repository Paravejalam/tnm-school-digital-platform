export type AcademicClassStatus = 'active' | 'inactive';

export interface AcademicClassRecord {
  id: number;
  class_name: string | null;
  code?: string | null;
  academic_session_id: number | null;
  status: AcademicClassStatus | string | null;
  grade_level?: number | null;
}

export interface AcademicClassListResponse {
  items: AcademicClassRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface AcademicClassFormValues {
  class_name: string;
  academic_session_id: string;
  grade_level: string;
  status: AcademicClassStatus;
}

export interface AcademicClassUpdateValues {
  class_name: string;
  academic_session_id: string;
  status: AcademicClassStatus;
}

export interface AcademicClassSessionOption {
  id: number;
  session_name: string | null;
  status: string | null;
}

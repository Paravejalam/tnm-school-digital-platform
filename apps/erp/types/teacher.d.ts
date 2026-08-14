export type TeacherStatus = 'active' | 'inactive';

export interface TeacherRecord {
  id: number;
  employee_id: string | null;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  department: string | null;
  designation: string | null;
  status: TeacherStatus | string | null;
}

export interface TeacherListResponse {
  items: TeacherRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface TeacherFormValues {
  employee_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  department: string;
  designation: string;
  status: TeacherStatus;
}

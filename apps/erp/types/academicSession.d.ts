export type AcademicSessionStatus = 'active' | 'inactive' | 'archived';

/** Fields intentionally exposed by the Academic Session read response. */
export interface AcademicSessionRecord {
  id: number;
  session_name: string | null;
  status: AcademicSessionStatus | string | null;
}

export interface AcademicSessionListResponse {
  items: AcademicSessionRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface AcademicSessionCreateValues {
  session_name: string;
  start_date: string;
  end_date: string;
  status: AcademicSessionStatus;
  is_current: boolean;
}

export interface AcademicSessionUpdateValues {
  session_name: string;
  status: AcademicSessionStatus;
}

export interface AcademicSessionFormValues extends AcademicSessionCreateValues {}

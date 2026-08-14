export type SubjectStatus = 'active' | 'inactive';

export interface SubjectRecord {
  id: number;
  subject_name: string | null;
  code: string | null;
  section_id: number | null;
  status: SubjectStatus | string | null;
}

export interface SubjectListResponse {
  items: SubjectRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface SubjectFormValues {
  subject_name: string;
  code: string;
  section_id: string;
  status: SubjectStatus;
}

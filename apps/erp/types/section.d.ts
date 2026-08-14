export type SectionStatus = 'active' | 'inactive';

export interface SectionRecord {
  id: number;
  section_name: string | null;
  code: string | null;
  class_id: number | null;
  status: SectionStatus | string | null;
  capacity?: number | null;
}

export interface SectionListResponse {
  items: SectionRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

export interface SectionFormValues {
  section_name: string;
  class_id: string;
  capacity: string;
  status: SectionStatus;
}

export interface SectionUpdateValues {
  section_name: string;
  class_id: string;
  status: SectionStatus;
}

export interface SectionClassOption {
  id: number;
  class_name: string | null;
  academic_session_id: number | null;
  status: string | null;
}

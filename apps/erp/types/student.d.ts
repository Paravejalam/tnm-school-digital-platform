export type StudentStatus = 'active' | 'inactive' | 'graduated' | 'transferred' | 'withdrawn';
export type StudentGender = 'male' | 'female' | 'other' | 'prefer_not_to_say';

/** Fields intentionally exposed by the Student read response. */
export interface StudentRecord {
  id: number;
  admission_number: string | null;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  class_name: string | null;
  section: string | null;
  status: StudentStatus | string | null;
}

export interface StudentListResponse {
  items: StudentRecord[];
  pagination: { total: number; page: number; per_page: number };
}

/** Creation supports the schema's optional enrollment and profile references. */
export interface StudentCreateValues {
  admission_number: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  class_name: string;
  section: string;
  status: StudentStatus;
  roll_number: string;
  date_of_birth: string;
  gender: StudentGender | '';
  academic_session_id: string;
  class_id: string;
  section_id: string;
}

/** Updates intentionally contain only fields exposed by the read contract. */
export interface StudentUpdateValues {
  admission_number: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  class_name: string;
  section: string;
  status: StudentStatus;
}

export interface StudentFormValues extends StudentCreateValues {}

export interface StudentSessionOption {
  id: number;
  session_name: string | null;
  status: string | null;
}

export interface StudentClassOption {
  id: number;
  class_name: string | null;
  academic_session_id: number | null;
  status: string | null;
}

export interface StudentSectionOption {
  id: number;
  section_name: string | null;
  class_id: number | null;
  status: string | null;
}

export interface StudentReferenceOptions {
  sessions: StudentSessionOption[];
  classes: StudentClassOption[];
  sections: StudentSectionOption[];
}

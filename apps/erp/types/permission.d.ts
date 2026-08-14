export interface PermissionRecord {
  id: number;
  name: string | null;
  slug: string | null;
  module: string | null;
  description: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface PermissionListResponse {
  items: PermissionRecord[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
  };
}

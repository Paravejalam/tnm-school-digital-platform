export interface UserProfile {
  id: number;
  name: string;
  email: string;
}

export interface AuthResponse {
  user: UserProfile | null;
  token: string;
  refresh_token: string;
}

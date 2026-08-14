export interface FeatureItem {
  eyebrow: string;
  title: string;
  description: string;
}

export interface LeadershipItem {
  name: string;
  role: string;
  description: string;
}

export interface NoticeItem {
  title: string;
  date: string;
  description: string;
}

export interface GalleryItem {
  title: string;
  description: string;
  imageAvailable: boolean;
}

export interface AdmissionStep {
  number: string;
  title: string;
  description: string;
}

export interface PublicContent {
  hero: {
    eyebrow: string;
    title: string;
    description: string;
  };
  introduction: {
    eyebrow: string;
    title: string;
    paragraphs: string[];
  };
  highlights: FeatureItem[];
  academics: FeatureItem[];
  leadership: LeadershipItem[];
  notices: NoticeItem[];
  gallery: GalleryItem[];
  admissionSteps: AdmissionStep[];
}

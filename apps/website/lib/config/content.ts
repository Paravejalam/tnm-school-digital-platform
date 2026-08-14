import type { PublicContent } from '../../types/content';

const unavailable = 'To be provided by the school';

export const publicContent: PublicContent = {
  hero: {
    eyebrow: 'T.N. Memorial Public School',
    title: 'A thoughtful place to learn, belong, and grow.',
    description: 'Explore the school’s public information, academic direction, admissions guidance, and community updates in one welcoming place.',
  },
  introduction: {
    eyebrow: 'Welcome',
    title: 'A school story shaped by its community.',
    paragraphs: [
      'T.N. Memorial Public School is preparing a clear and accessible public home for students, families, and the wider school community.',
      'Verified information about the school’s history, programmes, leadership, and facilities will be published here as it is approved by the school.',
    ],
  },
  highlights: [
    {
      eyebrow: 'Learning',
      title: 'A clear academic foundation',
      description: 'Academic information will be organised here so families can understand the school’s learning journey.',
    },
    {
      eyebrow: 'Community',
      title: 'A welcoming school experience',
      description: 'The public website is designed to make important school information easier to find and understand.',
    },
    {
      eyebrow: 'Connection',
      title: 'An open line for families',
      description: 'Admissions and contact guidance will be kept current as the school confirms its public information.',
    },
  ],
  academics: [
    {
      eyebrow: '01',
      title: 'Academic classes',
      description: 'Information about classes and grade groupings will be published after public content approval.',
    },
    {
      eyebrow: '02',
      title: 'Subjects and learning',
      description: 'The school’s subject and learning information will be presented in a concise family-friendly format.',
    },
    {
      eyebrow: '03',
      title: 'School calendar',
      description: 'Important academic dates and holidays will be shared when an approved public calendar is available.',
    },
  ],
  leadership: [
    {
      name: unavailable,
      role: 'Leadership profile',
      description: 'Leadership information will be published here after the school approves the names, roles, and biographies for public display.',
    },
  ],
  notices: [],
  gallery: [],
  admissionSteps: [
    {
      number: '01',
      title: 'Review the guidance',
      description: 'Admission requirements and the current process will be published here once confirmed by the school.',
    },
    {
      number: '02',
      title: 'Prepare your enquiry',
      description: 'Families will be able to review the information needed for an informed admission enquiry.',
    },
    {
      number: '03',
      title: 'Speak with the school',
      description: 'Approved contact details and enquiry channels will be listed here when available.',
    },
  ],
};

export const publicContact = {
  email: unavailable,
  phone: unavailable,
  address: unavailable,
  officeHours: unavailable,
};

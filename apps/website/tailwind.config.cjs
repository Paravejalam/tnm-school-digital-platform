module.exports = {
  content: ['./app/**/*.{ts,tsx}', './components/**/*.{ts,tsx}', './lib/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        ink: '#172033',
        navy: '#16324f',
        teal: '#0f766e',
        sand: '#f6f2e9',
        gold: '#d4a72c',
      },
      boxShadow: {
        soft: '0 18px 50px -28px rgba(23, 32, 51, 0.35)',
      },
    },
  },
  plugins: [],
};

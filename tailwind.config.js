/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        crm: {
          primary: '#3B82F6',
          'primary-hover': '#2563EB',
          sidebar: '#1E293B',
          'sidebar-hover': '#334155',
          page: '#F8FAFC',
          card: '#FFFFFF',
          border: '#E2E8F0',
          heading: '#1E293B',
          body: '#334155',
          muted: '#64748B',
          success: '#22C55E',
          warning: '#F59E0B',
          danger: '#EF4444',
          info: '#0EA5E9',
          'tab-bg': '#EFF6FF',
          'tab-text': '#2563EB',
        },
        settl: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
          950: '#172554',
        },
      },
      fontFamily: {
        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
    },
  },
  plugins: [],
};

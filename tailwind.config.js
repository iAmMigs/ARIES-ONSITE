/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        'feu-green': {
          50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac',
          400: '#4ade80', 500: '#40916c', 600: '#2d6a4f', 700: '#1a4d2e',
          800: '#166534', 900: '#14532d',
        },
        'feu-gold': {
          50: '#fefce8', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d',
          400: '#f0c14b', 500: '#d4a017', 600: '#b8860b', 700: '#a16207',
          800: '#854d0e', 900: '#713f12',
        },
        'primary': '#1a4d2e',
        'primary-foreground': '#ffffff',
        'accent': '#d4a017',
        'background': '#f9fafb',
        'foreground': '#111827',
        'muted': '#6b7280',
        'muted-foreground': '#9ca3af',
        'border': '#e5e7eb',
        'success': '#22c55e',
        'warning': '#f59e0b',
        'danger': '#ef4444',
        'info': '#3b82f6',
      },
      fontFamily: {
        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
      },
      boxShadow: {
        'feu': '0 10px 40px -10px rgba(26, 77, 46, 0.3)',
        'gold': '0 10px 40px -10px rgba(212, 160, 23, 0.3)',
      }
    }
  },
  plugins: [],
}
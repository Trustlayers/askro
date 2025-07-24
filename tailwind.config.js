/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        'askro': {
          'primary': '#14b8a6',
          'secondary': '#6366f1',
          'accent': '#f43f5e',
          'neutral': '#1f2937',
          'base-100': '#ffffff',
          'info': '#3b82f6',
          'success': '#22c55e',
          'warning': '#f97316',
          'error': '#ef4444',
        }
      },
      fontFamily: {
        'askro': ['Inter', 'system-ui', 'sans-serif'],
      },
      container: {
        center: true,
        padding: {
          DEFAULT: '1rem',
          sm: '2rem',
          lg: '4rem',
          xl: '5rem',
          '2xl': '6rem',
        },
      },
    },
  },
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: [
      {
        askrotheme: {
          "primary": "#14b8a6",    // Teal-500
          "secondary": "#6366f1", // Indigo-500
          "accent": "#f43f5e",     // Rose-500
          "neutral": "#1f2937",    // Gray-800
          "base-100": "#ffffff",   // White
          "info": "#3b82f6",      // Blue-500
          "success": "#22c55e",   // Green-500
          "warning": "#f97316",   // Orange-500
          "error": "#ef4444",      // Red-500
        },
      },
      "light",
      "dark",
    ],
    base: true,
    styled: true,
    utils: true,
    prefix: "",
    logs: true,
    themeRoot: ":root",
  },
}

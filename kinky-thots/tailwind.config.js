/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './*.html',
    './*.php',
    './php-pages/**/*.php',
    './.src/**/*.{js,css}',
  ],
  theme: {
    extend: {
      colors: {
        // Brand colors from existing CSS
        primary: {
          pink: '#f805a7',
          cyan: '#0bd0f3',
        },
        dark: {
          900: '#0a0a0a',
          800: '#111111',
          700: '#1a1a1a',
          600: '#222222',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};

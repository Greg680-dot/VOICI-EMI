/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          50: '#F0F4F8',
          100: '#D9E2EC',
          200: '#BCCCDC',
          300: '#9FB3C8',
          400: '#627D98',
          500: '#334E68',
          600: '#243B53',
          700: '#1B3A5C', // Identité principale
          800: '#142A43',
          900: '#0E1D2F',
          DEFAULT: '#1B3A5C',
        },
        amber: {
          50: '#FDF8F2',
          100: '#FBECDB',
          200: '#F7D6B3',
          300: '#F1BA84',
          400: '#EB9E4A',
          500: '#E08E28', // Accent signature
          600: '#B86C16',
          700: '#8C4D0A',
          DEFAULT: '#E08E28',
        },
        surface: {
          warm: '#FAF7F2', // Fond chaud signature
          card: '#FFFFFF',
          muted: '#F3EFEA',
          border: '#E8E2D9',
        },
        ink: {
          base: '#26313E', // Texte principal
          muted: '#526071',
          light: '#7F8E9D',
        }
      },
      fontFamily: {
        serif: ['Georgia', 'Cambria', '"Times New Roman"', 'Times', 'serif'],
        sans: ['system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
      },
      boxShadow: {
        subtle: '0 1px 3px 0 rgba(27, 58, 92, 0.04), 0 1px 2px 0 rgba(27, 58, 92, 0.02)',
        card: '0 4px 6px -1px rgba(27, 58, 92, 0.05), 0 2px 4px -1px rgba(27, 58, 92, 0.03)',
        floating: '0 10px 15px -3px rgba(27, 58, 92, 0.08), 0 4px 6px -2px rgba(27, 58, 92, 0.04)',
      }
    },
  },
  plugins: [],
}

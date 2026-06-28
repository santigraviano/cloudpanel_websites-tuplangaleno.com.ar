/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#00559B',
          dark: '#004D88',
          teal: '#009487',
        },
        plan: {
          azul: '#004D88',
          plata: '#95908D',
          oro: '#DB9923',
          'oro-dark': '#AF8438',
        },
        gray: {
          body: '#656565',
          nav: '#555555',
          dark: '#32373c',
        },
        whatsapp: '#25d366',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        slab: ['"Roboto Slab"', 'serif'],
      },
    },
  },
  plugins: [],
};

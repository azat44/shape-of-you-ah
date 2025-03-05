const plugin = require('tailwindcss/plugin');

module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.{html,twig}",
    "./public/js/**/*.js",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        themeOneText: {
          DEFAULT: '#92400e',
          light: '#b45309',
          dark: '#7c2d12'
        },
        themeTwoText: {
          DEFAULT: '#1e3a8a',
          light: '#1d4ed8',
          dark: '#172554',
          accent: '#3b82f6'
        },
        themeOneBg: {
          DEFAULT: '#fffbeb',
          alt: '#fef3c7'
        },
        themeTwoBg: {
          DEFAULT: '#eff6ff',
          alt: '#dbeafe'
        }
      }
    }
  },
  plugins: [
    plugin(function({ addVariant }) {
      addVariant('theme-one', '.theme-one &');
      addVariant('theme-two', '.theme-two &');
    }),
  ],
  safelist: [
    'theme-one',
    'theme-two',
    'dark',
    'light',
    'text-themeOneText',
    'text-themeOneText-light',
    'text-themeOneText-dark',
    'text-themeTwoText',
    'text-themeTwoText-light',
    'text-themeTwoText-dark',
    'text-themeTwoText-accent',
    'bg-themeOneBg',
    'bg-themeOneBg-alt',
    'bg-themeTwoBg',
    'bg-themeTwoBg-alt',
    'text-white',
    'text-black',
    'text-gray-800'
  ]
};
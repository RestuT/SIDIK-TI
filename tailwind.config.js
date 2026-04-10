/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./api/**/*.php", "./api/includes/**/*.php", "./api/public/**/*.js"],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "primary": "#3525cd",
        "primary-container": "#4f46e5",
        "background": "#f7f9fb",
        "surface": "#ffffff",
        "on-surface": "#191c1e",
        "on-surface-variant": "#464555",
        "outline": "#777587",
        "outline-variant": "#c7c4d8",
        "error": "#ba1a1a",
        "error-container": "#ffdad6",
        "secondary": "#0051d5",
        "secondary-container": "#316bf3",
        "tertiary": "#005338",
        "tertiary-container": "#006e4b",
        "surface-container-lowest": "#ffffff",
        "surface-container-low": "#f2f4f6",
        "surface-container": "#eceef0",
        "surface-container-high": "#e6e8ea",
        "surface-container-highest": "#e0e3e5",
      },
      fontFamily: {
        "headline": ["Plus Jakarta Sans", "sans-serif"],
        "body": ["Inter", "sans-serif"],
      },
      borderRadius: {
        "DEFAULT": "1rem",
        "lg": "2rem",
        "xl": "3rem",
        "full": "9999px",
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries'),
  ],
}

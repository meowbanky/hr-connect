/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./**/*.{html,js,php}"],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#1919e6",
        "background-light": "#f6f6f8",
        "background-dark": "#111121",
        "text-main": "#0e0e1b",
        "text-secondary": "#4e4e97",
      },
      fontFamily: {
        display: ["Inter", "sans-serif"],
      },
    },
  },
  plugins: [],
};

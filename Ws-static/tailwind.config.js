/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.{html,js,php}",
    "./**/*.{html,js,php}"
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          red: "#E5391C",
          redDark: "#C92F17",
          charcoal: "#1F1F1F",
          charcoalSoft: "#4A4A4A",
          grey: "#F2F2F2",
          border: "#D9D9D9",
        },
      },
    },
    // Make ALL border-radius utilities resolve to 0px
    borderRadius: {
      none: "0px",
      sm: "0px",
      DEFAULT: "0px",
      md: "0px",
      lg: "0px",
      xl: "0px",
      "2xl": "0px",
      "3xl": "0px",
      full: "0px",
    },
  },
  plugins: [],
};

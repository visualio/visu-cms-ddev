const screens = {
  mobile: 480,
  "lg-mobile": 640,
  tablet: 768,
  "lg-tablet": 1024,
  desktop: 1280,
  "lg-desktop": 1441,
  xs: 360,
  xsm: 380,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
  "2xl": 1440,
}

const pixels = [
  1, 2, 3, 4, 5, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 38, 32, 34, 36, 40, 42, 44, 46,
  48, 50, 54, 56, 58, 60, 64, 68, 70, 74, 72, 76, 80, 86, 94, 96, 124, 128, 146, 172, 176, 192, 196, 200, 256,
]

const customColors = {
  white: {
    DEFAULT: "#FFFFFF",
    dark: "#e1e1e1"
  },
  black: "#000000",
  blue: {
    DEFAULT: "#1b8bea",
    light: "#41bff7",
    lighter: '#84c7ff',
    lightest: '#a9d5ff',
    hover:"#1b8bea",
  },
  grey: {
    DEFAULT: "#1b1b1b",
    light: "#333333"
  },
  "nav-hover": "rgba(10, 88, 165, .4)"
}

module.exports = {
  content: [
    "./app/modules/Front/**/*.latte",
    "./app/modules/Front/**/*.js",
    "./dev/front/js/**/*.js",
  ],
  theme: {
    fontFamily: {
      barlow: ["barlow, sans-serif"],
    },
    fontSize: {
      ...defaultFontSizes(),
      "12px": [pxToRem(12), "normal"],
      "14px": [pxToRem(14), "normal"],
      "16px": [pxToRem(16), "normal"],
      "18px": [pxToRem(18), "normal"],
      "24px": [pxToRem(24), "normal"],
      "32px": [pxToRem(32), "normal"],
      "48px": [pxToRem(48), "normal"],
      "56px": [pxToRem(56), "normal"],
      "64px": [pxToRem(64), "normal"],
      "128px": [pxToRem(128), "normal"],
      "h1": ["5.3rem", "110%"],
      "h3": ["2.75rem", "120%"],
      "h4": ["2.1875rem", "120%"],
      "h5": ["2rem", "106.25%"],
      "lg": ["1.375rem", "140%", ".22px"],
      "xl": ["1.75rem", "140%"],
      "base-desktop": ["1.125rem", "140%", ".18px"],
    },
    screens: {
      ...defaultScreens(),
      ...Object.fromEntries(
        Object.entries(screens).map(([key, pixels]) => [key, pxToRem(pixels)])
      ),
    },
    spacing: {
      ...defaultSpacing(),
      ...createSizeObject(pixels),
      0: "0px",
    },
    extend: {
      colors: {
        ...customColors,
      },
      aspectRatio: {
        '1/1': '1 / 1',
        '1/2': '1 / 2',
        '2/1': '2 / 1',
        '2/3': '2 / 3',
        '3/2': '3 / 2',
        '4/3': '4 / 3',
        '16/9': '16 / 9'
      },
    }
  },
  corePlugins: {
    container: false,
  },
  plugins: [require("@tailwindcss/aspect-ratio")],
}

function pxToRem(pixels) {
  return `${pixels / 16}rem`
}

function createSizeObject(values) {
  const sizes = {}

  values.forEach((value) => {
    sizes[`${value}px`] = `${value}px`
  })

  return sizes
}

function defaultFontSizes() {
  // Vrátí výchozí velikosti písma z Tailwindu
  return require('tailwindcss/defaultTheme').fontSize;
}

function defaultScreens() {
  // Vrátí výchozí breakpoints z Tailwindu
  return require('tailwindcss/defaultTheme').screens;
}

function defaultSpacing() {
  // Vrátí výchozí spacing z Tailwindu
  return require('tailwindcss/defaultTheme').spacing;
}
import liveReload from "vite-plugin-live-reload"
import dotenv from 'dotenv';

dotenv.config();

const {resolve} = require("path")

export default {
  define: {
    __PROJECT_NAME__: JSON.stringify(process.env.PROJECT_NAME),
    ["process.env"]: `"${process.env.APP_ENV}"`, // needed by uppy
    ["process.browser"]: true, // needed by uikit-sortable-tree
  },
  plugins: [
    liveReload(`app/modules/${capitalize(process.env.APP_MODULE)}/**/*.latte`, {
      alwaysReload: true,
      root: resolve(__dirname),
    }),
  ],
  resolve: {
    alias: {
      "tailwind.config.js": resolve(__dirname, "tailwind.config.js"),
    },
  },
  optimizeDeps: {
    include: ["tailwind.config.js"],
  },
  // config
  root: `dev/${process.env.APP_MODULE}`,
  publicDir: `public`, // relative to the root
  base:
    process.env.APP_ENV === "development"
      ? `/dev/${process.env.APP_MODULE}/`
      : `/dist/${process.env.APP_MODULE}/`,
  mode: process.env.APP_ENV,
  build: {
    // output dir for production build
    outDir: resolve(__dirname, `www/dist/${process.env.APP_MODULE}`),
    emptyOutDir: true,

    // emit manifest so PHP can find the hashed files
    manifest: true,

    // only targeting browsers with native dynamic import support
    polyfillDynamicImport: false,

    // esbuild target
    target: "esnext",

    // our entry
    rollupOptions: {
      input: resolve(__dirname, `dev/${process.env.APP_MODULE}/js/index.js`),
    },
  },
  server: {
    host: "0.0.0.0",
    port: 3333,
    cors: true,
    // https: {
    //   key: fs.readFileSync('./vite.key'),
    //   cert: fs.readFileSync('./vite.crt'),
    // },
    hmr: {
      // protocol: "wss",
      protocol: "ws",
      host: "localhost",
      clientPort: 3333,
    },
    watch: {
      usePolling: true,
    },
  },
}

function capitalize(word) {
  return word[0].toUpperCase() + word.slice(1).toLowerCase()
}

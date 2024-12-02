const { default: config } = require("./vite.config")

const cssPath = `http://localhost:${config.server.port}${config.base}`

module.exports = {
  plugins: [
    require("postcss-import"),
    require("tailwindcss"),
    require("autoprefixer"),
    process.env.APP_ENV === "development"
      ? require("postcss-url")([
          {
            filter: "**/**/*.*",
            url: (asset) => `${cssPath}/${asset.pathname}`,
          },
        ])
      : null,
  ],
}

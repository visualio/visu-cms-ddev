import "./imports/polyfills.js"
import UIkit from "uikit/dist/js/uikit.js"
import UIkitIcons from "uikit/dist/js/uikit-icons.js"
import { initAll } from "./imports/initilaizers.js"
import { requestSnippets } from "./imports/helpers.js"

import "../css/index.css"

window.requestSnippets = requestSnippets
UIkit.use(UIkitIcons)

document.addEventListener(`DOMContentLoaded`, () => {
  insertNetteFormsScript()
  initAll().then(() => {
    // eslint-disable-next-line no-console
    console.log("All modules successfully loaded")
  })
})

function insertNetteFormsScript() {
  // npm version of nette forms has no named export to use :(
  // fallback to CDN
  const script = document.createElement("script")
  script.src = "https://nette.github.io/resources/js/3/netteForms.min.js"
  document.head.appendChild(script)
}

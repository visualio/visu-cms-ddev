import ImageEditor from "tui-image-editor"
import UIkit from "uikit/dist/js/uikit.js"
import { theme, locale, transparentPixel } from "./ImageUploadEditorSettings.js"

export default function (elements) {
  elements.forEach((el) => {
    const uploadInput = el.querySelector(`input[type="file"]`)
    const textInput = el.querySelector(`input[type="text"]`)
    const image = el.querySelector(`img`)
    const editButton = el.querySelector(`button[data-edit]`)
    const deleteButton = el.querySelector(`button[data-delete]`)
    const thumbnail = el.querySelector(`[data-thubmnail]`)
    const spinner = el.querySelector(`[uk-spinner]`)
    uploadInput.addEventListener(`change`, () => {
      const {
        files: [file],
      } = uploadInput
      if (file) {
        const modal = initModal()
        const reader = new FileReader()
        reader.readAsDataURL(file)
        reader.onload = function () {
          initImageEditor(reader.result, textInput, image, thumbnail, modal)
        }
      }
    })
    editButton.addEventListener("click", () => {
      initImageEditor(image.src, textInput, image, thumbnail)
    })
    deleteButton.addEventListener("click", () => {
      image.src = transparentPixel
      thumbnail.setAttribute("hidden", true)
      textInput.setAttribute("value", "")
    })
    if (textInput.value.startsWith("data:image")) {
      image.src = textInput.value
    }
    spinner && spinner.remove()
  })
}

function initImageEditor(source, input, image, thumbnail, modal = initModal()) {
  const imageEditor = new ImageEditor(document.querySelector("#imageEditor"), {
    includeUI: {
      loadImage: {
        path: source,
        name: "Uploaded image",
      },
      menuBarPosition: "bottom",
      theme,
      locale,
      uiSize: {
        width: "100%",
        height: "70vh",
      },
    },
    cssMaxWidth: 600,
    usageStatistics: false,
    cssMaxHeight: 800,
  })
  window.saveAs = function (blob) {
    const reader = new FileReader()
    reader.readAsDataURL(blob)
    reader.onloadend = function () {
      input.value = reader.result
      image.src = reader.result
      thumbnail.removeAttribute("hidden")
      modal.hide()
    }
  }
  return imageEditor
}

function initModal() {
  return UIkit.modal.dialog(
    "<div id='imageEditor'><div uk-spinner class='uk-padding-large uk-width-1-1 uk-text-center'></div></div>",
    { stack: true }
  )
}

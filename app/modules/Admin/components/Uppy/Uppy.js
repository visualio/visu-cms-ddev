import Uppy from "@uppy/core"
import Tus from "@uppy/tus"
import XHRUpload from "@uppy/xhr-upload"
import DragDrop from "@uppy/drag-drop"
import StatusBar from "@uppy/status-bar"
import Czech from "@uppy/locales/lib/cs_CZ"
import { requestSnippets } from "../../../../../dev/admin/js/imports/helpers"

function createFileName(name, id) {
  const parts = name.split(".")
  const ext = parts.pop()
  const fileName = parts
    .join(".")
    .replace(/[^a-z0-9]/gi, "-")
    .toLowerCase()
  return `${id ? `${id}_` : ""}${fileName}_${Date.now().toString(36)}.${ext}`
}

function initRemoveButtons(element, endpoint) {
  element.querySelectorAll(".remove-file-button").forEach((button) => {
    button.onclick = async () => {
      await requestSnippets({
        endpoint,
        body: button.dataset.name,
        element: button,
      })
      initRemoveButtons(element, endpoint)
    }
  })
}

export default function (elements) {
  elements.forEach((element) => {
    const {
      note,
      uploadEndpoint,
      successEndpoint,
      removeEndpoint,
      allowMultipleUploads,
      id,
      restrictions,
      adapter,
    } = JSON.parse(element.dataset.uppy)

    initRemoveButtons(element, removeEndpoint)
    const input = element.querySelector("textarea")
    new Uppy({
      debug: process.env !== "production",
      autoProceed: true,
      allowMultipleUploads,
      id,
      restrictions: {
        ...restrictions,
        maxNumberOfFiles: allowMultipleUploads ? 10 : 1,
      },
      locale: Czech,
      onBeforeFileAdded: (file) => {
        const name = createFileName(file.name, input.id)
        Object.defineProperty(file.data, 'name', {
          writable: true,
          value: name
        });
        return { ...file, name, meta: { ...file.meta, name } }
      },
    })
      .use(DragDrop, {
        target: element.querySelector(".area"),
        note,
        locale: {
          strings: {
            dropHereOr: `Nahrajte ${
              allowMultipleUploads ? "soubory" : "soubor"
            } přetáhnutím nebo %{browse}`,
            browse: `${allowMultipleUploads ? "je" : "ho"} vyberte`,
          },
        },
      })
      .use(StatusBar, {
        target: element.querySelector(".status"),
      })
      .use(
        adapter === "xhr" ? XHRUpload : Tus,
        adapter === "xhr"
          ? {
            endpoint: uploadEndpoint,
            formData: true,
            fieldName: "files[]",
          }
          : {
            endpoint: uploadEndpoint
          }
      )
      .on("upload-success", async ({ name }) => {
        try {
          await requestSnippets({
            endpoint: successEndpoint,
            method: "POST",
            body: name,
            element,
          })
          initRemoveButtons(element, removeEndpoint)
        } catch (e) {
          console.error(e)
          alert("Soubor se nepodařilo nahrát")
        }
      })
  })
}

import "./ckeditor.js"
import Choices from "choices.js"
import { choicesOptions } from "./settings"
import flatpickr from "flatpickr"
import { Czech } from "flatpickr/dist/l10n/cs"
import { makeGetRequest, toggle } from "./helpers"
import UIkit from "uikit/dist/js/uikit.js"
import initDynamicForm from "../../../../app/modules/Admin/components/DynamicForm/DynamicForm"
import initDataGrid from "../../../../app/modules/Admin/components/DataGrid/DataGrid"
import initFileManager from "../../../../app/modules/Admin/components/FileManager/FileManager"
import initDropZone from "../../../../app/modules/Admin/components/DropZone/DropZone"

export function initCKEditor() {
  // use observer to reduce load on JS engine
  // eg: many instances are used in form multipliers
  window.CKObserver =
    window.CKObserver ||
    new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          window.ClassicEditor.create(entry.target, {
            height: 400,
            simpleUpload: {
              uploadUrl: entry.target.dataset.uploadUrl,
              withCredentials: true,
              headers: {},
            },
            toolbar: {
              items: [
                "heading",
                "|",
                "bold",
                "italic",
                "superscript",
                "subscript",
                "link",
                "bulletedList",
                "numberedList",
                "removeFormat",
                "code",
                "|",
                "indent",
                "outdent",
                "|",
                "imageUpload",
                "blockQuote",
                "insertTable",
                "mediaEmbed",
                "undo",
                "redo",
              ],
            },
            language: "cs",
            image: {
              toolbar: [
                "imageTextAlternative",
                "imageStyle:full",
                "imageStyle:side",
              ],
            },
            table: {
              contentToolbar: ["tableColumn", "tableRow", "mergeTableCells"],
            },
            mediaEmbed: {
              previewsInData: true,
            },
            heading: {
              options: [
                {
                  model: "paragraph",
                  view: "p",
                  title: "Paragraph",
                  class: "ck-heading_paragraph",
                },
                {
                  model: "heading1",
                  view: "h3",
                  title: "Heading 1",
                  class: "ck-heading_heading1",
                },
                {
                  model: "heading2",
                  view: "h4",
                  title: "Heading 2",
                  class: "ck-heading_heading2",
                },
                {
                  model: "heading3",
                  view: {
                    name: "h5",
                    classes: "label",
                  },
                  title: "Label",
                  class: "ck-heading_heading3",
                },
                {
                  model: "heading4",
                  view: {
                    name: "span",
                    classes: "btn-wysiwyg",
                  },
                  title: "Tlačítko",
                  class: "ck-heading_heading4",
                },
              ],
            },
          })
            .then((editor) => {
              editor.model.document.on("change:data", () => {
                editor.updateSourceElement()
              })
            })
            .catch((err) => console.error(err.stack))
          window.CKObserver.unobserve(entry.target)
        }
      })
    })
  window.CKObserver.disconnect()
  document.querySelectorAll(".js-wysiwyg").forEach((field) => {
    window.CKObserver.observe(field)
  })
}

export function initLinkLikeElements() {
  document.querySelectorAll("[data-href]").forEach((element) =>
    element.addEventListener("click", (e) => {
      e.preventDefault()
      const { href } = element.dataset
      location.href = href
    })
  )
}

export function initChoices(documentOrElement = document) {
  window.choicesInstances = window.choicesInstances || {}
  documentOrElement.querySelectorAll(`.js-select`).forEach((element) => {
    createChoicesInstance(element)
  })
}

export function createChoicesInstance(element) {
  const order = element.dataset.order ? element.dataset.order.split(",") : null
  const choices = new Choices(element, {
    ...choicesOptions(element),
    sorter(a, b) {
      if (order) {
        return order.indexOf(a.value) - order.indexOf(b.value)
      }
      return b.label.length - a.label.length
    },
    callbackOnInit() {
      const list = this.itemList.element
      UIkit.sortable(list, {})
      UIkit.util.on(list, "moved", ({ target: { children } }) => {
        const values = [...children].map((el) => el.dataset.value)
        this.removeActiveItems()
        this.setChoiceByValue(values)
      })
    },
  })
  if (element.id) window.choicesInstances[element.id] = choices
  return choices
}

export function initFlatPicker() {
  flatpickr(`.js-date`, {
    locale: Czech,
    altInput: true,
    altFormat: `d.m.Y`,
    dateFormat: `Y-m-d`,
    defaultDate: new Date(),
  })
}

export function initTogglers() {
  const togglers = document.querySelectorAll(`[data-toggler]`)
  togglers.forEach((toggler) =>
    toggler.addEventListener(`change`, () => toggle(togglers))
  )
  toggle(togglers)
}

export function initSortable() {
  UIkit.util.on(
    ".js-sortable",
    "moved",
    ({
       target: {
         children,
         dataset: { callback, param = `idList` },
       },
     }) => {
      const idList = [...children].map((el) => el.id)
      makeGetRequest(`${callback}&${param}=${idList}`)
    }
  )
}

export function initNetteAjax(documentOrElement = document) {
  ;[...documentOrElement.querySelectorAll(".ajax, [data-ajax]")].forEach(
    (element) => {
      if (element instanceof HTMLFormElement) {
        element.onsubmit = async (e) => {
          e.preventDefault()
          const formEntriesModified = await Promise.all(
            [...new FormData(element).entries()].map(formEntryWithoutBase64)
          )
          const body = new FormData()
          for (const [key, value] of formEntriesModified) {
            body.append(key, value)
          }
          await window.requestSnippets({
            endpoint: element.action,
            method: "POST",
            body,
            element,
          })
          element.reset()
        }
      }
      if (element instanceof HTMLAnchorElement) {
        element.onclick = async (e) => {
          e.preventDefault()
          await window.requestSnippets({
            endpoint: element.href,
            method: "GET",
            element,
          })
        }
      }
      if (
        element instanceof HTMLButtonElement ||
        (element instanceof HTMLInputElement && element.type === "button")
      ) {
        element.onclick = async (e) => {
          e.preventDefault()
          await window.requestSnippets({
            endpoint: element.dataset.ajax,
            method: "GET",
            element,
          })
        }
      }
      if (element.hasAttribute("uk-sortable")) {
        UIkit.util.on(
          element,
          "moved",
          async ({
                   target: {
                     children,
                     dataset: { callback, param = `idList` },
                   },
                 }) => {
            const idList = [...children].map((el) => el.id)
            await window.requestSnippets({
              endpoint: callback,
              body: { [param]: idList },
              element,
            })
          }
        )
      }
    }
  )
}

async function formEntryWithoutBase64([name, value]) {
  try {
    if (!value.startsWith("data:image/png;base64")) {
      return [name, value]
    }
    const res = await fetch(value)
    const blob = await res.blob()
    return [name, new File([blob], name, { type: "image/png" })]
  } catch (e) {
    return [name, value]
  }
}

export async function initUppy(documentOrElement = document) {
  const uppyElements = documentOrElement.querySelectorAll("[data-uppy]")
  if (uppyElements.length > 0) {
    const { default: init } = await import(
      "../../../../app/modules/Admin/components/Uppy/Uppy.js"
      )
    init(uppyElements)
  }
}

export async function initImageEditors(documentOrElement = document) {
  const imageEditors = documentOrElement.querySelectorAll(`.image-editor`)
  if (imageEditors.length > 0) {
    const { default: init } = await import(
      "../../../../app/modules/Admin/components/ImageUploadEditor/ImageUploadEditor.js"
      )
    init(imageEditors)
  }
}

export async function initSortableTrees(documentOrElement = document) {
  const sortableTrees = documentOrElement.querySelectorAll("[data-uk-tree]")
  if (sortableTrees.length > 0) {
    const { default: init } = await import(
      "../../../../app/modules/Admin/components/SortableTree/SortableTree.js"
      )
    init(sortableTrees)
  }
}

export async function initAll(documentOrElement = document) {
  if (window.Nette) {
    documentOrElement.querySelectorAll("form").forEach((form) => {
      window.Nette.initForm(form)
    })
  }
  initChoices(documentOrElement)
  initCKEditor(documentOrElement)
  initFlatPicker(documentOrElement)
  initLinkLikeElements(documentOrElement)
  initSortable(documentOrElement)
  initTogglers(documentOrElement)
  initNetteAjax(documentOrElement)
  initDataGrid(documentOrElement)
  initFileManager(documentOrElement)
  initDropZone(documentOrElement)
  initDynamicForm(documentOrElement)
  await initImageEditors(documentOrElement)
  await initSortableTrees(documentOrElement)
  await initUppy(documentOrElement)
}

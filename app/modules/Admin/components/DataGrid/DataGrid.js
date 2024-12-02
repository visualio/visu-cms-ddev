import UIkit from "uikit/dist/js/uikit"
import flatpickr from "flatpickr"
import { Czech } from "flatpickr/dist/l10n/cs"
import { makeGetRequest } from "../../../../../dev/admin/js/imports/helpers"

export default function () {
  // adjust styling, enable multi action
  const groupActions = document.querySelector(`.row-group-actions`)
  if (groupActions) {
    groupActions
      .closest("table")
      .querySelector("tfoot")
      .appendChild(groupActions)
    const selectBoxes = groupActions.querySelectorAll(`select`)
    const submit = groupActions.querySelector(`input[type=submit]`)
    const formElements = groupActions.firstElementChild.children
    formElements.forEach((child) => {
      child.style.display = ``
    })
    selectBoxes.forEach((selectBox, index) => {
      if (index === 0) selectBox.disabled = false
      selectBox.className = "uk-select uk-width-small uk-margin-right"
    })
    if (submit) {
      submit.className = "uk-button uk-button-secondary"
    }
    document.querySelectorAll("[data-check]").forEach((checkbox) => {
      checkbox.classList.add(`uk-checkbox`)
    })
  }

  // icons
  document.querySelectorAll(".data-grid-icon").forEach((icon) => {
    icon.setAttribute("uk-icon", icon.classList[1])
  })

  // confirm
  const confirmators = document.querySelectorAll(`[data-datagrid-confirm]`)
  confirmators.forEach((element) =>
    element.addEventListener(`click`, (e) => {
      e.preventDefault()
      UIkit.modal
        .confirm(element.dataset.datagridConfirm)
        .then(() => (location.href = element.href))
    })
  )

  // sortable
  const sortables = document.querySelectorAll(`[data-sortable]`)
  sortables.forEach((element) => {
    UIkit.sortable(element, {
      handle: ".js-sortable-handle",
    })
    UIkit.util.on(
      "[data-sortable]",
      "moved",
      ({
        target: {
          children,
          dataset: { sortableUrl, param = `idList` },
        },
      }) => {
        const idList = [...children].map((el) => el.dataset.id)
        makeGetRequest(`${sortableUrl}&${param}=${idList}`)
      }
    )
  })

  // datepicker
  document.querySelectorAll('[data-provide="datepicker"]').forEach((elem) => {
    elem.classList.add(`uk-input`)
    elem.removeAttribute("data-date-format")
    flatpickr(elem, {
      locale: Czech,
      dateFormat: "d-m-Y",
    })
  })

  // auto-sumbit inputs
  document.querySelectorAll("[data-autosubmit]").forEach((elem) => {
    elem.addEventListener("change", () => {
      elem.closest("form").submit()
    })
  })
}

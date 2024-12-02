import UIkit from "uikit/dist/js/uikit.js"

export default function initDynamicForm(documentOrElement = document) {
  documentOrElement
    .querySelectorAll(".dynamic-form .multiplier-head ul")
    .forEach((list) => {
      // nasty multiplier fix start
      setTimeout(() => {
        const activeItem = list.querySelector('li.uk-active > a')
        if (activeItem) activeItem.click()
      }, 0)
      // nasty fix end
      UIkit.sortable(list, {
        handle: ".uk-sortable-handle",
      })
      UIkit.util.on(list, "moved", (e) => {
        const newOrder = [...e.target.children].map(
          (item) => item.dataset.sortableKey
        )
        const fieldsets = e.target.parentNode.nextElementSibling
        ;[...fieldsets.children]
          .sort((a, b) =>
            newOrder.indexOf(a.dataset.sortableKey) >
            newOrder.indexOf(b.dataset.sortableKey)
              ? 1
              : -1
          )
          .forEach((node) => fieldsets.appendChild(node))
      })
    })
}

import SortableTree from "uikit-sortable-tree/dist/uikit-sortable-tree"
import UIkit from "uikit/dist/js/uikit.js"
import {
  notificationFailure,
  notificationSuccess,
} from "../../../../../dev/admin/js/imports/settings"

export default function (elements) {
  elements.forEach((element) => {
    const settings = JSON.parse(element.dataset.ukTree)
    SortableTree({
      ...settings,
      element,
      onSave: (data) => {
        element.classList.add("loading")
        fetch(settings.urls.save, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(data),
        })
          .then((res) => {
            res.json().then(({ message }) => {
              if (res.status > 300) {
                element.classList.remove("loading")
                UIkit.notification({ ...notificationFailure, message })
              } else {
                UIkit.notification({ ...notificationSuccess, message })
                setTimeout(() => {
                  location.reload()
                }, 2000)
              }
            })
          })
          .catch((err) => {
            console.error(err)
            UIkit.notification(notificationFailure)
          })
      },
      onEdit: (id) => {
        location.href = `${settings.urls.edit}/${id}`
      },
      height: "calc(100vh - 300px)",
      translations: {
        add: "Přidat",
        save: "Uložit",
        empty: "(Prázdné)",
      },
    })
  })
}

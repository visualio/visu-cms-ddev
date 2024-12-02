import { createChoicesInstance, initAll } from "./initilaizers"
import UIkit from "uikit/dist/js/uikit.js"
import { notificationFailure, notificationSuccess } from "./settings"

export function makeGetRequest(url) {
  const req = new XMLHttpRequest()
  req.open("GET", url)
  req.addEventListener("load", () => {
    if (req.readyState === 4 && req.status === 200) {
      return UIkit.notification(notificationSuccess)
    }
    return UIkit.notification(notificationFailure)
  })
  req.addEventListener("error", () => UIkit.notification(notificationFailure))
  req.send()
}

export async function requestSnippets({
  endpoint,
  element = null,
  method = "POST",
  body: requestBody = null,
}) {
  try {
    setLoading()
    const formParent = element
      ? element.closest("form[data-ajax-parent]")
      : null
    let body = {}
    if (formParent && element !== formParent) {
      const formData = new FormData(formParent)
      formData.delete("_do")
      formData.append(
        formParent.dataset.ajaxParent,
        JSON.stringify(requestBody)
      )
      body = formData
    } else {
      body = requestBody
    }

    const response = await fetch(endpoint, {
      method: formParent ? "POST" : method,
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body,
    })

    if (response.status > 300) {
      const { message } = await response.json()
      document.body.classList.remove("loading")
      UIkit.notification({ ...notificationFailure, message })
      throw Error(message)
    }

    const { snippets, redirect } = await response.json()

    if (redirect) {
      // this is how nette redirects...
      location.href = redirect
    } else {
      for (const [id, html] of Object.entries(snippets)) {
        const element = document.getElementById(id)
        if (element) {
          if (element.multiple && window.choicesInstances[element.id]) {
            // handle multiselect
            const value = window.choicesInstances[element.id].getValue(true)
            window.choicesInstances[element.id].destroy()
            element.innerHTML = html
            const choices = createChoicesInstance(element)
            choices.setChoiceByValue(value)
          } else {
            // handle rest
            element.innerHTML = html
            await initAll(element)
            if (element.hasAttribute("uk-modal")) {
              element.children.length
                ? UIkit.modal(element).show()
                : UIkit.modal(element).hide()
            }
          }
        }
      }
    }
    unsetLoading()
  } catch (e) {
    UIkit.notification({ ...notificationFailure, message: e.message })
    unsetLoading()
  }
}

export function toggle(togglers) {
  Object.entries(
    [...togglers].reduce((togglerGroups, toggler) => {
      const id = toggler.dataset.toggler || `reset`
      const group = togglerGroups[id]
      if (group) {
        group.push(toggler)
      } else {
        togglerGroups[id] = [toggler]
      }
      return togglerGroups
    }, {})
  ).forEach(([key, togglers]) => {
    const target = document.querySelector(`[data-toggle="${key}"]`)
    if (togglers.some((toggler) => toggler.checked)) {
      target
        ? target.classList.remove(`uk-hidden`)
        : [...document.querySelectorAll(`[data-toggle]`)].forEach((toggle) =>
            toggle.classList.remove(`uk-hidden`)
          )
    } else {
      target && target.classList.add(`uk-hidden`)
    }
  })
}

function setLoading() {
  document.body.style.height = `${document.body.offsetHeight}px`
  document.body.classList.add("loading")
}

function unsetLoading() {
  document.body.removeAttribute("style")
  document.body.classList.remove("loading")
}

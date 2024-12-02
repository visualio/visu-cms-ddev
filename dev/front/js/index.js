import Alpine from "alpinejs"
import "../css/index.css"


Alpine.data("ajax", (initState = {}) => ({
  loading: false,
  interactive: false,
  state: initState,
  getBody() {
    let formData = null
    if (this.$el instanceof HTMLFormElement) formData = new FormData(this.$el)
    if (this.$root instanceof HTMLFormElement)
      formData = new FormData(this.$root)
    // needed for isSubmittedBy nette method
    if (formData && this.$el instanceof HTMLButtonElement)
      formData.append(this.$el.name, this.$el.value)
    if (
      formData &&
      this.$el instanceof HTMLInputElement &&
      this.$el.type === "reset"
    )
      formData.append(this.$el.name, this.$el.value)
    if (formData && this.$event.submitter instanceof HTMLButtonElement)
      formData.append(this.$event.submitter.name, this.$event.submitter.value)
    return formData
  },
  getUrl() {
    if (this.$el instanceof HTMLFormElement) return this.$el.action
    if (this.$root instanceof HTMLFormElement) return this.$root.action
    if (this.$el instanceof HTMLAnchorElement) return this.$el.href
    return initState.url
  },
  propagate() {
    this.$el.closest("form").dispatchEvent(new Event("request"))
  },
  async request(body = null) {
    this.loading = true
    // await verifyRecaptcha(this.$el)
    return fetch(this.getUrl(), {
      method: body || this.getBody() ? "POST" : "GET",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: body || this.getBody(),
    })
      .then((response) => {
        if (response.redirected) location.href = response.url
        return response.json()
      })
      .then(({ snippets, redirect, url }) => {
        if (redirect) {
          location.href = redirect
        }
        if (snippets) {
          if (url) {
            window.history.pushState(snippets, "", url)
          }
          const updatedElements = applySnippets(snippets)
          scrollToHighestElement(updatedElements)
          this.interactive = true
          this.loading = false
        }
      })
      .catch((e) => {
        console.warn(e)
        this.loading = false
      })
  },
}))

function applySnippets(snippets) {
  const updatedElements = []
  for (const [id, html] of Object.entries(snippets)) {
    const element = document.getElementById(id)
    if (element) {
      element.innerHTML = html
      updatedElements.push(element)
    }
  }
  return updatedElements
}

function scrollToHighestElement(elements) {
  const topValues = elements.map(
    (element) => element.getBoundingClientRect().top
  )
  const max = Math.max(...topValues)
  const index = topValues.indexOf(max)
  elements[index].scrollIntoView({ behavior: "smooth" })
}

window.Alpine = Alpine
Alpine.start()

window.addEventListener(`DOMContentLoaded`, () => {
  console.log("DOM loaded")
})
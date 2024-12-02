export default function initFileManager(documentOrElement = document) {
  // toggle disabled submit
  documentOrElement
    .querySelectorAll("[data-file-manager-form]")
    .forEach((form) => {
      const submit = form.querySelector("input[type=submit]")
      if (submit) {
        form.onchange = () => {
          const formData = new FormData(form)
          const hasSelection = [...formData.keys()].some((key) =>
            key.startsWith("file")
          )
          if (hasSelection) {
            submit.removeAttribute("disabled")
          } else {
            submit.setAttribute("disabled", true)
          }
        }
      }
    })

  // search
  let filter = null
  let search = null

  // filter tags
  documentOrElement
    .querySelectorAll("[data-file-manager-filter]")
    .forEach((tagFilter) => {
      tagFilter.onchange = (e) => {
        filter = e.target.value
        filterFiles(filter, search)
      }
    })

  // filter search
  documentOrElement
    .querySelectorAll("[data-file-manager-search]")
    .forEach((input) => {
      input.oninput = (e) => {
        e.preventDefault()
        search = e.target.value
        filterFiles(filter, search)
      }
    })

  function filterFiles(filter, search) {
    documentOrElement
      .querySelectorAll("[data-file-manager-filter-data]")
      .forEach((item) => {
        const filterData = item.dataset.fileManagerFilterData.split(",")
        const isSearched = filterData.some((datum) =>
          search ? datum.toLowerCase().indexOf(search.toLowerCase()) > -1 : true
        )
        const isFiltered = filterData.some((datum) =>
          filter ? filter === datum : true
        )
        if (isSearched && isFiltered) {
          item.removeAttribute("hidden")
        } else {
          item.setAttribute("hidden", true)
        }
      })
  }
}

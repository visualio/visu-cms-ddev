export default function initDropZone(documentOrElement = document) {
  documentOrElement.querySelectorAll(".drop-zone input").forEach((input) => {
    const list = input.closest(".drop-zone").lastElementChild
    input.addEventListener("change", ({ target: { files } }) => {
      list.innerHTML = ""
      for (const file of files) {
        const item = document.createElement("li")
        item.innerText = file.name
        list.appendChild(item)
      }
    })
  })
}

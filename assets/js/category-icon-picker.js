document.addEventListener("click", function (event) {
  const button = event.target.closest(".rm-icon-option");

  if (!button) {
    return;
  }

  const picker = button.closest(".rm-icon-picker");

  if (!picker) {
    return;
  }

  const input = picker.querySelector(".rm-category-icon-value");

  if (!input) {
    return;
  }

  picker.querySelectorAll(".rm-icon-option").forEach(function (option) {
    const selected = option === button;

    option.classList.toggle("is-selected", selected);
    option.setAttribute("aria-pressed", selected ? "true" : "false");
  });

  input.value = button.dataset.icon || "";
});

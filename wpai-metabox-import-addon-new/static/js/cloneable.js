function addRow(cloneable) {
  const container = cloneable.querySelector(".pmmi-cloneable-rows");
  const template = cloneable.querySelector(".pmmi-cloneable-template");

  const clone = template.content.cloneNode(true);
  const index = container.children.length;

  // Update names to match index
  clone.querySelectorAll("input, select, textarea").forEach((input) => {
    input.name = input.name.replace("__index__", index);
  });

  // Then add to DOM
  container.appendChild(clone);

  const lastRow = container.querySelector(".pmmi-cloneable-row:last-child");

  const customEvent = new CustomEvent("pmxi-refresh-repeater", {
    bubbles: true,
    detail: { node: lastRow },
  });
  dispatchEvent(customEvent);
}

function removeAllRowsExceptFirst(cloneable) {
  const rows = cloneable.querySelectorAll(".pmmi-cloneable-row");
  [...rows].filter((r, i) => i > 0).forEach((r) => r.remove());
}

function removeRow(row) {
  const container = row.parentElement;
  row.remove();

  // Refresh indexes
  [...container.children].forEach((row, index) => {
    row.querySelectorAll("input, select, textarea").forEach((input) => {
      input.name = input.name.replace(
        new RegExp(`\\[\\d+\\]`, "g"),
        `[${index}]`
      );
    });
  });
}

function getModeSwitchers(cloneable) {
  return [
    ...cloneable.parentElement.querySelectorAll(".pmxi-cloneable-mode .switcher"),
  ];
}

function getMode(cloneable) {
  const switchers = getModeSwitchers(cloneable);

  // Get the first checked switcher
  return switchers
    .filter((i) => i.checked)
    .map((i) => i.value)
    .at(0);
}

function updateUI(cloneable) {
  const mode = getMode(cloneable);
  const rows = cloneable.querySelectorAll(".pmxi-cloneable-row");

  if (mode === "fixed") {
    cloneable.classList.add("is-fixed");
  } else {
    cloneable.classList.remove("is-fixed");

    if (rows.length === 0) {
      addRow(cloneable);
    }

    removeAllRowsExceptFirst(cloneable);
  }
}

/**
 * @param {HTMLElement} cloneable
 */
export function refreshCloneable(cloneable) {
  const addButton = cloneable.querySelector(".pmmi-cloneable-add-row");
  const switchers = getModeSwitchers(cloneable);

  addButton.addEventListener("click", () => {
    addRow(cloneable);
  });

  switchers.forEach((switcher) => {
    switcher.addEventListener("change", () => updateUI(cloneable, addButton));
  });

  cloneable.addEventListener("click", (event) => {
    if (!event.target.matches(".pmmi-cloneable-remove-row")) return;
    removeRow(event.target.closest(".pmmi-cloneable-row"));
  });

  const count = cloneable.querySelectorAll(".pmmi-cloneable-row").length;

  if (count === 0) {
    addRow(cloneable);
  }

  updateUI(cloneable);
}

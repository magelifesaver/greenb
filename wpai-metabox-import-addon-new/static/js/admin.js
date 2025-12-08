import { refreshCloneable } from "./cloneable.js";

function refresh(event) {
  const { container } = event.detail;

  // Refresh repeaters
  container
    .querySelectorAll(".pmmi-cloneable")
    .forEach((cloneable) => refreshCloneable(cloneable));
}

addEventListener("pmxi-group-loaded", refresh);

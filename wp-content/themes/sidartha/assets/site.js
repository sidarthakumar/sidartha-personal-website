(function () {
  "use strict";
  const button = document.querySelector(".menu-toggle");
  const nav = document.getElementById("primary-navigation");
  if (!button || !nav) return;
  const mobile = window.matchMedia("(max-width: 900px)");
  document.documentElement.classList.add("has-js");
  function close(restoreFocus) {
    button.setAttribute("aria-expanded", "false");
    nav.classList.remove("is-open");
    if (restoreFocus) button.focus();
  }
  button.addEventListener("click", function () {
    const open = button.getAttribute("aria-expanded") !== "true";
    button.setAttribute("aria-expanded", String(open));
    nav.classList.toggle("is-open", open);
  });
  nav.addEventListener("keydown", function (event) {
    if (event.key === "Escape") close(true);
  });
  button.addEventListener("keydown", function (event) {
    if (event.key === "Escape") close(true);
  });
  nav.addEventListener("click", function (event) {
    if (event.target.closest("a") && mobile.matches) close(false);
  });
  document.addEventListener("click", function (event) {
    if (!nav.contains(event.target) && !button.contains(event.target))
      close(false);
  });
  document.addEventListener("focusin", function (event) {
    if (!nav.contains(event.target) && !button.contains(event.target))
      close(false);
  });
  mobile.addEventListener("change", function () {
    close(false);
  });
})();

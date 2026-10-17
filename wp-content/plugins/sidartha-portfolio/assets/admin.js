(function () {
  "use strict";
  const choose = document.getElementById("skp-select-photo");
  if (!choose || !window.wp || !wp.media) return;
  const field = document.getElementById("skp-photo");
  const status = document.getElementById("skp-photo-status");
  choose.addEventListener("click", function () {
    const picker = wp.media({
      title: "Choose your profile photograph",
      library: { type: "image" },
      multiple: false,
    });
    picker.on("select", function () {
      field.value = picker.state().get("selection").first().get("id");
      status.textContent =
        "Photograph selected. Save settings and review its alt text in the media library.";
    });
    picker.open();
  });
  document
    .getElementById("skp-clear-photo")
    .addEventListener("click", function () {
      field.value = "0";
      status.textContent = "The monogram will appear after saving.";
    });
})();

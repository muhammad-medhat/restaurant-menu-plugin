(function ($) {
  if (typeof acf === "undefined") {
    return;
  }

  function formatIcon(option) {
    if (!option.id) {
      return option.text;
    }

    var classes = String(option.id).split(/\s+/);
    var $container = $("<span>");
    var $icon = $("<i>").addClass(classes);

    $icon.css({
      display: "inline-block",
      width: "24px",
      marginRight: "8px",
    });

    $container.append($icon);
    $container.append(document.createTextNode(option.text || ""));

    return $container;
  }

  function setupIconSelect(field) {
    if (!field || field.get("name") !== "fa_icon") {
      return;
    }

    var $select = field.$input();

    if (!$select.length || !$select.is("select")) {
      return;
    }

    // Ensure Select2 is available.
    if (typeof $.fn.select2 !== "function") {
      return;
    }

    // Avoid initializing repeatedly.
    if ($select.hasClass("select2-hidden-accessible")) {
      $select.select2("destroy");
    }

    $select.select2({
      width: "100%",
      templateResult: formatIcon,
      templateSelection: formatIcon,
      escapeMarkup: function (markup) {
        return markup;
      },
    });
  }

  acf.addAction("ready_field/name=fa_icon", setupIconSelect);
  acf.addAction("append_field/name=fa_icon", setupIconSelect);
})(jQuery);

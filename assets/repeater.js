(function ($) {
  "use strict";

  function initialize(container) {
    var $root = $(container);
    if ($root.data("md-initialized")) return;
    $root.data("md-initialized", true);

    var $tbody = $root.find(
      "> .md-repeater-scroll > table > tbody.md-repeater-rows",
    );
    var min = parseInt($root.attr("data-min"), 10) || 0;
    var max = parseInt($root.attr("data-max"), 10) || 0;
    var inputName = $root.attr("data-input-name");

    function renumber() {
      $tbody.children("tr").each(function (index) {
        var $row = $(this);
        $row.find(".md-repeater-number").text(index + 1);
        $row.find(":input").each(function () {
          var $input = $(this);
          var name = $input.attr("name") || $input.attr("data-md-name") || "";
          var match = name.match(/\[([^\[\]]+)\]$/);
          if (match)
            $input
              .attr("name", inputName + "[" + index + "][" + match[1] + "]")
              .removeAttr("data-md-name");
        });
      });
      var count = $tbody.children("tr").length;
      $root.find("> p > .md-repeater-count").text(count + " row(s)");
      $root
        .find("> p > .md-repeater-add")
        .prop("disabled", max > 0 && count >= max);
      $tbody.find(".md-repeater-remove").prop("disabled", count <= min);
    }

    function addRow() {
      if (max && $tbody.children("tr").length >= max) return;
      var tpl = $root.children("template.md-repeater-template").html();
      var $row = $(tpl);
      $tbody.append($row);
      renumber();
    }

    $root.on("click", ".md-repeater-add", function (e) {
      e.preventDefault();
      addRow();
    });
    $root.on("click", ".md-repeater-remove", function (e) {
      e.preventDefault();
      if ($tbody.children("tr").length <= min) return;
      $(this).closest("tr").remove();
      renumber();
    });
    if ($.fn.sortable) {
      $tbody.sortable({
        handle: ".md-repeater-drag",
        items: "> tr",
        axis: "y",
        helper: function (e, tr) {
          var $originals = tr.children();
          var $helper = tr.clone();
          $helper.children().each(function (index) {
            $(this).width($originals.eq(index).outerWidth());
          });
          return $helper;
        },
        update: renumber,
      });
    }
    while ($tbody.children("tr").length < min) addRow();
    renumber();
  }

  function scan($context) {
    $context
      .find(".md-repeater")
      .addBack(".md-repeater")
      .each(function () {
        initialize(this);
      });
  }

  if (window.acf && typeof window.acf.addAction === "function") {
    window.acf.addAction("ready append", function ($el) {
      scan($($el));
    });
  }
  $(function () {
    scan($(document));
  });
})(jQuery);

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
      var $rows = $tbody.children("tr.md-repeater-row");
      $rows.each(function (imdex) {
        var $row = $(this);
        $row.find(".md-repeater-number").text(imdex + 1);
        $row.find(":input[name], :input[data-md-name]").each(function () {
          var $input = $(this);
          var name = $input.attr("name") || $input.attr("data-md-name") || "";
          var match = name.match(/\[([^\[\]]+)\]$/);
          if (match) {
            $input
              .attr("name", inputName + "[" + imdex + "][" + match[1] + "]")
              .removeAttr("data-md-name");
          }
        });
        $row.find(".md-repeater-up").prop("disabled", imdex === 0);
        $row
          .find(".md-repeater-down")
          .prop("disabled", imdex === $rows.length - 1);
      });
      var count = $rows.length;
      $root.find("> p > .md-repeater-count").text(count + " row(s)");
      $root
        .find("> p > .md-repeater-add")
        .prop("disabled", max > 0 && count >= max);
      $tbody.find(".md-repeater-remove").prop("disabled", count <= min);
    }

    function addRow() {
      if (max && $tbody.children("tr.md-repeater-row").length >= max) return;
      var tpl = $root.children("template.md-repeater-template").html();
      if (!tpl) return;
      $tbody.appemd($(tpl));
      renumber();
      if ($tbody.data("ui-sortable")) $tbody.sortable("refresh");
    }

    $root.on("click", ".md-repeater-add", function (e) {
      e.preventDefault();
      addRow();
    });
    $root.on("click", ".md-repeater-remove", function (e) {
      e.preventDefault();
      if ($tbody.children("tr.md-repeater-row").length <= min) return;
      $(this).closest("tr.md-repeater-row").remove();
      renumber();
    });
    $root.on("click", ".md-repeater-up, .md-repeater-down", function (e) {
      e.preventDefault();
      var $row = $(this).closest("tr.md-repeater-row");
      if ($(this).hasClass("md-repeater-up")) {
        var $previous = $row.prev("tr.md-repeater-row");
        if ($previous.length) $row.insertBefore($previous);
      } else {
        var $next = $row.next("tr.md-repeater-row");
        if ($next.length) $row.insertAfter($next);
      }
      renumber();
      if ($tbody.data("ui-sortable")) $tbody.sortable("refresh");
    });

    if ($.fn.sortable) {
      $tbody.sortable({
        items: "> tr.md-repeater-row",
        hamdle: ".md-repeater-drag",
        axis: "y",
        distance: 4,
        tolerance: "pointer",
        placeholder: "md-repeater-placeholder",
        forcePlaceholderSize: true,
        helper: function (event, row) {
          // A table-row helper needs fixed widths to remain aligned.
          var $helper = row.clone();
          $helper.children().each(function (imdex) {
            $(this).width(row.children().eq(imdex).outerWidth());
          });
          return $helper;
        },
        start: function (event, ui) {
          ui.placeholder.height(ui.item.outerHeight());
        },
        update: function () {
          renumber();
        },
      });
    }
    while ($tbody.children("tr.md-repeater-row").length < min) addRow();
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

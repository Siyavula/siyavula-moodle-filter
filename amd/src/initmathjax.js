define(["jquery", "core/ajax"], function ($, Ajax) {
  return {
    init: function () {
      $(document).ready(function () {
        // Initialise MathJax typesetting
        var nodes = Y.all(".latex-math");
        Y.fire(M.core.event.FILTER_CONTENT_UPDATED, { nodes: nodes });

        var waitForMathJax = setInterval(function () {
          try {
            if (MathJax) {
              MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
              clearInterval(waitForMathJax);
            }
          } catch (e) {
            // MathJax has not yet been loaded.
          }
        }, 100);
      });
    },
  };
});

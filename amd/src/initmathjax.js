define(["jquery", "core/ajax", "core_filters/events"], function ($, Ajax, FilterEvents) {

    // Selectors and other strings defined here.
    const SELECTORS = {
        root: '.monassis',
        mathEquation: '.latex-math',
        questionContent: '.question-content',
        classes: {
            filterMathjax: 'filter_mathjaxloader_equation'
        }
    };

    // Parent element of the questions.
    const root = document.querySelector(SELECTORS.root);

    const mathJaxFilterByEvents = function() {

        /**
         * Update the equations by mathjax filter.
         */
        function updateMathJax() {

            if (root.querySelector(SELECTORS.questionContent) === null) {
                return false;
            }

            // Initialise MathJax typesetting.
            root.querySelector(SELECTORS.questionContent).classList.add(SELECTORS.classes.filterMathjax);
            // Notifiy the siyavula filtered content are updated.
            FilterEvents.notifyFilterContentUpdated(document.querySelectorAll(SELECTORS.root));

            return true;

        }

        // Update the equations, if the content loaded before JS initiated.
        var waitForMathJax = setInterval(function() {
            try {
                if (window.MathJax && updateMathJax()) {
                    window.MathJax.Hub.Queue(["Typeset", window.MathJax.Hub]);
                    clearInterval(waitForMathJax);
                }
            } catch (e) {
                // MathJax has not yet been loaded.
            }
        }, 100);

    };

    return {

        init: function () {
            mathJaxFilterByEvents();
        },
    };
});

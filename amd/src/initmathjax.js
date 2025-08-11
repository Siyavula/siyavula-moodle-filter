define(["jquery", "core/ajax", "core_filters/events", 'filter_mathjaxloader/loader'], function ($, Ajax, FilterEvents, MathJaxLoader) {

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

    const mathJaxFilterByEvents = function (isSupported = true) {

        /**
         * Update the equations by mathjax filter.
         */
        function updateMathJax() {

            if (root.querySelector(SELECTORS.questionContent) === null) {
                return false;
            }

            if (root.querySelector(SELECTORS.questionContent).innerHTML === '') {
                return false;
            }

            // Initialise MathJax typesetting.
            root.querySelector(SELECTORS.questionContent).classList.add(SELECTORS.classes.filterMathjax);

            // Notifiy the siyavula filtered content are updated.
            if (isSupported) {
                FilterEvents.notifyFilterContentUpdated(document.querySelectorAll(SELECTORS.root));
            } else {
                window.MathJax.Hub?.Queue(["Typeset", window.MathJax.Hub, root.querySelector(SELECTORS.questionContent)]);
            }

            return true;

        }

        // Update the equations, if the content loaded before JS initiated.
        var count = 0;
        var waitForMathJax = setInterval(function () {

            try {
                if (window.MathJax && updateMathJax()) {
                    clearInterval(waitForMathJax);
                    window.MathJax.Hub?.Queue(["Typeset", window.MathJax.Hub]);
                }

            } catch (e) {
                // MathJax has not yet been loaded.
            }
            count++;
            if (count > 200) {
                clearInterval(waitForMathJax);
                console.warn('MathJax did not load in time, please check your MathJax configuration.');
            }

        }, 100);

    };

    return {

        init: function (isSupported = true) {

            // Moodle 5.0 and later versions support the MathJax filter.
            if (!isSupported && window.MathJax.version === undefined) {
                // Make the mathjax filter work with the Siyavula filter.
                // It helps to make the mathjax.hub is initialized.
                MathJaxLoader.loadMathJax();
            }

            mathJaxFilterByEvents(isSupported);
        },
    };

});

define(["jquery", "core/ajax", 'core_filters/events'], function ($, Ajax, FilterEvents) {

    // Selectors and other strings defined here.
    const SELECTORS = {
        root: '.monassis',
        mathEquation: '.latex-math',
        classes: {
            filterMathjax: 'filter_mathjaxloader_equation'
        }
    }


    return {

        init: function () {

            // Parent element of the questions.
            var root = document.querySelector(SELECTORS.root);

            const updateMathJax = () => {
                if (root.querySelectorAll(SELECTORS.mathEquation).length !== 0) {
                    // Initialise MathJax typesetting.
                    // Add additional filtermathjax class for fetch the list of nodes needed for filter.
                    root.querySelectorAll(SELECTORS.mathEquation).forEach(
                        (e) => e.classList.add(SELECTORS.classes.filterMathjax)
                    );

                    // Notifiy the siyavula filtered content are updated.
                    FilterEvents.notifyFilterContentUpdated(root);

                    // Ensure MathJax is ready before triggering typeset
                    if (window.MathJax) {
                        if (window.MathJax.Hub.queue.pending) {
                            window.MathJax.Hub.Queue(["Typeset", window.MathJax.Hub, root]);
                        }
                    }
                }
            }

            // Callaback to the observer.
            function callback(mutationList, observer) {
                // console.log('mutation', mutationList);
                for (const mutation of mutationList) {

                    // Nodes are added inside the root.
                    if (mutation.type === "childList") {
                        updateMathJax();
                    }
                }
            };

            // Create observer for the monassis root, wait untill the contens are loadded.
            const observer = new MutationObserver(callback);

            // Start observing the monassis root for initiate the mathjax filter.
            observer.observe(root, { childList: true, subtree: true });

            updateMathJax();
        },
    };
});

define([], function() {

    /**
     * Update mastery stars display based on API response data
     *
     * @param {Object} practice Practice data from API response
     */
    function updateMasteryDisplay(practice) {
        if (!practice || !practice.chapter || !practice.section) {
            return;
        }

        const chapterMastery = practice.chapter.mastery.toFixed(2);
        const sectionMastery = practice.section.mastery.toFixed(2);

        // Update chapter mastery
        const chapterMasteryEl = document.querySelector('#chapter-mastery');
        const chapterTitleEl = document.querySelector('#chapter-mastery-title');
        if (chapterMasteryEl) {
            chapterMasteryEl.setAttribute('value', chapterMastery);
            chapterMasteryEl.setAttribute('data-text', chapterMastery + '%');
        }
        if (chapterTitleEl) {
            chapterTitleEl.textContent = practice.chapter.title;
        }

        // Update section mastery
        const sectionMasteryEl = document.querySelector('#section-mastery');
        const sectionTitleEl = document.querySelector('#section-mastery-title');
        if (sectionMasteryEl) {
            sectionMasteryEl.setAttribute('value', sectionMastery);
            sectionMasteryEl.setAttribute('data-text', sectionMastery + '%');
        }
        if (sectionTitleEl) {
            sectionTitleEl.textContent = practice.section.title;
        }
    }


    /**
     * Wrap the API's updateUI method to intercept responses
     *
     * @param {Object} api The SiyavulaAPI instance
     */
    function wrapUpdateUI(api) {
        if (!api || !api.updateUI) {
            console.warn('Mastery listener: Cannot wrap updateUI, API not ready');
            return;
        }

        const originalUpdateUI = api.updateUI.bind(api);

        api.updateUI = function(response) {
            // Call the original method
            originalUpdateUI(response);

            // Update mastery if response contains practice data
            if (response && response.practice) {
                updateMasteryDisplay(response.practice);
            }
        };
    }

    return {
        // Wrap an API instance directly
        wrapAPI: function(api) {
            wrapUpdateUI(api);
        },
        // Expose updateDisplay so it can be called directly with practice data
        updateDisplay: function(practiceData) {
            updateMasteryDisplay(practiceData);
        }
    };

});

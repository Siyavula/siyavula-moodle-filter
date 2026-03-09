define(['core/ajax'], function(Ajax) {

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
     * Trigger a targeted server-side grade update for a single section via AJAX.
     *
     * @param {number} cmid           Course module ID of the siyavula activity
     * @param {number} sectionid      Siyavula section ID
     * @param {number} sectionmastery Section mastery value (0–100)
     */
    function triggerGradeUpdate(cmid, sectionid, sectionmastery) {
        Ajax.call([{
            methodname: 'mod_siyavula_update_section_grade',
            args: {cmid: cmid, sectionid: sectionid, sectionmastery: sectionmastery},
        }])[0].catch(function(err) {
            console.warn('Siyavula: grade update failed', err);
        });
    }

    /**
     * Wrap the API's updateUI method to intercept responses
     *
     * @param {Object} api       The SiyavulaAPI instance
     * @param {number} cmid      Course module ID (used to trigger server-side grade sync)
     * @param {number} sectionid Siyavula section ID for the targeted grade update
     */
    function wrapUpdateUI(api, cmid, sectionid) {
        if (!api || !api.updateUI) {
            console.warn('Mastery listener: Cannot wrap updateUI, API not ready');
            return;
        }

        const originalUpdateUI = api.updateUI.bind(api);

        api.updateUI = function(response) {
            // Call the original method
            originalUpdateUI(response);

            // Update mastery display and trigger server-side grade sync
            if (response && response.practice) {
                updateMasteryDisplay(response.practice);
                if (cmid && sectionid) {
                    triggerGradeUpdate(cmid, sectionid, response.practice.section.mastery);
                }
            }
        };
    }

    return {
        // Wrap an API instance directly
        wrapAPI: function(api, cmid, sectionid) {
            wrapUpdateUI(api, cmid, sectionid);
        },
        // Expose updateDisplay so it can be called directly with practice data
        updateDisplay: function(practiceData) {
            updateMasteryDisplay(practiceData);
        }
    };

});

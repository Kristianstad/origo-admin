/**
 * Gör så att vanligt vänsterklick på alternativen i en <select multiple>
 * endast togglar det klickade valet (som Ctrl+klick borde göra),
 * och blockerar Shift-klick, Ctrl-klick och range-markering.
 *
 * Logiken körs i mousedown-fasen för att hinna före webbläsarens
 * inbyggda rensning av multipla val.
 *
 * @param {string} selectId - ID på <select multiple>-elementet
 */
function makeSelectToggleOnly(selectId) {
    const select = document.getElementById(selectId);
    if (!select || !select.multiple) {
        console.warn(`makeSelectToggleOnly: Hittade inte #${selectId} eller den är inte multiple`);
        return;
    }

    select.addEventListener('mousedown', function(event) {
        // Bara vänsterklick på OPTION ska hanteras
        if (event.button !== 0 || event.target.tagName !== 'OPTION') {
            return;
        }

        // Blockera alla modifier-tangenter (shift, ctrl, meta/cmd)
        if (event.shiftKey || event.ctrlKey || event.metaKey) {
            event.preventDefault();
            event.stopPropagation();
            //console.log(`Blocked modifier mousedown: shift=${event.shiftKey}, ctrl=${event.ctrlKey}`);
            return;
        }

        // Vanligt vänsterklick → vi tar över kontrollen
        event.preventDefault();
        event.stopPropagation();
        const scrollTop=select.scrollTop;
        const scrollLeft=select.scrollLeft;

        // Toggle det klickade alternativet
        const option = event.target;
        option.selected = !option.selected;

        // Synka med befintlig update-funktion
        if (typeof update === 'function') {
            update(select);
        } else {
            console.warn('update-funktionen saknas – kunde inte synka urvalet');
        }
        const restoreScroll=() => {
            select.scrollTop=scrollTop;
            select.scrollLeft=scrollLeft;
        };
        restoreScroll();
        requestAnimationFrame(() => {
            restoreScroll();
            setTimeout(restoreScroll, 0);
        });

    }, true);  // capture-fas → absolut nödvändigt för att övertrumfa default-beteendet
}

/**
 * MeetSpace Enterprise Suite - Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // Search input behavior
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                console.log('Search query:', searchInput.value.trim());
            }
        });
    }

    console.log('MeetSpace Enterprise Suite initialized.');
});
// Disable Right Click (Context Menu)
document.addEventListener('contextmenu', event => event.preventDefault());

// Disable Keyboard Shortcuts (Ctrl+Shift+I, F12, Ctrl+U, Ctrl+S)
document.addEventListener('keydown', function(e) {
    // F12
    if (e.key === 'F12') {
        e.preventDefault();
        return false;
    }

    // Ctrl shortcuts
    if (e.ctrlKey) {
        // Ctrl+Shift+I or Ctrl+Shift+J or Ctrl+Shift+C
        if (e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C' || e.key === 'i' || e.key === 'j' || e.key === 'c')) {
            e.preventDefault();
            return false;
        }
        // Ctrl+U (View Source)
        if (e.key === 'U' || e.key === 'u') {
            e.preventDefault();
            return false;
        }
        // Ctrl+S (Save)
        if (e.key === 'S' || e.key === 's') {
            e.preventDefault();
            return false;
        }
    }
});

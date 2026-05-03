/**
 * Admin Idle Timeout Script
 * Automatically logs out the administrator after 15 minutes of inactivity.
 */

(function() {
    // 15 minutes in milliseconds
    const IDLE_TIMEOUT_MS = 15 * 60 * 1000; 
    let idleTimer = null;

    function resetTimer() {
        if (idleTimer) {
            clearTimeout(idleTimer);
        }
        idleTimer = setTimeout(handleIdleTimeout, IDLE_TIMEOUT_MS);
    }

    function handleIdleTimeout() {
        // Create the floating card overlay
        const overlay = document.createElement('div');
        overlay.id = 'idle-timeout-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100vw';
        overlay.style.height = '100vh';
        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        overlay.style.zIndex = '999999';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.backdropFilter = 'blur(4px)';

        const card = document.createElement('div');
        card.style.backgroundColor = '#fff';
        card.style.padding = '2rem 3rem';
        card.style.borderRadius = '1rem';
        card.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.5)';
        card.style.textAlign = 'center';
        card.style.maxWidth = '400px';

        card.innerHTML = `
            <i class="ki-filled ki-time text-warning mb-4" style="font-size: 3rem;"></i>
            <h2 class="text-2xl font-bold mb-2 text-gray-800">Session Expired</h2>
            <p class="text-gray-600 mb-6">You have been idle for 15 minutes. For your security, you have been automatically logged out.</p>
            <p class="text-sm text-gray-400">Redirecting to login page...</p>
        `;

        overlay.appendChild(card);
        document.body.appendChild(overlay);

        // Remove event listeners so user can't reset it while it's redirecting
        document.removeEventListener('mousemove', resetTimer);
        document.removeEventListener('keydown', resetTimer);
        document.removeEventListener('scroll', resetTimer);
        document.removeEventListener('click', resetTimer);

        // Redirect to logout
        setTimeout(() => {
            window.location.href = '/logout';
        }, 3000);
    }

    // Initialize listeners
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keydown', resetTimer);
    document.addEventListener('scroll', resetTimer);
    document.addEventListener('click', resetTimer);

    // Start timer on load
    resetTimer();
})();

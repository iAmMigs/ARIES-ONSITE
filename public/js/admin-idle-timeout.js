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
        // Add animation keyframes if not present
        if (!document.getElementById('timeout-animations')) {
            const style = document.createElement('style');
            style.id = 'timeout-animations';
            style.innerHTML = `
                @keyframes timeout-fade-in { from { opacity: 0; } to { opacity: 1; } }
                @keyframes timeout-scale-in { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
                @keyframes timeout-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            `;
            document.head.appendChild(style);
        }

        // Create the floating card overlay
        const overlay = document.createElement('div');
        overlay.id = 'idle-timeout-overlay';
        overlay.style.cssText = `
            position: fixed; inset: 0; z-index: 999999;
            background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            animation: timeout-fade-in 0.4s ease-out;
        `;

        const card = document.createElement('div');
        card.style.cssText = `
            background: #fff; padding: 3.5rem 3rem; border-radius: 2rem;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.4);
            text-align: center; max-width: 420px; width: 90%;
            border: 1px solid rgba(255,255,255,0.2);
            animation: timeout-scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        `;

        const totalSeconds = 10;
        const endTime = Date.now() + (totalSeconds * 1000);
        
        card.innerHTML = `
            <div style="margin-bottom: 2rem; position: relative; display: inline-block;">
                <div style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid #f3f4f6; border-top-color: #006b3d; animation: timeout-spin 2s linear infinite;"></div>
                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                    <span id="timeout-countdown-text" style="font-size: 2rem; font-weight: 800; color: #1a4d2e; font-family: Inter, sans-serif;">${totalSeconds}</span>
                </div>
            </div>
            
            <h2 style="font-size: 1.75rem; font-weight: 800; color: #1a1b1e; margin-bottom: 1rem; font-family: Inter, sans-serif;">Session Expired</h2>
            <p style="font-size: 1rem; color: #64748b; margin-bottom: 2.5rem; line-height: 1.6; font-family: Inter, sans-serif;">
                For your security, you have been logged out due to inactivity. You will be redirected to the landing page in <span id="timeout-seconds-text" style="font-weight: 700; color: #006b3d;">${totalSeconds} seconds</span>.
            </p>

            <button id="timeout-immediate-btn" style="
                background: linear-gradient(135deg, #1a4d2e 0%, #2d6a4f 100%);
                color: #fff; font-weight: 700; font-size: 1rem;
                padding: 1rem 2rem; border-radius: 1rem; width: 100%;
                transition: all 0.3s ease; cursor: pointer; border: none;
                box-shadow: 0 10px 20px -5px rgba(26, 77, 46, 0.3);
                font-family: Inter, sans-serif;
            ">Back</button>
        `;

        overlay.appendChild(card);
        document.body.appendChild(overlay);

        const countdownText = document.getElementById('timeout-countdown-text');
        const secondsText = document.getElementById('timeout-seconds-text');
        const immediateBtn = document.getElementById('timeout-immediate-btn');

        function updateCountdown() {
            const now = Date.now();
            const remaining = Math.max(0, Math.ceil((endTime - now) / 1000));
            
            if (countdownText) countdownText.innerText = remaining;
            if (secondsText) secondsText.innerText = remaining + (remaining === 1 ? ' second' : ' seconds');
            
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                document.removeEventListener('visibilitychange', updateCountdown);
                window.location.href = '/logout';
            }
        }

        const countdownInterval = setInterval(updateCountdown, 1000);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                updateCountdown();
            }
        });

        if (immediateBtn) {
            immediateBtn.addEventListener('click', () => {
                clearInterval(countdownInterval);
                document.removeEventListener('visibilitychange', updateCountdown);
                window.location.href = '/logout';
            });
            immediateBtn.addEventListener('mouseover', () => { 
                immediateBtn.style.transform = 'translateY(-2px)';
                immediateBtn.style.boxShadow = '0 15px 30px -5px rgba(26, 77, 46, 0.4)';
            });
            immediateBtn.addEventListener('mouseout', () => { 
                immediateBtn.style.transform = 'translateY(0)';
                immediateBtn.style.boxShadow = '0 10px 20px -5px rgba(26, 77, 46, 0.3)';
            });
        }

        // Remove event listeners so user can't reset it while it's redirecting
        document.removeEventListener('mousemove', resetTimer);
        document.removeEventListener('keydown', resetTimer);
        document.removeEventListener('scroll', resetTimer);
        document.removeEventListener('click', resetTimer);
    }

    // Initialize listeners
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keydown', resetTimer);
    document.addEventListener('scroll', resetTimer);
    document.addEventListener('click', resetTimer);

    // Start timer on load
    resetTimer();
})();

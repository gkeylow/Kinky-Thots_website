<?php
/**
 * Footer Scripts Include
 * Include this at the very end of every page (after footer.php and any page scripts)
 * Contains: Age verification modal
 */
?>
<!-- Age Verification Modal -->
<div id="age-gate-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border:2px solid #f805a7; border-radius:20px; padding:3rem; max-width:400px; text-align:center; box-shadow:0 0 40px rgba(248,5,167,0.3);">
        <img src="https://i.ibb.co/vCYpJSng/icon-kt-250.png" alt="Kinky Thots" width="80" style="margin-bottom:1.5rem;">
        <h2 style="color:#fff; font-size:1.8rem; margin-bottom:1rem; background:linear-gradient(45deg,#0bd0f3,#f805a7); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">Age Verification</h2>
        <p style="color:rgba(255,255,255,0.8); margin-bottom:0.5rem;">This website contains adult content.</p>
        <p style="color:rgba(255,255,255,0.8); margin-bottom:2rem;">Are you 18 years or older?</p>
        <div style="display:flex; gap:1rem; justify-content:center;">
            <button id="age-gate-yes" style="padding:0.8rem 2rem; font-size:1rem; font-weight:bold; border:none; border-radius:25px; cursor:pointer; background:linear-gradient(45deg,#0bd0f3,#f805a7); color:#fff;">Yes, I'm 18+</button>
            <button id="age-gate-no" style="padding:0.8rem 2rem; font-size:1rem; font-weight:bold; border:2px solid rgba(255,255,255,0.3); border-radius:25px; cursor:pointer; background:transparent; color:#fff;">No, Leave</button>
        </div>
        <p style="color:rgba(255,255,255,0.5); font-size:0.75rem; margin-top:1.5rem;">By entering, you agree to our <a href="/terms.php" style="color:#0bd0f3;">Terms of Service</a></p>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('age-gate-modal');
    var yesBtn = document.getElementById('age-gate-yes');
    var noBtn = document.getElementById('age-gate-no');
    if (!localStorage.getItem('age_verified')) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    yesBtn.onclick = function() {
        localStorage.setItem('age_verified', 'true');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };
    noBtn.onclick = function() {
        window.location.href = 'https://www.google.com';
    };
})();
</script>
</body>
</html>

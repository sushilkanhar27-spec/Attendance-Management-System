<?php if (isset($error) && $error !== ''): ?>
    <div id="httpStatus" class="status-container show"
         data-status-code="401" data-status-title="Login failed"
         data-status-message="<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>"
         role="alertdialog" aria-modal="true" aria-labelledby="statusTitle">
        <section class="status-card">
            <div id="animationArea" class="animation-area" aria-hidden="true">
                <div class="lock"></div>
            </div>
            <div id="statusCode" class="status-code">401</div>
            <h2 id="statusTitle">Login failed</h2>
            <p id="statusMessage"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <button class="status-button" type="button" onclick="closeStatus()">Try again</button>
        </section>
    </div>
<?php endif; ?>

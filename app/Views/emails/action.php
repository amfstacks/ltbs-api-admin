<?= $this->extend('emails/master') ?>

<?= $this->section('content') ?>

    <h2 style="color: #111827; margin-top: 0; font-size: 20px;"><?= esc($greeting ?? 'Hello!') ?></h2>
    
    <!-- Render raw HTML for paragraphs so we can pass formatted text -->
    <div style="margin-bottom: 25px;">
        <?= $bodyMessage ?>
    </div>
    
    <?php if(isset($buttonUrl) && isset($buttonText)): ?>
        <div style="text-align: center;">
            <a href="<?= esc($buttonUrl) ?>" class="btn"><?= esc($buttonText) ?></a>
        </div>
    <?php endif; ?>
    
    <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
        If the button above doesn't work, copy and paste this link into your web browser:<br>
        <a href="<?= esc($buttonUrl) ?>" style="color: #2563eb; word-break: break-all;"><?= esc($buttonUrl) ?></a>
    </p>

<?= $this->endSection() ?>
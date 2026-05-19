<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Quality Assurance Guidelines</h1>
        <p class="text-sm text-gray-500 mt-1">The official standard operating procedure for platform reviewers.</p>
    </div>
    <button class="bg-navy-900 hover:bg-navy-800 text-white px-4 py-2 rounded-lg font-bold transition-colors text-sm flex items-center">
        <i class="ph-bold ph-printer mr-2"></i> Print Standard
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 max-w-4xl space-y-8">

    <section>
        <div class="flex items-center mb-4 border-b border-gray-100 pb-2">
            <i class="ph-fill ph-book-open text-gold-500 text-2xl mr-3"></i>
            <h2 class="text-xl font-bold text-navy-900">1. Theological Soundness</h2>
        </div>
        <ul class="list-disc list-inside text-gray-600 space-y-2 text-sm ml-2">
            <li>Content must align with the core doctrinal statements of the platform.</li>
            <li>Divisive, overly political, or hateful speech masquerading as theology must be <strong class="text-red-500">Hard Rejected</strong>.</li>
            <li>If a doctrine is questionable but not explicitly forbidden, use the <strong>Private Admin Chat</strong> to discuss with other reviewers before deciding.</li>
        </ul>
    </section>

    <section>
        <div class="flex items-center mb-4 border-b border-gray-100 pb-2">
            <i class="ph-fill ph-waveform text-gold-500 text-2xl mr-3"></i>
            <h2 class="text-xl font-bold text-navy-900">2. Audio Engineering Standards</h2>
        </div>
        <p class="text-sm text-gray-600 mb-3">All podcasts must meet a minimum listening standard. Test the <strong>HQ MP3</strong> version using the sticky player.</p>
        <ul class="list-disc list-inside text-gray-600 space-y-2 text-sm ml-2">
            <li><strong>Clipping/Distortion:</strong> If the speaker's voice distorts heavily when they raise their voice, request changes.</li>
            <li><strong>Dead Air:</strong> Unedited silences lasting longer than 10 seconds should be timestamped and returned for editing.</li>
            <li><strong>Background Noise:</strong> Minor hiss is acceptable; loud rustling, wind, or distracting background conversations are not.</li>
        </ul>
    </section>

    <section>
        <div class="flex items-center mb-4 border-b border-gray-100 pb-2">
            <i class="ph-fill ph-text-aa text-gold-500 text-2xl mr-3"></i>
            <h2 class="text-xl font-bold text-navy-900">3. Metadata & Formatting</h2>
        </div>
        <ul class="list-disc list-inside text-gray-600 space-y-2 text-sm ml-2">
            <li>Titles should be in Title Case (e.g., "The Power of Grace", not "THE POWER OF GRACE").</li>
            <li>Descriptions must accurately reflect the content and be free of severe typographical errors.</li>
            <li>The correct Authors/Speakers must be tagged.</li>
        </ul>
    </section>

    <section class="bg-gray-50 p-6 rounded-lg border border-gray-200 mt-8">
        <h2 class="text-lg font-bold text-navy-900 mb-3">Decision Matrix Protocol</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded border border-green-200">
                <h4 class="font-bold text-green-700 text-sm mb-1"><i class="ph-fill ph-check-circle mr-1"></i> Approve</h4>
                <p class="text-xs text-gray-500">Meets all standards perfectly, or has highly negligible audio imperfections.</p>
            </div>
            <div class="bg-white p-4 rounded border border-yellow-200">
                <h4 class="font-bold text-yellow-700 text-sm mb-1"><i class="ph-fill ph-pencil-circle mr-1"></i> Request Changes</h4>
                <p class="text-xs text-gray-500">Good content, but requires structural edits, metadata fixes, or audio trimming. Must include timestamped notes.</p>
            </div>
            <div class="bg-white p-4 rounded border border-red-200">
                <h4 class="font-bold text-red-700 text-sm mb-1"><i class="ph-fill ph-x-circle mr-1"></i> Hard Reject</h4>
                <p class="text-xs text-gray-500">Violates platform guidelines severely. Flags the content for Super Admin review.</p>
            </div>
        </div>
    </section>

</div>

<?= $this->endSection() ?>
<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center">
    <a href="<?= site_url('admin/podcasts') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors">
        <i class="ph-bold ph-arrow-left text-xl"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Manage Audio</h1>
        <p class="text-gray-500 mt-1">Teaching: <span class="font-semibold text-navy-900"><?= esc($podcast['title']) ?></span></p>
    </div>
</div>

<div x-data="{ 
    isUploading: false,
    uploadProgress: 0,
    uploadStatusText: 'Uploading new files...',
    errorMessage: '',

    submitForm(e) {
        this.errorMessage = '';
        
        // Basic validation before hitting server
        if (!this.$refs.mediaHighInput.files.length) {
            this.errorMessage = 'Please select a new High Quality MP3 file to replace the old one.';
            return;
        }

        this.isUploading = true;
        this.uploadProgress = 0;
        this.uploadStatusText = 'Sending new audio files to server...';

        let formData = new FormData(e.target);
        let xhr = new XMLHttpRequest();
        
        xhr.open('POST', e.target.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                this.uploadProgress = Math.round((event.loaded / event.total) * 100);
                if(this.uploadProgress === 100) {
                    this.uploadStatusText = 'Server is syncing to Cloudflare R2 and deleting old files... Please wait.';
                }
            }
        };

        xhr.onload = () => {
            try {
                let response = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && response.success) {
                    window.location.href = response.redirect;
                } else {
                    this.isUploading = false;
                    this.errorMessage = response.message || 'An unknown error occurred.';
                }
            } catch (error) {
                this.isUploading = false;
                this.errorMessage = 'Server Crash: File size exceeds php.ini limits, or a database error occurred.';
            }
        };

        xhr.onerror = () => {
            this.isUploading = false;
            this.errorMessage = 'A network error occurred. Check your connection.';
        };

        xhr.send(formData);
    }
}" class="max-w-3xl mx-auto">

    <div x-show="errorMessage !== ''" x-transition style="display: none;" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded shadow-sm flex items-start">
        <i class="ph-fill ph-warning-circle text-xl mr-3 mt-0.5"></i>
        <p x-text="errorMessage" class="text-sm font-medium"></p>
    </div>

    <!-- Danger Warning -->
    <div class="mb-6 p-5 bg-orange-50 border border-orange-200 rounded-xl flex items-start shadow-sm">
        <i class="ph-fill ph-warning text-orange-500 text-2xl mr-4"></i>
        <div>
            <h3 class="text-orange-800 font-bold text-sm">Permanent Action Warning</h3>
            <p class="text-orange-700 text-sm mt-1">Uploading new audio files here will immediately and permanently delete the existing MP3 files from the Cloudflare R2 bucket. This cannot be undone.</p>
        </div>
    </div>

    <form action="<?= site_url('admin/podcasts/update-media/' . $podcast['id']) ?>" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 relative overflow-hidden">
        <?= csrf_field() ?>

        <!-- Full Screen Uploading Overlay -->
        <div x-show="isUploading" style="display: none;" class="absolute inset-0 bg-white/95 backdrop-blur-sm z-50 flex flex-col items-center justify-center p-8">
            <div class="w-full max-w-md text-center">
                <i class="ph-duotone ph-headphones text-6xl text-gold-500 mb-4 animate-bounce"></i>
                <h3 class="text-xl font-bold text-navy-900 mb-2">Replacing Media</h3>
                <p class="text-sm text-gray-500 mb-6" x-text="uploadStatusText"></p>
                
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden shadow-inner">
                    <div class="bg-gold-500 h-3 rounded-full transition-all duration-200 ease-out relative" :style="'width: ' + uploadProgress + '%'">
                        <div class="absolute top-0 left-0 bottom-0 right-0 overflow-hidden">
                            <div class="w-full h-full bg-white/20 animate-[shimmer_2s_infinite]"></div>
                        </div>
                    </div>
                </div>
                <p class="text-xs font-bold text-navy-900" x-text="uploadProgress + '%'"></p>
            </div>
        </div>

        <!-- <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New High Quality File (320kbps MP3) <span class="text-red-500">*</span></label>
                <input type="file" x-ref="mediaHighInput" name="media_high" accept="audio/mpeg, audio/mp3" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100 border border-gray-300 rounded-md bg-white">
                <?php if(!empty($podcast['media_high_url'])): ?>
                    <p class="text-xs text-green-600 mt-2"><i class="ph-bold ph-check mr-1"></i> Currently has High Quality media attached.</p>
                <?php endif; ?>
            </div>
            
            <div class="pt-4 border-t border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-1">New Data Saver File (64kbps AAC/M4A) <span class="text-xs text-gray-400 font-normal">- Optional</span></label>
                <input type="file" name="media_low" accept="audio/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 border border-gray-300 rounded-md bg-white">
                <?php if(!empty($podcast['media_low_url'])): ?>
                    <p class="text-xs text-green-600 mt-2"><i class="ph-bold ph-check mr-1"></i> Currently has Low Quality media attached.</p>
                <?php endif; ?>
            </div>
        </div> -->

        <div class="space-y-6">
            <!-- High Quality Media Section -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label class="block text-sm font-bold text-navy-900 mb-2">High Quality File (320kbps MP3) <span class="text-red-500">*</span></label>
                
                <?php if(!empty($podcast['media_high_url'])): ?>
                    <div class="mb-4 p-3 bg-white border border-gray-200 rounded shadow-sm">
                        <p class="text-xs text-green-600 mb-2 font-bold"><i class="ph-bold ph-check-circle mr-1"></i> Current File Attached</p>
                        <!-- Standard HTML5 Audio Player -->
                        <audio controls class="w-full h-10">
                            <source src="<?= esc($podcast['media_high_url']) ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-red-500 mb-3"><i class="ph-bold ph-x-circle mr-1"></i> No High Quality media currently attached.</p>
                <?php endif; ?>

                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Select New File to Replace</label>
                <input type="file" x-ref="mediaHighInput" name="media_high" accept="audio/mpeg, audio/mp3" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100 border border-gray-300 rounded-md bg-white">
            </div>
            
            <!-- Low Quality Media Section -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label class="block text-sm font-bold text-navy-900 mb-2">Data Saver File (64kbps AAC/M4A) <span class="text-xs text-gray-400 font-normal">- Optional</span></label>
                
                <?php if(!empty($podcast['media_low_url'])): ?>
                    <div class="mb-4 p-3 bg-white border border-gray-200 rounded shadow-sm">
                        <p class="text-xs text-green-600 mb-2 font-bold"><i class="ph-bold ph-check-circle mr-1"></i> Current File Attached</p>
                        <audio controls class="w-full h-10">
                            <source src="<?= esc($podcast['media_low_url']) ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-gray-500 mb-3"><i class="ph-bold ph-info mr-1"></i> No Low Quality media currently attached.</p>
                <?php endif; ?>

                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Select New File to Replace</label>
                <input type="file" name="media_low" accept="audio/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 border border-gray-300 rounded-md bg-white">
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors shadow-sm flex items-center">
                <i class="ph-bold ph-warning-circle mr-2"></i> Replace Audio Files
            </button>
        </div>
    </form>
</div>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>

<?= $this->endSection() ?>
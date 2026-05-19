<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center">
        <a href="<?= site_url('admin/podcasts') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors">
            <i class="ph-bold ph-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Manage Audio</h1>
            <p class="text-gray-500 mt-1">Teaching: <span class="font-semibold text-navy-900"><?= esc($podcast['title']) ?></span></p>
        </div>
    </div>
    
    <div class="flex flex-wrap gap-2">
        <?php if($podcast['master_high_url']): ?>
            <span class="text-xs font-mono bg-green-50 text-green-700 px-2 py-1 rounded border border-green-200" title="High Quality MP3 Generated"><i class="ph-bold ph-check text-green-500 mr-1"></i>HQ MP3</span>
        <?php endif; ?>
        <?php if($podcast['master_low_url']): ?>
            <span class="text-xs font-mono bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-200" title="Data Saver MP3 Generated"><i class="ph-bold ph-check text-blue-500 mr-1"></i>LQ MP3</span>
        <?php endif; ?>
        <?php if($podcast['media_high_url']): ?>
            <span class="text-xs font-mono bg-purple-50 text-purple-700 px-2 py-1 rounded border border-purple-200" title="HLS Streaming Generated"><i class="ph-bold ph-check text-purple-500 mr-1"></i>HLS Ready</span>
        <?php endif; ?>
    </div>
</div>

<div x-data="{ 
    isUploading: false,
    uploadProgress: 0,
    uploadStatusText: 'Uploading new files...',
    errorMessage: '',

    submitForm(e) {
        this.errorMessage = '';
        
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
                    this.uploadStatusText = 'Server is storing files and resetting queue... Please wait.';
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
}" class="max-w-3xl mx-auto pb-32"> <div x-show="errorMessage !== ''" x-transition style="display: none;" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded shadow-sm flex items-start">
        <i class="ph-fill ph-warning-circle text-xl mr-3 mt-0.5"></i>
        <p x-text="errorMessage" class="text-sm font-medium"></p>
    </div>

    <div class="mb-6 p-5 bg-orange-50 border border-orange-200 rounded-xl flex items-start shadow-sm">
        <i class="ph-fill ph-warning text-orange-500 text-2xl mr-4"></i>
        <div>
            <h3 class="text-orange-800 font-bold text-sm">Action Warning</h3>
            <p class="text-orange-700 text-sm mt-1">Uploading a new audio file will reset this podcast's status back to <strong>Processing</strong>. It will be re-queued for FFmpeg compression, and will need to pass QA Review again before being published.</p>
        </div>
    </div>

    <form action="<?= site_url('admin/podcasts/update-media/' . $podcast['id']) ?>" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 relative overflow-hidden">
        <?= csrf_field() ?>

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

        <div class="space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label class="block text-sm font-bold text-navy-900 mb-2">High Quality File (320kbps MP3) <span class="text-red-500">*</span></label>
                
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Select New File to Replace</label>
                <input type="file" x-ref="mediaHighInput" name="media_high" accept="audio/mpeg, audio/mp3" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100 border border-gray-300 rounded-md bg-white">
            </div>
            
            </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors shadow-sm flex items-center">
                <i class="ph-bold ph-warning-circle mr-2"></i> Replace & Re-Queue Audio
            </button>
        </div>
    </form>
</div>

<?php if(!empty($highUrl)): ?>
<div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t border-gray-200 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.1)] z-40 p-4" 
     x-data="audioPlayer('<?= esc($highUrl, 'js') ?>', '<?= esc($lowUrl ?? '', 'js') ?>')"
     @seek-audio.window="forceSeek($event.detail)"> 
    
    <audio x-ref="audioEl" :src="currentSrc" @timeupdate="updateProgress()" @loadedmetadata="setDuration()" @error="handleError()" preload="metadata"></audio>

    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center gap-4">
        
        <div class="flex items-center gap-4 text-navy-900">
            <button @click="toggleQuality()" 
                    class="px-2 py-1 rounded text-xs font-bold font-mono transition-colors border"
                    :class="quality === 'high' ? 'bg-navy-900 text-white border-navy-900' : 'bg-gray-100 text-gray-500 border-gray-200'"
                    title="Toggle Quality (Testing)">
                <span x-text="quality === 'high' ? 'HQ' : 'LQ'">HQ</span>
            </button>

            <button @click="rewind()" class="p-2 hover:bg-gray-100 rounded-full transition-colors tooltip" title="Rewind 15s">
                <i class="ph-fill ph-rewind-circle text-2xl"></i>
            </button>
            
            <button @click="togglePlay()" class="w-12 h-12 bg-gold-500 hover:bg-gold-600 text-navy-900 rounded-full flex items-center justify-center transition-colors shadow-md">
                <i class="ph-fill text-2xl" :class="playing ? 'ph-pause' : 'ph-play'"></i>
            </button>

            <button @click="changeSpeed()" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-bold font-mono transition-colors">
                <span x-text="speed + 'x'">1x</span>
            </button>
        </div>

        <div class="flex-1 w-full flex items-center gap-3 font-mono text-sm text-gray-600">
            <span x-text="formatTime(currentTime)">00:00</span>
            
            <div class="relative flex-1 h-2 bg-gray-200 rounded-full cursor-pointer" @click="seek($event)">
                <div class="absolute top-0 left-0 h-full bg-gold-500 rounded-full transition-all duration-75" :style="'width: ' + progressPercent + '%'"></div>
                <div class="absolute top-1/2 -mt-1.5 w-3 h-3 bg-navy-900 rounded-full shadow transition-all duration-75" :style="'left: calc(' + progressPercent + '% - 6px)'"></div>
            </div>
            
            <span x-text="formatTime(duration)">00:00</span>
        </div>

        <div x-show="hasError" style="display: none;" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded flex items-center">
            <i class="ph-bold ph-warning-circle mr-1"></i> Audio Failed
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('audioPlayer', (highUrl, lowUrl) => ({
            audio: null, playing: false, currentTime: 0, duration: 0, progressPercent: 0, speed: 1, hasError: false,
            quality: 'high', highUrl: highUrl, lowUrl: lowUrl, currentSrc: highUrl,

            init() {
                this.audio = this.$refs.audioEl;
                if (!this.currentSrc || this.currentSrc.trim() === '') {
                    this.hasError = true; console.error("Audio src is empty!");
                }
            },
            handleError() { this.hasError = true; this.playing = false; console.error("HTML5 Audio Error!"); },
            toggleQuality() {
                if(!this.lowUrl || this.lowUrl.trim() === '') { alert("No Low Quality version available for this track yet."); return; }
                const timeStore = this.audio.currentTime; const wasPlaying = this.playing;
                this.quality = this.quality === 'high' ? 'low' : 'high';
                this.currentSrc = this.quality === 'high' ? this.highUrl : this.lowUrl;
                this.$nextTick(() => {
                    this.hasError = false; this.audio.load();
                    setTimeout(() => { this.audio.currentTime = timeStore; if(wasPlaying) this.audio.play(); }, 50);
                });
            },
            togglePlay() {
                if (this.hasError) { alert("Cannot play audio. Check console."); return; }
                if (this.playing) this.audio.pause(); else this.audio.play();
                this.playing = !this.playing;
            },
            rewind() { this.audio.currentTime = Math.max(0, this.audio.currentTime - 15); },
            changeSpeed() {
                const speeds = [1, 1.25, 1.5, 2];
                this.speed = speeds[(speeds.indexOf(this.speed) + 1) % speeds.length];
                this.audio.playbackRate = this.speed;
            },
            updateProgress() { this.currentTime = this.audio.currentTime; if (this.duration > 0) this.progressPercent = (this.currentTime / this.duration) * 100; },
            setDuration() { this.duration = this.audio.duration; },
            seek(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                this.audio.currentTime = ((event.clientX - rect.left) / rect.width) * this.duration;
            },
            forceSeek(seconds) { this.audio.currentTime = seconds; if(!this.playing) this.togglePlay(); },
            formatTime(s) { return `${Math.floor(s / 60).toString().padStart(2, '0')}:${Math.floor(s % 60).toString().padStart(2, '0')}`; }
        }));
    });
</script>
<?php endif; ?>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>

<?= $this->endSection() ?>
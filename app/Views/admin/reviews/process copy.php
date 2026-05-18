<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <a href="<?= site_url('admin/reviews') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors">
            <i class="ph-bold ph-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Review Room</h1>
            <p class="text-sm text-gray-500 mt-1">Evaluate this teaching before it goes live to the community.</p>
        </div>
    </div>
    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
        Status: Under Review
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-32">

    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="w-32 h-32 flex-shrink-0 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center">
                    <i class="ph-fill ph-image text-4xl text-gray-300"></i>
                    </div>
                
                <div class="flex-1">
                    <div class="flex gap-2 mb-2">
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold uppercase rounded">Bible Study</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold uppercase rounded">Grace</span>
                    </div>
                    <h2 class="text-xl font-bold text-navy-900 leading-tight">The Concept of Grace Part 1</h2>
                    <p class="text-sm text-gray-500 mt-1 flex items-center">
                        <i class="ph-fill ph-microphone-stage mr-1.5 text-gold-500"></i>
                        Uploaded by <span class="font-bold text-navy-900 ml-1">Elder Samuel</span>
                    </p>
                    <div class="mt-4 text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg border border-gray-100">
                        "An in-depth exploration of how Grace interacts with the Law in the New Testament."
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="noteEngine()">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-navy-900 flex items-center">
                        <i class="ph-fill ph-clock-user text-gold-500 mr-2 text-xl"></i> Timestamped Notes
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Leave exact markers for edits or corrections.</p>
                </div>
            </div>

            <div class="flex gap-3 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <button @click="captureTime()" type="button" class="flex-shrink-0 bg-navy-900 hover:bg-navy-800 text-white px-3 py-2 rounded-lg text-sm font-bold flex flex-col items-center justify-center transition-colors">
                    <i class="ph-bold ph-crosshair text-lg mb-0.5"></i>
                    <span x-text="formatTime(currentTime)">00:00</span>
                </button>
                <div class="flex-1">
                    <input type="text" placeholder="E.g., Audio spikes here, or check theology..." class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                </div>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-navy-900 px-4 py-2 rounded-lg font-bold transition-colors">
                    Add Note
                </button>
            </div>

            <div class="space-y-3">
                <div class="flex gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                    <button class="text-gold-600 font-mono font-bold bg-gold-50 px-2 py-1 rounded text-sm hover:bg-gold-100 h-fit">
                        [14:23]
                    </button>
                    <div>
                        <p class="text-sm text-gray-800">There is a 30-second silence here that needs to be trimmed out.</p>
                        <p class="text-xs text-gray-400 mt-1">Added by John (Reviewer) • 10 mins ago</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-navy-900 flex items-center mb-4">
                <i class="ph-fill ph-lock-key text-gray-400 mr-2 text-xl"></i> Private Admin Chat
            </h3>
            <div class="bg-gray-50 p-4 rounded-lg h-48 overflow-y-auto mb-4 border border-gray-100 space-y-4">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-navy-900 text-white flex items-center justify-center font-bold text-xs shrink-0">S</div>
                    <div class="bg-white p-3 rounded-lg rounded-tl-none border border-gray-200 text-sm text-gray-700 shadow-sm">
                        I think the theology is sound, but we definitely need him to fix that audio clipping before we approve.
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <input type="text" placeholder="Message other reviewers..." class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                <button class="bg-gray-200 hover:bg-gray-300 text-navy-900 px-4 py-2 rounded-lg font-bold transition-colors">
                    <i class="ph-bold ph-paper-plane-right"></i>
                </button>
            </div>
        </div>

    </div>

    <div class="space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4 border-b border-gray-100 pb-3">Approval Consensus</h3>
            <p class="text-xs text-gray-500 mb-4">3 approvals required to publish (2 Reviewers, 1 Author/Admin).</p>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-200 text-green-800 flex items-center justify-center font-bold text-xs">J</div>
                        <div>
                            <p class="text-sm font-bold text-navy-900 leading-none">John Doe</p>
                            <p class="text-xs text-gray-500 mt-0.5">Reviewer 1</p>
                        </div>
                    </div>
                    <i class="ph-fill ph-check-circle text-green-500 text-xl" title="Approved"></i>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-navy-100 text-navy-900 flex items-center justify-center font-bold text-xs">
                            <i class="ph-fill ph-user"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-navy-900 leading-none">You</p>
                            <p class="text-xs text-gray-500 mt-0.5">Reviewer 2</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-400">PENDING</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-xs">S</div>
                        <div>
                            <p class="text-sm font-bold text-navy-900 leading-none">Sarah</p>
                            <p class="text-xs text-gray-500 mt-0.5">Super Admin</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-400">PENDING</span>
                </div>
            </div>
        </div>

        <div class="bg-navy-900 rounded-xl shadow-md border border-navy-800 p-6">
            <h3 class="text-lg font-bold text-white mb-2">Make a Decision</h3>
            <p class="text-xs text-gray-400 mb-6">Your action will be logged. Soft rejects will notify the uploader.</p>

            <div class="space-y-3">
                <button class="w-full flex items-center justify-center py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition-colors">
                    <i class="ph-bold ph-check-circle mr-2 text-lg"></i> Approve Podcast
                </button>
                
                <button class="w-full flex items-center justify-center py-3 px-4 bg-yellow-500 hover:bg-yellow-600 text-navy-900 font-bold rounded-lg transition-colors">
                    <i class="ph-bold ph-pencil-simple mr-2 text-lg"></i> Request Changes
                </button>

                <button class="w-full flex items-center justify-center py-3 px-4 bg-transparent border border-red-500/50 hover:bg-red-500/10 text-red-400 font-bold rounded-lg transition-colors mt-6">
                    <i class="ph-bold ph-x-circle mr-2 text-lg"></i> Hard Reject (Flag)
                </button>
            </div>
        </div>

    </div>
</div>

<div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t border-gray-200 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.1)] z-40 p-4" x-data="audioPlayer()">
    
    <audio id="review-audio" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" @timeupdate="updateProgress()" @loadedmetadata="setDuration()"></audio>

    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center gap-4">
        
        <div class="flex items-center gap-4 text-navy-900">
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
                <div class="absolute top-0 left-0 h-full bg-gold-500 rounded-full" :style="'width: ' + progressPercent + '%'"></div>
                <div class="absolute top-1/2 -mt-1.5 w-3 h-3 bg-navy-900 rounded-full shadow" :style="'left: calc(' + progressPercent + '% - 6px)'"></div>
            </div>
            
            <span x-text="formatTime(duration)">00:00</span>
        </div>
    </div>
</div>

<script>
    // Audio Player Engine
    document.addEventListener('alpine:init', () => {
        Alpine.data('audioPlayer', () => ({
            audio: null,
            playing: false,
            currentTime: 0,
            duration: 0,
            progressPercent: 0,
            speed: 1,

            init() {
                this.audio = document.getElementById('review-audio');
                // Ensure duration is set if audio is already loaded from cache
                if (this.audio.readyState >= 1) this.setDuration();
            },
            togglePlay() {
                if (this.playing) {
                    this.audio.pause();
                } else {
                    this.audio.play();
                }
                this.playing = !this.playing;
            },
            rewind() {
                this.audio.currentTime = Math.max(0, this.audio.currentTime - 15);
            },
            changeSpeed() {
                const speeds = [1, 1.25, 1.5, 2];
                let nextIndex = speeds.indexOf(this.speed) + 1;
                if (nextIndex >= speeds.length) nextIndex = 0;
                this.speed = speeds[nextIndex];
                this.audio.playbackRate = this.speed;
            },
            updateProgress() {
                this.currentTime = this.audio.currentTime;
                if (this.duration > 0) {
                    this.progressPercent = (this.currentTime / this.duration) * 100;
                }
            },
            setDuration() {
                this.duration = this.audio.duration;
            },
            seek(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const clickX = event.clientX - rect.left;
                const width = rect.width;
                const newTime = (clickX / width) * this.duration;
                this.audio.currentTime = newTime;
            },
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return "00:00";
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = Math.floor(seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            }
        }));

        // Notes Engine (Interacts with Player)
        Alpine.data('noteEngine', () => ({
            currentTime: 0,
            
            // This grabs the current time straight from the audio DOM element
            captureTime() {
                const audioEl = document.getElementById('review-audio');
                if(audioEl) {
                    this.currentTime = audioEl.currentTime;
                }
            },
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return "00:00";
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = Math.floor(seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            }
        }));
    });
</script>

<?= $this->endSection() ?>
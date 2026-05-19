<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <a href="<?= site_url('admin/reviews') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors">
            <i class="ph-bold ph-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Review Room</h1>
            <p class="text-sm text-gray-500 mt-1">Evaluating: <span class="font-semibold text-navy-900"><?= esc($podcast['title']) ?></span></p>
        </div>
    </div>
    
    <span id="global-status-badge" class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full border 
        <?= $podcast['status'] === 'in_review' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-green-100 text-green-800 border-green-200' ?>">
        <?= $podcast['status'] === 'in_review' ? 'Under Review' : ucfirst($podcast['status']) ?>
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-32">

    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="w-32 h-32 flex-shrink-0 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center">
                    <?php if($podcast['cover_image_url']): ?>
                        <img src="<?= base_url('uploads/covers/' . $podcast['cover_image_url']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="ph-fill ph-microphone-stage text-4xl text-gray-300"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-navy-900 leading-tight"><?= esc($podcast['title']) ?></h2>
                    <div class="mt-4 text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg border border-gray-100 whitespace-pre-line">
                        <?= esc($podcast['description']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="noteEngine(<?= $podcast['id'] ?>)">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-4">
                <h3 class="text-lg font-bold text-navy-900 flex items-center">
                    <i class="ph-fill ph-clock-user text-gold-500 mr-2 text-xl"></i> Timestamped Notes
                </h3>
            </div>

            <form @submit.prevent="submitNote" class="flex gap-3 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <button @click="captureTime()" type="button" class="flex-shrink-0 bg-navy-900 hover:bg-navy-800 text-white px-3 py-2 rounded-lg text-sm font-bold flex flex-col items-center justify-center transition-colors">
                    <i class="ph-bold ph-crosshair text-lg mb-0.5"></i>
                    <span x-text="formatTime(currentTime)">00:00</span>
                </button>
                <div class="flex-1">
                    <input type="text" x-model="newNote" required placeholder="Audio spikes here..." class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                </div>
                <button type="submit" :disabled="loading" class="bg-gray-200 hover:bg-gray-300 text-navy-900 px-4 py-2 rounded-lg font-bold transition-colors disabled:opacity-50">
                    <span x-show="!loading">Add Note</span>
                    <span x-show="loading">Adding...</span>
                </button>
            </form>
            
            <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                <template x-for="note in notes" :key="note.id">
                    <div class="flex gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                        <button @click="$dispatch('seek-audio', note.timestamp)" class="text-gold-600 font-mono font-bold bg-gold-50 px-2 py-1 rounded text-sm hover:bg-gold-100 h-fit transition-colors">
                            <span x-text="'[' + formatTime(note.timestamp) + ']'"></span>
                        </button>
                        <div>
                            <p class="text-sm text-gray-800" x-text="note.note"></p>
                            <p class="text-xs text-gray-400 mt-1">
                                Added by <span x-text="note.first_name"></span> • <span x-text="timeAgo(note.created_at)"></span>
                            </p>
                        </div>
                    </div>
                </template>
                <div x-show="notes.length === 0" class="text-center text-sm text-gray-500 py-4">No notes added yet.</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="chatEngine(<?= $podcast['id'] ?>)">
            <h3 class="text-lg font-bold text-navy-900 flex items-center mb-4">
                <i class="ph-fill ph-lock-key text-gray-400 mr-2 text-xl"></i> Private Admin Chat
            </h3>
            
            <div class="bg-gray-50 p-4 rounded-lg h-48 overflow-y-auto mb-4 border border-gray-100 space-y-4" id="chatContainer">
                <template x-for="chat in chats" :key="chat.id">
                    <div class="flex gap-3" :class="chat.user_id == myId ? 'flex-row-reverse' : ''">
                        <div class="w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-xs shrink-0"
                             :class="chat.user_id == myId ? 'bg-gold-500 text-navy-900' : 'bg-navy-900'">
                            <span x-text="chat.first_name.charAt(0)"></span>
                        </div>
                        <div class="p-3 rounded-lg border border-gray-200 text-sm text-gray-700 shadow-sm"
                             :class="chat.user_id == myId ? 'bg-gold-50 rounded-tr-none' : 'bg-white rounded-tl-none'">
                            <p x-text="chat.message"></p>
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="submitChat" class="flex gap-2">
                <input type="text" x-model="newMessage" required placeholder="Discuss internally..." class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                <button type="submit" :disabled="loading" class="bg-gray-200 hover:bg-gray-300 text-navy-900 px-4 py-2 rounded-lg font-bold transition-colors disabled:opacity-50">
                    <i class="ph-bold ph-paper-plane-right"></i>
                </button>
            </form>
        </div>

    </div>

    <div class="space-y-6">
        
        <div class="bg-navy-900 rounded-xl shadow-md border border-navy-800 p-6" x-data="decisionEngine(<?= $podcast['id'] ?>)">
            <h3 class="text-lg font-bold text-white mb-2">Submit Decision</h3>
            <p class="text-xs text-gray-400 mb-6">Instantly updates the ledger. Auto-publishes at 3 approvals.</p>

            <div x-show="successMessage" style="display:none;" class="mb-4 text-center p-3 bg-green-500/20 rounded-lg border border-green-500/30 text-green-400 text-sm font-bold">
                <i class="ph-fill ph-check-circle mr-1"></i> <span x-text="successMessage"></span>
            </div>

            <div class="space-y-3" x-show="!isApproved">
                <button @click="submitDecision('approved')" :disabled="loading" class="w-full flex items-center justify-center py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition-colors shadow-sm disabled:opacity-50">
                    <i class="ph-bold ph-check-circle mr-2 text-lg"></i> Approve Podcast
                </button>
                
                <button @click="submitDecision('changes_requested')" :disabled="loading" class="w-full flex items-center justify-center py-3 px-4 bg-yellow-500 hover:bg-yellow-600 text-navy-900 font-bold rounded-lg transition-colors shadow-sm disabled:opacity-50">
                    <i class="ph-bold ph-pencil-simple mr-2 text-lg"></i> Request Changes
                </button>

                <button @click="if(confirm('Are you sure?')) submitDecision('rejected')" :disabled="loading" class="w-full flex items-center justify-center py-3 px-4 bg-transparent border border-red-500/50 hover:bg-red-500/10 text-red-400 font-bold rounded-lg transition-colors mt-6 disabled:opacity-50">
                    <i class="ph-bold ph-x-circle mr-2 text-lg"></i> Hard Reject (Flag)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t border-gray-200 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.1)] z-40 p-4" 
     x-data="audioPlayer('<?= esc($highUrl, 'js') ?>', '<?= esc($lowUrl ?? '', 'js') ?>')"
     @seek-audio.window="forceSeek($event.detail)"> <audio x-ref="audioEl" :src="currentSrc" @timeupdate="updateProgress()" @loadedmetadata="setDuration()"></audio>

    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center gap-4">
        <div class="flex items-center gap-4 text-navy-900">
            <button @click="togglePlay()" class="w-12 h-12 bg-gold-500 hover:bg-gold-600 text-navy-900 rounded-full flex items-center justify-center transition-colors shadow-md">
                <i class="ph-fill text-2xl" :class="playing ? 'ph-pause' : 'ph-play'"></i>
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
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        
        // 1. NOTES ENGINE
        Alpine.data('noteEngine', (podcastId) => ({
            notes: [],
            newNote: '',
            currentTime: 0,
            rawTime: 0,
            loading: false,

            init() { this.fetchNotes(); },
            
            async fetchNotes() {
                const res = await fetch(`<?= site_url('admin/reviews/api/notes/') ?>${podcastId}`);
                this.notes = await res.json();
            },
            
            async submitNote() {
                if(!this.newNote.trim()) return;
                this.loading = true;
                await fetch(`<?= site_url('admin/reviews/api/notes/') ?>${podcastId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ timestamp: this.rawTime, note: this.newNote })
                });
                this.newNote = '';
                await this.fetchNotes();
                this.loading = false;
            },

            captureTime() {
                const audioEl = document.querySelector('audio');
                if(audioEl) {
                    this.currentTime = audioEl.currentTime;
                    this.rawTime = Math.floor(audioEl.currentTime);
                }
            },
            
            formatTime(s) {
                const m = Math.floor(s / 60).toString().padStart(2, '0');
                const sec = Math.floor(s % 60).toString().padStart(2, '0');
                return `${m}:${sec}`;
            },
            timeAgo(dateStr) { return "Just now"; } // Simplified for UI
        }));

        // 2. CHAT ENGINE
        Alpine.data('chatEngine', (podcastId) => ({
            chats: [],
            myId: null,
            newMessage: '',
            loading: false,

            init() { 
                this.fetchChats(); 
                setInterval(() => this.fetchChats(), 5000); // Auto-poll every 5s like WhatsApp!
            },

            async fetchChats() {
                const res = await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`);
                const data = await res.json();
                this.chats = data.chats;
                this.myId = data.me;
                // Auto scroll to bottom
                this.$nextTick(() => {
                    const container = document.getElementById('chatContainer');
                    container.scrollTop = container.scrollHeight;
                });
            },

            async submitChat() {
                if(!this.newMessage.trim()) return;
                this.loading = true;
                await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: this.newMessage })
                });
                this.newMessage = '';
                await this.fetchChats();
                this.loading = false;
            }
        }));

        // 3. DECISION ENGINE
        Alpine.data('decisionEngine', (podcastId) => ({
            loading: false,
            isApproved: <?= $myStatus === 'approved' ? 'true' : 'false' ?>,
            successMessage: '',

            async submitDecision(decision) {
                this.loading = true;
                const res = await fetch(`<?= site_url('admin/reviews/api/decision/') ?>${podcastId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ decision: decision })
                });
                const data = await res.json();
                
                if(decision === 'approved') {
                    this.isApproved = true;
                    this.successMessage = "Decision recorded successfully.";
                } else {
                    this.successMessage = "Changes requested. Author notified.";
                }

                if(data.is_published) {
                    document.getElementById('global-status-badge').innerText = "Published";
                    document.getElementById('global-status-badge').className = "px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full border bg-green-100 text-green-800 border-green-200";
                }
                
                setTimeout(() => location.reload(), 2000); // Reload to update UI Ledger cleanly
            }
        }));

        // 4. AUDIO PLAYER (Slightly Minified)
        Alpine.data('audioPlayer', (highUrl, lowUrl) => ({
            audio: null, playing: false, currentTime: 0, duration: 0, progressPercent: 0, currentSrc: highUrl,
            init() { this.audio = this.$refs.audioEl; },
            togglePlay() { this.playing ? this.audio.pause() : this.audio.play(); this.playing = !this.playing; },
            updateProgress() { this.currentTime = this.audio.currentTime; if (this.duration > 0) this.progressPercent = (this.currentTime / this.duration) * 100; },
            setDuration() { this.duration = this.audio.duration; },
            seek(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                this.audio.currentTime = ((event.clientX - rect.left) / rect.width) * this.duration;
            },
            // MAGIC: Receives the event from the Notes Engine!
            forceSeek(seconds) {
                this.audio.currentTime = seconds;
                if(!this.playing) this.togglePlay();
            },
            formatTime(s) { return `${Math.floor(s / 60).toString().padStart(2, '0')}:${Math.floor(s % 60).toString().padStart(2, '0')}`; }
        }));
    });
</script>

<?= $this->endSection() ?>
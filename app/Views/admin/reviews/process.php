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
    
    <?php if($podcast['status'] === 'in_review'): ?>
        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
            <i class="ph-fill ph-magnifying-glass mr-1.5 mt-0.5"></i> Under Review
        </span>
    <?php else: ?>
        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
            <i class="ph-fill ph-check-circle mr-1.5 mt-0.5"></i> <?= ucfirst($podcast['status']) ?>
        </span>
    <?php endif; ?>
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
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex gap-2 mb-2">
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold uppercase rounded"><?= esc($podcast['category_name'] ?? 'Uncategorized') ?></span>
                            </div>
                            <h2 class="text-xl font-bold text-navy-900 leading-tight"><?= esc($podcast['title']) ?></h2>
                            <p class="text-sm text-gray-500 mt-1 flex items-center">
                                <i class="ph-fill ph-users mr-1.5 text-gold-500"></i>
                                <?php 
                                    $authorNames = array_map(fn($a) => $a['first_name'] . ' ' . $a['last_name'], $authors);
                                    echo esc(implode(', ', $authorNames)); 
                                ?>
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 items-end">
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
                    
                    <div class="mt-4 text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg border border-gray-100 whitespace-pre-line">
                        <?= esc($podcast['description']) ?>
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
                    <p class="text-xs text-gray-500 mt-1">Leave exact markers for edits or corrections before requesting changes.</p>
                </div>
            </div>

            <form action="<?= site_url('admin/reviews/add_note/'.$podcast['id']) ?>" method="POST" class="flex gap-3 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <?= csrf_field() ?>
                <input type="hidden" name="timestamp" x-model="rawTime">
                
                <button @click="captureTime()" type="button" class="flex-shrink-0 bg-navy-900 hover:bg-navy-800 text-white px-3 py-2 rounded-lg text-sm font-bold flex flex-col items-center justify-center transition-colors tooltip" title="Grab current audio time">
                    <i class="ph-bold ph-crosshair text-lg mb-0.5"></i>
                    <span x-text="formatTime(currentTime)">00:00</span>
                </button>
                <div class="flex-1">
                    <input type="text" name="note" required placeholder="E.g., Audio spikes here, or check theology..." class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                </div>
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-navy-900 px-4 py-2 rounded-lg font-bold transition-colors">
                    Add Note
                </button>
            </form>
            
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
            <p class="text-xs text-gray-500 mb-4">Discuss this teaching internally. The author cannot see these messages.</p>
            
            <div class="bg-gray-50 p-4 rounded-lg h-48 overflow-y-auto mb-4 border border-gray-100 space-y-4">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-navy-900 text-white flex items-center justify-center font-bold text-xs shrink-0">S</div>
                    <div class="bg-white p-3 rounded-lg rounded-tl-none border border-gray-200 text-sm text-gray-700 shadow-sm">
                        I think the theology is sound, but we definitely need him to fix that audio clipping before we approve.
                    </div>
                </div>
            </div>

            <form action="<?= site_url('admin/reviews/add_chat/'.$podcast['id']) ?>" method="POST" class="flex gap-2">
                <?= csrf_field() ?>
                <input type="text" name="message" required placeholder="Message other reviewers..." class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-navy-900 px-4 py-2 rounded-lg font-bold transition-colors">
                    <i class="ph-bold ph-paper-plane-right"></i>
                </button>
            </form>
        </div>

    </div>

    <div class="space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4 border-b border-gray-100 pb-3">Approval Ledger</h3>
            <p class="text-xs text-gray-500 mb-4">Current Approvals: <span class="font-bold text-navy-900"><?= $podcast['review_count'] ?> / 3</span></p>
            
            <div class="space-y-4">
                <?php if(empty($reviews)): ?>
                    <p class="text-sm text-gray-500 text-center py-4 bg-gray-50 rounded-lg border border-gray-100 dashed">No reviews submitted yet.</p>
                <?php else: ?>
                    <?php foreach($reviews as $rev): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg border 
                            <?= $rev['status'] == 'approved' ? 'bg-green-50 border-green-100' : 
                               ($rev['status'] == 'changes_requested' ? 'bg-yellow-50 border-yellow-100' : 
                               'bg-red-50 border-red-100') ?>">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center font-bold text-xs text-navy-900 shadow-sm">
                                    <?= substr($rev['first_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-navy-900 leading-none"><?= esc($rev['first_name'] . ' ' . $rev['last_name']) ?></p>
                                    <p class="text-xs text-gray-500 mt-0.5 capitalize"><?= esc($rev['role']) ?></p>
                                </div>
                            </div>
                            
                            <?php if($rev['status'] == 'approved'): ?>
                                <i class="ph-fill ph-check-circle text-green-500 text-xl" title="Approved"></i>
                            <?php elseif($rev['status'] == 'changes_requested'): ?>
                                <i class="ph-fill ph-pencil-circle text-yellow-500 text-xl" title="Changes Requested"></i>
                            <?php else: ?>
                                <i class="ph-fill ph-x-circle text-red-500 text-xl" title="Rejected"></i>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-navy-900 rounded-xl shadow-md border border-navy-800 p-6">
            <h3 class="text-lg font-bold text-white mb-2">Submit Decision</h3>
            <p class="text-xs text-gray-400 mb-6">Your action will be logged in the permanent audit trail.</p>

            <form action="<?= site_url('admin/reviews/submit_decision/'.$podcast['id']) ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                
                <?php if($myStatus === 'approved'): ?>
                    <div class="text-center p-3 bg-green-500/20 rounded-lg border border-green-500/30 text-green-400 text-sm font-bold">
                        <i class="ph-fill ph-check-circle mr-1"></i> You have approved this podcast.
                    </div>
                <?php else: ?>
                    <button type="submit" name="decision" value="approved" class="w-full flex items-center justify-center py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition-colors shadow-sm">
                        <i class="ph-bold ph-check-circle mr-2 text-lg"></i> Approve Podcast
                    </button>
                    
                    <button type="submit" name="decision" value="changes_requested" class="w-full flex items-center justify-center py-3 px-4 bg-yellow-500 hover:bg-yellow-600 text-navy-900 font-bold rounded-lg transition-colors shadow-sm">
                        <i class="ph-bold ph-pencil-simple mr-2 text-lg"></i> Request Changes
                    </button>

                    <button type="submit" name="decision" value="rejected" class="w-full flex items-center justify-center py-3 px-4 bg-transparent border border-red-500/50 hover:bg-red-500/10 text-red-400 font-bold rounded-lg transition-colors mt-6" onclick="return confirm('Are you sure you want to hard reject? This will flag the podcast.')">
                        <i class="ph-bold ph-x-circle mr-2 text-lg"></i> Hard Reject (Flag)
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t border-gray-200 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.1)] z-40 p-4" 
     x-data="audioPlayer('<?= esc($highUrl, 'js') ?>', '<?= esc($lowUrl ?? '', 'js') ?>')">
    
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
            <i class="ph-bold ph-warning-circle mr-1"></i> Audio Failed (Check Console)
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        
        Alpine.data('audioPlayer', (highUrl, lowUrl) => ({
            audio: null,
            playing: false,
            currentTime: 0,
            duration: 0,
            progressPercent: 0,
            speed: 1,
            hasError: false,
            
            quality: 'high',
            highUrl: highUrl,
            lowUrl: lowUrl,
            currentSrc: highUrl,

            init() {
                this.audio = this.$refs.audioEl;
                if (!this.currentSrc || this.currentSrc.trim() === '') {
                    this.hasError = true;
                    console.error("Audio src is empty!");
                }
            },
            handleError() {
                this.hasError = true;
                this.playing = false;
                console.error("HTML5 Audio Error! The browser cannot play this URL. It might be a CORS issue, a 403 Forbidden (missing User-Agent), or an unsupported format like .m3u8.");
            },
            toggleQuality() {
                if(!this.lowUrl || this.lowUrl.trim() === '') {
                    alert("No Low Quality version available for this track.");
                    return;
                }
                
                const timeStore = this.audio.currentTime;
                const wasPlaying = this.playing;
                
                this.quality = this.quality === 'high' ? 'low' : 'high';
                this.currentSrc = this.quality === 'high' ? this.highUrl : this.lowUrl;
                
                this.$nextTick(() => {
                    this.hasError = false; // Reset error state on switch
                    this.audio.load();
                    setTimeout(() => {
                        this.audio.currentTime = timeStore;
                        if(wasPlaying) this.audio.play();
                    }, 50);
                });
            },
            togglePlay() {
                if (this.hasError) {
                    alert("Cannot play audio. Please check the browser console for network or CORS errors.");
                    return;
                }
                if (this.playing) this.audio.pause();
                else this.audio.play();
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
                if (this.duration > 0) this.progressPercent = (this.currentTime / this.duration) * 100;
            },
            setDuration() {
                this.duration = this.audio.duration;
            },
            seek(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const clickX = event.clientX - rect.left;
                const newTime = (clickX / rect.width) * this.duration;
                this.audio.currentTime = newTime;
            },
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return "00:00";
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = Math.floor(seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            }
        }));

        Alpine.data('noteEngine', () => ({
            currentTime: 0,
            rawTime: 0,
            captureTime() {
                const audioEl = document.querySelector('audio');
                if(audioEl) {
                    this.currentTime = audioEl.currentTime;
                    this.rawTime = Math.floor(audioEl.currentTime);
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
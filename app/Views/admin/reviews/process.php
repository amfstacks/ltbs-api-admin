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
                        <img src="<?= media_url($podcast['cover_image_url']) ?>" class="w-full h-full object-cover">
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

       <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col h-[400px]" x-data='chatEngine(<?= $podcast['id'] ?>, <?= $mentionableUsers ?? "[]" ?>)'>   <h3 class="text-lg font-bold text-navy-900 flex items-center mb-4 flex-shrink-0">
                <i class="ph-fill ph-lock-key text-gray-400 mr-2 text-xl"></i> Private Admin Chat
            </h3>
            
            <div class="bg-gray-50 p-4 rounded-lg flex-1 overflow-y-auto mb-4 border border-gray-100 space-y-4" id="chatContainer">
                <template x-for="chat in chats" :key="chat.id">
                    <div class="flex gap-3 group" :class="chat.user_id == myId ? 'flex-row-reverse' : ''">
                        <div class="w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-xs shrink-0"
                             :class="chat.user_id == myId ? 'bg-gold-500 text-navy-900' : 'bg-navy-900'">
                            <span x-text="chat.first_name.charAt(0)"></span>
                        </div>
                        
                        <div class="flex flex-col" :class="chat.user_id == myId ? 'items-end' : 'items-start'">
                            
                            <div class="p-3 rounded-lg border border-gray-200 text-sm text-gray-700 shadow-sm relative max-w-[90%]"
                                 :class="chat.user_id == myId ? 'bg-gold-50 rounded-tr-none' : 'bg-white rounded-tl-none'">
                                
                                <template x-if="chat.reply_to_message">
                                    <div class="mb-2 p-2 bg-white/50 rounded border-l-2 border-gold-500 text-xs text-gray-500">
                                        <span class="font-bold text-navy-900" x-text="chat.reply_to_name + ':'"></span>
                                        <span x-text="chat.reply_to_message.substring(0, 40) + '...'"></span>
                                    </div>
                                </template>

                                <p x-html="formatMessage(chat.message)"></p>
                            </div>
                            
                            <button @click="setReply(chat)" class="text-xs text-gray-400 hover:text-navy-900 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="ph-bold ph-arrow-u-up-left mr-1"></i> Reply
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="relative flex-shrink-0">
                
                <div x-show="showMentions" style="display:none;" class="absolute bottom-full left-0 w-64 mb-2 bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden">
                    <ul class="py-1 max-h-48 overflow-y-auto">
                        <template x-for="user in filteredMentions" :key="user.id">
                            <li @click="insertMention(user)" class="px-4 py-2 text-sm text-gray-700 hover:bg-gold-50 hover:text-navy-900 cursor-pointer flex items-center">
                                <div class="w-6 h-6 rounded-full bg-navy-100 text-navy-900 flex items-center justify-center text-xs font-bold mr-2" x-text="user.name.charAt(0)"></div>
                                <span x-text="user.name"></span>
                            </li>
                        </template>
                        <li x-show="filteredMentions.length === 0" class="px-4 py-2 text-xs text-gray-500">No reviewers found</li>
                    </ul>
                </div>

                <div x-show="replyingTo" style="display:none;" class="mb-2 px-3 py-2 bg-gray-100 rounded-lg text-xs text-gray-600 flex justify-between items-center border border-gray-200">
                    <div class="truncate mr-4">
                        <i class="ph-bold ph-arrow-u-up-left text-navy-900 mr-1"></i>
                        Replying to <span class="font-bold text-navy-900" x-text="replyingTo?.name"></span>: <span x-text="replyingTo?.text.substring(0, 30) + '...'"></span>
                    </div>
                    <button @click="clearReply()" type="button" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x"></i></button>
                </div>

                <form @submit.prevent="submitChat" class="flex gap-2">
                    <input type="text" x-ref="chatInput" x-model="newMessage" @input="checkMentions" required placeholder="Type @ to mention someone..." autocomplete="off" class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                    <button type="submit" :disabled="loading" class="bg-navy-900 hover:bg-navy-800 text-white px-4 py-2 rounded-lg font-bold transition-colors disabled:opacity-50 shadow-sm">
                        <i class="ph-bold ph-paper-plane-tilt"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <div class="space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4 border-b border-gray-100 pb-3">Approval Consensus</h3>
            <p class="text-xs text-gray-500 mb-4">3 approvals required to publish (2 Reviewers, 1 Author/Admin).</p>
            
            <div class="space-y-4">
                <?php 
                    $reviewCount = count($reviews); 
                ?>
                
                <?php foreach($reviews as $rev): ?>
                    <?php 
                        $isApproved = $rev['status'] === 'approved';
                        $isChanges = $rev['status'] === 'changes_requested';
                        
                        $bgClass = $isApproved ? 'bg-green-50 border-green-100' : ($isChanges ? 'bg-yellow-50 border-yellow-100' : 'bg-red-50 border-red-100');
                        $iconClass = $isApproved ? 'ph-check-circle text-green-500' : ($isChanges ? 'ph-pencil-circle text-yellow-500' : 'ph-x-circle text-red-500');
                    ?>
                    <div class="flex items-center justify-between p-3 rounded-lg border <?= $bgClass ?>">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center font-bold text-xs text-navy-900 shadow-sm">
                                <?= strtoupper(substr($rev['first_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-navy-900 leading-none">
                                    <?= esc($rev['first_name'] . ' ' . $rev['last_name']) ?>
                                    <?php if($rev['user_id'] == session()->get('user_id')): ?>
                                        <span class="text-[10px] bg-navy-900 text-white px-1.5 py-0.5 rounded ml-1">YOU</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5 capitalize"><?= esc($rev['role']) ?></p>
                            </div>
                        </div>
                        <i class="ph-fill <?= $iconClass ?> text-xl" title="<?= ucfirst(str_replace('_', ' ', $rev['status'])) ?>"></i>
                    </div>
                <?php endforeach; ?>

                <?php 
                    $pendingSlots = max(0, 3 - $reviewCount);
                    for ($i = 0; $i < $pendingSlots; $i++): 
                ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed opacity-70">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-xs">
                                <i class="ph-bold ph-hourglass-high"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-500 leading-none">Awaiting Review</p>
                                <p class="text-xs text-gray-400 mt-0.5">Reviewer/Admin</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-400">PENDING</span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- <div class="bg-navy-900 rounded-xl shadow-md border border-navy-800 p-6" x-data="decisionEngine(<?= $podcast['id'] ?>)">
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
        </div> -->
        <div class="bg-navy-900 rounded-xl shadow-md border border-navy-800 p-6 relative" x-data="decisionEngine(<?= $podcast['id'] ?>)">
            <h3 class="text-lg font-bold text-white mb-2">Submit Decision</h3>
            <p class="text-xs text-gray-400 mb-6">Instantly updates the ledger. Auto-publishes at 3 approvals.</p>

            <div x-show="successMessage" style="display:none;" class="mb-4 text-center p-3 bg-green-500/20 rounded-lg border border-green-500/30 text-green-400 text-sm font-bold">
                <i class="ph-fill ph-check-circle mr-1"></i> <span x-text="successMessage"></span>
            </div>

            <div class="space-y-3" x-show="!isApproved && !showModal">
                <button @click="submitDecision('approved')" :disabled="loading" class="w-full flex items-center justify-center py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition-colors shadow-sm disabled:opacity-50">
                    <i class="ph-bold ph-check-circle mr-2 text-lg"></i> Approve Podcast
                </button>
                
                <button @click="openModal('changes_requested')" :disabled="loading" class="w-full flex items-center justify-center py-3 px-4 bg-yellow-500 hover:bg-yellow-600 text-navy-900 font-bold rounded-lg transition-colors shadow-sm disabled:opacity-50">
                    <i class="ph-bold ph-pencil-simple mr-2 text-lg"></i> Request Changes
                </button>

                <button @click="openModal('rejected')" :disabled="loading" class="w-full flex items-center justify-center py-3 px-4 bg-transparent border border-red-500/50 hover:bg-red-500/10 text-red-400 font-bold rounded-lg transition-colors mt-6 disabled:opacity-50">
                    <i class="ph-bold ph-x-circle mr-2 text-lg"></i> Hard Reject (Flag)
                </button>
            </div>

            <div x-show="showModal" style="display:none;" class="absolute inset-0 bg-navy-900 rounded-xl p-6 flex flex-col z-10">
                <h4 class="text-white font-bold mb-2 flex items-center">
                    <i class="ph-fill mr-2" :class="pendingDecision === 'rejected' ? 'ph-x-circle text-red-500' : 'ph-pencil-circle text-yellow-500'"></i>
                    <span x-text="pendingDecision === 'rejected' ? 'Reason for Rejection' : 'Required Changes'"></span>
                </h4>
                <p class="text-xs text-gray-400 mb-4">This will be permanently logged in the audit trail.</p>
                
                <textarea x-model="decisionNotes" x-ref="notesInput" rows="4" class="w-full px-3 py-2 text-sm border border-navy-700 bg-navy-800 text-white rounded-lg focus:ring-gold-500 focus:border-gold-500 mb-4 resize-none" placeholder="Provide clear feedback or refer to timestamped notes..."></textarea>
                
                <div class="flex gap-3 mt-auto">
                    <button @click="showModal = false; decisionNotes = ''" class="flex-1 py-2 bg-navy-800 hover:bg-navy-700 text-gray-300 font-bold rounded-lg transition-colors text-sm">Cancel</button>
                    <button @click="submitDecision(pendingDecision)" :disabled="!decisionNotes.trim() || loading" class="flex-1 py-2 font-bold rounded-lg transition-colors text-sm disabled:opacity-50 text-navy-900" :class="pendingDecision === 'rejected' ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-yellow-500 hover:bg-yellow-600'">
                        <span x-show="!loading">Submit</span>
                        <span x-show="loading">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
            <i class="ph-bold ph-warning-circle mr-1"></i> Audio Failed (Check Console)
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        
        // 1. NOTES ENGINE
        Alpine.data('noteEngine', (podcastId) => ({
            notes: [], newNote: '', currentTime: 0, rawTime: 0, loading: false,
            init() { this.fetchNotes(); },
            async fetchNotes() {
                const res = await fetch(`<?= site_url('admin/reviews/api/notes/') ?>${podcastId}`);
                this.notes = await res.json();
            },
            async submitNote() {
                if(!this.newNote.trim()) return;
                this.loading = true;
                await fetch(`<?= site_url('admin/reviews/api/notes/') ?>${podcastId}`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ timestamp: this.rawTime, note: this.newNote })
                });
                this.newNote = ''; await this.fetchNotes(); this.loading = false;
            },
            captureTime() {
                const audioEl = document.querySelector('audio');
                if(audioEl) { this.currentTime = audioEl.currentTime; this.rawTime = Math.floor(audioEl.currentTime); }
            },
            formatTime(s) { return `${Math.floor(s / 60).toString().padStart(2, '0')}:${Math.floor(s % 60).toString().padStart(2, '0')}`; },
            timeAgo(dateStr) { return "Just now"; } 
        }));

        // 2. CHAT ENGINE
        // 2. CHAT ENGINE (Upgraded with Threads & Mentions)
        // 2. CHAT ENGINE (Upgraded with Error Handling)
        Alpine.data('chatEngine', (podcastId, mentionableUsers) => ({
            chats: [], myId: null, newMessage: '', loading: false,
            
            // Threading State
            replyingTo: null, 
            
            // Mentions State
            showMentions: false, 
            mentionQuery: '', 
            filteredMentions: [],

            init() { 
                this.fetchChats(); 
                setInterval(() => this.fetchChats(), 5000); 
            },

            async fetchChats() {
                try {
                    const res = await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`);
                    
                    // If the server crashes (e.g., missing SQL column), catch it immediately!
                    if (!res.ok) throw new Error("Server returned " + res.status);

                    const data = await res.json(); 
                    const shouldScroll = this.chats.length !== data.chats.length; 
                    this.chats = data.chats; 
                    this.myId = data.me;
                    
                    if(shouldScroll) {
                        this.$nextTick(() => { 
                            const c = document.getElementById('chatContainer'); 
                            if(c) c.scrollTop = c.scrollHeight; 
                        });
                    }
                } catch (error) {
                    console.error("Chat Fetch Error: Did you remember to run the ALTER TABLE SQL for reply_to_id?", error);
                }
            },

            async submitChat() {
                if(!this.newMessage.trim()) return;
                this.loading = true;
                
                const payload = { message: this.newMessage };
                if (this.replyingTo) payload.reply_to_id = this.replyingTo.id;

                try {
                    const res = await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) throw new Error("Failed to send message");

                    this.newMessage = ''; 
                    this.clearReply();
                    this.showMentions = false;
                    await this.fetchChats(); 
                } catch (error) {
                    console.error("Chat Submit Error:", error);
                    alert("Failed to send message. Check the console.");
                } finally {
                    this.loading = false;
                }
            },

            // --- REPLY LOGIC ---
            setReply(chat) {
                this.replyingTo = { id: chat.id, name: chat.first_name, text: chat.message };
                this.$refs.chatInput.focus();
            },
            clearReply() {
                this.replyingTo = null;
            },

            // --- MENTIONS LOGIC ---
            checkMentions(e) {
                const text = this.newMessage;
                const cursorPosition = e.target.selectionStart;
                const textBeforeCursor = text.substring(0, cursorPosition);
                
                const match = textBeforeCursor.match(/(?:\s|^)@([a-zA-Z0-9_]*)$/);

                // Ensure mentionableUsers is treated safely
                const safeUsers = Array.isArray(mentionableUsers) ? mentionableUsers : [];

                if (match) {
                    this.showMentions = true;
                    this.mentionQuery = match[1].toLowerCase();
                    this.filteredMentions = safeUsers.filter(u => u.name.toLowerCase().includes(this.mentionQuery));
                } else {
                    this.showMentions = false;
                }
            },
            insertMention(user) {
                const cursorPosition = this.$refs.chatInput.selectionStart;
                const textBeforeCursor = this.newMessage.substring(0, cursorPosition);
                const textAfterCursor = this.newMessage.substring(cursorPosition);

                const startReplace = textBeforeCursor.lastIndexOf('@');
                this.newMessage = this.newMessage.substring(0, startReplace) + '@' + user.name + ' ' + textAfterCursor;
                
                this.showMentions = false;
                this.$refs.chatInput.focus();
            },
            formatMessage(msg) {
                if(!msg) return '';
                return msg.replace(/(@[a-zA-Z0-9_ ]+)/g, '<strong class="text-navy-900">$1</strong>');
            }
        }));
        // Alpine.data('chatEngine', (podcastId, mentionableUsers) => ({
        //     chats: [], myId: null, newMessage: '', loading: false,
            
        //     // Threading State
        //     replyingTo: null, 
            
        //     // Mentions State
        //     showMentions: false, 
        //     mentionQuery: '', 
        //     filteredMentions: [],

        //     init() { 
        //         this.fetchChats(); 
        //         setInterval(() => this.fetchChats(), 5000); 
        //     },

        //     async fetchChats() {
        //         const res = await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`);
        //         const data = await res.json(); 
        //         const shouldScroll = this.chats.length !== data.chats.length; // Only scroll if new message arrives
        //         this.chats = data.chats; 
        //         this.myId = data.me;
                
        //         if(shouldScroll) {
        //             this.$nextTick(() => { const c = document.getElementById('chatContainer'); c.scrollTop = c.scrollHeight; });
        //         }
        //     },

        //     async submitChat() {
        //         if(!this.newMessage.trim()) return;
        //         this.loading = true;
                
        //         const payload = { message: this.newMessage };
        //         if (this.replyingTo) payload.reply_to_id = this.replyingTo.id;

        //         await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`, {
        //             method: 'POST', headers: { 'Content-Type': 'application/json' },
        //             body: JSON.stringify(payload)
        //         });
                
        //         this.newMessage = ''; 
        //         this.clearReply();
        //         this.showMentions = false;
        //         await this.fetchChats(); 
        //         this.loading = false;
        //     },

        //     // --- REPLY LOGIC ---
        //     setReply(chat) {
        //         this.replyingTo = { id: chat.id, name: chat.first_name, text: chat.message };
        //         this.$refs.chatInput.focus();
        //     },
        //     clearReply() {
        //         this.replyingTo = null;
        //     },

        //     // --- MENTIONS LOGIC ---
        //     checkMentions(e) {
        //         const text = this.newMessage;
        //         const cursorPosition = e.target.selectionStart;
        //         const textBeforeCursor = text.substring(0, cursorPosition);
                
        //         // Matches '@' followed by any letters/numbers immediately before the cursor
        //         const match = textBeforeCursor.match(/(?:\s|^)@([a-zA-Z0-9_]*)$/);

        //         if (match) {
        //             this.showMentions = true;
        //             this.mentionQuery = match[1].toLowerCase();
        //             // Filter the users passed from PHP
        //             this.filteredMentions = mentionableUsers.filter(u => u.name.toLowerCase().includes(this.mentionQuery));
        //         } else {
        //             this.showMentions = false;
        //         }
        //     },
        //     insertMention(user) {
        //         const cursorPosition = this.$refs.chatInput.selectionStart;
        //         const textBeforeCursor = this.newMessage.substring(0, cursorPosition);
        //         const textAfterCursor = this.newMessage.substring(cursorPosition);

        //         const startReplace = textBeforeCursor.lastIndexOf('@');
        //         // Insert the full name and a trailing space
        //         this.newMessage = this.newMessage.substring(0, startReplace) + '@' + user.name + ' ' + textAfterCursor;
                
        //         this.showMentions = false;
        //         this.$refs.chatInput.focus();
        //     },
        //     // Formats "@John Doe" into bold text for the UI
        //     formatMessage(msg) {
        //         return msg.replace(/(@[a-zA-Z0-9_ ]+)/g, '<strong class="text-navy-900">$1</strong>');
        //     }
        // }));
        // Alpine.data('chatEngine', (podcastId) => ({
        //     chats: [], myId: null, newMessage: '', loading: false,
        //     init() { this.fetchChats(); setInterval(() => this.fetchChats(), 5000); },
        //     async fetchChats() {
        //         const res = await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`);
        //         const data = await res.json(); this.chats = data.chats; this.myId = data.me;
        //         this.$nextTick(() => { const c = document.getElementById('chatContainer'); c.scrollTop = c.scrollHeight; });
        //     },
        //     async submitChat() {
        //         if(!this.newMessage.trim()) return;
        //         this.loading = true;
        //         await fetch(`<?= site_url('admin/reviews/api/chats/') ?>${podcastId}`, {
        //             method: 'POST', headers: { 'Content-Type': 'application/json' },
        //             body: JSON.stringify({ message: this.newMessage })
        //         });
        //         this.newMessage = ''; await this.fetchChats(); this.loading = false;
        //     }
        // }));
// 3. DECISION ENGINE (Upgraded with Modal Logic)
        Alpine.data('decisionEngine', (podcastId) => ({
            loading: false, 
            isApproved: <?= $myStatus === 'approved' ? 'true' : 'false' ?>, 
            successMessage: '',
            
            // Modal State
            showModal: false,
            pendingDecision: '',
            decisionNotes: '',

            openModal(decision) {
                this.pendingDecision = decision;
                this.showModal = true;
                this.$nextTick(() => { this.$refs.notesInput.focus(); });
            },

            async submitDecision(decision) {
                // If it's not an approval, ensure notes aren't empty
                if (decision !== 'approved' && !this.decisionNotes.trim()) {
                    alert("You must provide a reason.");
                    return;
                }

                this.loading = true;
                
                const payload = { 
                    decision: decision,
                    notes: decision === 'approved' ? null : this.decisionNotes 
                };

                const res = await fetch(`<?= site_url('admin/reviews/api/decision/') ?>${podcastId}`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                
                this.showModal = false;

                if(decision === 'approved') { 
                    this.isApproved = true; 
                    this.successMessage = "Decision recorded successfully."; 
                } else { 
                    this.successMessage = decision === 'rejected' ? "Podcast rejected. Author notified." : "Changes requested. Author notified."; 
                }
                
                if(data.is_published) {
                    const badge = document.getElementById('global-status-badge');
                    badge.innerText = "Published"; 
                    badge.className = "px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full border bg-green-100 text-green-800 border-green-200";
                }
                
                setTimeout(() => location.reload(), 2000);
            }
        }));
        // 3. DECISION ENGINE
        // Alpine.data('decisionEngine', (podcastId) => ({
        //     loading: false, isApproved: <?= $myStatus === 'approved' ? 'true' : 'false' ?>, successMessage: '',
        //     async submitDecision(decision) {
        //         this.loading = true;
        //         const res = await fetch(`<?= site_url('admin/reviews/api/decision/') ?>${podcastId}`, {
        //             method: 'POST', headers: { 'Content-Type': 'application/json' },
        //             body: JSON.stringify({ decision: decision })
        //         });
        //         const data = await res.json();
        //         if(decision === 'approved') { this.isApproved = true; this.successMessage = "Decision recorded successfully."; }
        //         else { this.successMessage = "Changes requested. Author notified."; }
        //         if(data.is_published) {
        //             const badge = document.getElementById('global-status-badge');
        //             badge.innerText = "Published"; badge.className = "px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full border bg-green-100 text-green-800 border-green-200";
        //         }
        //         setTimeout(() => location.reload(), 2000);
        //     }
        // }));

        // 4. FULL AUDIO PLAYER
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
                if(!this.lowUrl || this.lowUrl.trim() === '') { alert("No Low Quality version available for this track."); return; }
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

<?= $this->endSection() ?>
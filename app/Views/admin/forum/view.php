<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center">
      <a href="<?= site_url('admin/forum/podcast/' . $thread['podcast_id']) ?>" class="text-gray-500 hover:text-navy-900 mr-4"><i class="ph-bold ph-arrow-left text-xl"></i></a>  <div>
            <h1 class="text-xl font-bold text-navy-900"><?= esc($thread['title']) ?></h1>
            <p class="text-sm text-gray-500 mt-1">Podcast: <span class="font-medium text-navy-900"><?= esc($thread['podcast_title']) ?></span></p>
        </div>
    </div>
</div>

<!-- Alpine.js Component for Polling -->
<!-- Alpine.js Component for Polling & Reply State -->
<!-- Alpine.js Component for Collapsible UI, Filtering, & Snippets -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col relative" style="height: 75vh;" 
     x-data="{ 
        replies: [], 
        replyingToId: null,
        replyingToName: null,
        expandedReplies: [], 
        
        // NEW: Chat Filter State
        currentFilter: 'all', // 'all', 'unreplied', 'replied'
        
        // NEW: Filter Logic
        get topLevelReplies() { 
            let parents = this.replies.filter(r => r.parent_reply_id === null); 
            
            if (this.currentFilter === 'unreplied') {
                return parents.filter(p => !this.hasOfficialReply(p.id));
            }
            if (this.currentFilter === 'answered') {
                return parents.filter(p => this.hasOfficialReply(p.id));
            }
            return parents;
        },
        
        getNestedReplies(parentId) { 
            return this.replies.filter(r => parseInt(r.parent_reply_id) === parseInt(parentId)); 
        },
        
        // Helper: Checks if an Admin has already replied to this Parent Text
        hasOfficialReply(parentId) {
            return this.getNestedReplies(parentId).some(r => r.is_official);
        },
        
        toggleExpand(id) {
            if(this.expandedReplies.includes(id)) {
                this.expandedReplies = this.expandedReplies.filter(e => e !== id);
            } else {
                this.expandedReplies.push(id);
            }
        },

        setReply(id, name) {
            this.replyingToId = id;
            this.replyingToName = name;
            this.$refs.messageInput.focus();
        },
        
        cancelReply() {
            this.replyingToId = null;
            this.replyingToName = null;
        },

        // NEW: World-Class Quick Snippets
        insertSnippet(text) {
            this.$refs.messageInput.value = this.$refs.messageInput.value + text;
            this.$refs.messageInput.focus();
        },

        async fetchReplies() {
            const response = await fetch('<?= site_url('admin/forum/api/replies/' . $thread['id']) ?>');
            this.replies = await response.json();
        },
        init() {
            this.fetchReplies();
            setInterval(() => this.fetchReplies(), 5000);
        }
     }">

    <!-- Top Action Bar (Filters & Status) -->
    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between rounded-t-xl z-10">
        
        <div class="flex bg-gray-200/50 p-1 rounded-lg">
            <button @click="currentFilter = 'all'" :class="currentFilter === 'all' ? 'bg-white shadow text-navy-900' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 rounded-md text-xs font-bold transition-all">
                All Comments
            </button>
            <button @click="currentFilter = 'unreplied'" :class="currentFilter === 'unreplied' ? 'bg-white shadow text-red-600' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 rounded-md text-xs font-bold transition-all flex items-center">
                <span x-show="currentFilter === 'unreplied'" class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5 animate-pulse"></span> Needs Reply
            </button>
            <button @click="currentFilter = 'answered'" :class="currentFilter === 'answered' ? 'bg-white shadow text-green-600' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 rounded-md text-xs font-bold transition-all">
                Answered
            </button>
        </div>

        <div class="text-xs font-medium text-gray-500">
            <i class="ph-fill ph-users mr-1"></i> <span x-text="replies.length"></span> Total Interactions
        </div>
    </div>

    <!-- Chat Timeline -->
    <div class="flex-1 p-6 overflow-y-auto space-y-6 bg-gray-50/50" x-ref="chatBox">
        
        <!-- Empty State if Filter yields 0 results -->
        <div x-show="topLevelReplies.length === 0" style="display:none;" class="flex flex-col items-center justify-center h-full text-gray-400">
            <i class="ph ph-check-circle text-5xl mb-2 text-gray-300"></i>
            <p>You're all caught up here!</p>
        </div>

        <template x-for="parentReply in topLevelReplies" :key="parentReply.id">
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm relative group transition-all hover:border-gold-300">
                
                <!-- NEW: Unanswered Badge -->
                <div x-show="!hasOfficialReply(parentReply.id)" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                    Awaiting Reply
                </div>

                <!-- The Parent Text -->
                <div class="flex items-start">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0" :class="parentReply.is_official ? 'bg-gold-500' : 'bg-navy-900'">
                        <span x-text="parentReply.first_name.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center">
                            <span class="text-sm font-bold text-navy-900" x-text="parentReply.first_name + ' ' + parentReply.last_name"></span>
                            <template x-if="parentReply.is_official"><i class="ph-fill ph-seal-check text-gold-400 ml-1 text-sm" title="Verified Author"></i></template>
                            <span class="text-xs text-gray-400 ml-3" x-text="parentReply.time_ago"></span>
                        </div>
                        <p class="text-[15px] leading-relaxed text-gray-700 mt-2" x-text="parentReply.message"></p>
                        
                        <!-- Action Row -->
                        <div class="mt-4 flex items-center space-x-5">
                            <button @click="setReply(parentReply.id, parentReply.first_name)" class="text-xs font-bold text-gray-500 hover:text-gold-500 flex items-center transition-colors bg-gray-50 px-3 py-1.5 rounded-md hover:bg-gold-50">
                                <i class="ph-bold ph-arrow-u-down-left mr-1.5"></i> Reply to this
                            </button>
                            
                            <template x-if="getNestedReplies(parentReply.id).length > 0">
                                <button @click="toggleExpand(parentReply.id)" class="text-xs font-bold text-navy-600 hover:text-navy-800 flex items-center transition-colors">
                                    <i class="ph-bold mr-1.5" :class="expandedReplies.includes(parentReply.id) ? 'ph-caret-up' : 'ph-caret-down'"></i>
                                    <span x-text="expandedReplies.includes(parentReply.id) ? 'Hide Replies' : 'View ' + getNestedReplies(parentReply.id).length + ' Replies'"></span>
                                </button>
                            </template>
                            
                            <!-- Future Pin Feature Prototype -->
                            <button class="text-xs text-gray-400 hover:text-gray-600 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" title="Pin to top">
                                <i class="ph-fill ph-push-pin"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Nested Replies -->
                <div x-show="expandedReplies.includes(parentReply.id)" x-collapse class="mt-5 pl-14 space-y-4 border-l-2 border-gray-100">
                    <template x-for="nested in getNestedReplies(parentReply.id)" :key="nested.id">
                        <div class="flex items-start">
                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0" :class="nested.is_official ? 'bg-gold-500 ring-2 ring-gold-100' : 'bg-gray-400'">
                                <span x-text="nested.first_name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="ml-3 flex-1 bg-gray-50 rounded-lg p-3.5 border border-gray-100">
                                <div class="flex items-center mb-1">
                                    <span class="text-xs font-bold text-navy-900" x-text="nested.first_name + ' ' + nested.last_name"></span>
                                    <template x-if="nested.is_official"><i class="ph-fill ph-seal-check text-gold-400 ml-1 text-xs"></i></template>
                                    <span class="text-xs text-gray-400 ml-auto" x-text="nested.time_ago"></span>
                                </div>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    <span class="font-bold text-gold-500 mr-1" x-text="'@' + nested.parent_first_name"></span>
                                    <span x-text="nested.message"></span>
                                </p>
                                <button @click="setReply(parentReply.id, nested.first_name)" class="text-[11px] font-bold text-gray-400 hover:text-gold-500 mt-2 transition-colors uppercase tracking-wider">
                                    Reply
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Dynamic Reply Box -->
    <div class="p-4 bg-white border-t border-gray-200 rounded-b-xl relative z-20 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        
        <!-- Replying To Indicator -->
        <div x-show="replyingToId !== null" style="display: none;" class="absolute -top-9 left-4 bg-navy-900 text-white text-xs font-bold px-3 py-1.5 rounded-t-lg flex items-center shadow-md">
            <i class="ph-bold ph-arrow-u-down-left mr-2 text-gold-400"></i> Replying to <span x-text="replyingToName" class="ml-1 text-gold-400"></span>
            <button @click="cancelReply()" type="button" class="ml-4 text-gray-400 hover:text-white"><i class="ph-bold ph-x"></i></button>
        </div>

        <form action="<?= site_url('admin/forum/reply/' . $thread['id']) ?>" method="POST" class="flex items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="parent_reply_id" :value="replyingToId">
            
            <div class="flex-1 relative mr-3">
                <textarea name="message" x-ref="messageInput" rows="2" required :placeholder="replyingToName ? 'Type your reply to ' + replyingToName + '...' : 'Type a general comment to the community...'" class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-gold-500 focus:border-gold-500 resize-none shadow-inner"></textarea>
                
                <!-- WORLD CLASS: Quick Snippets Dropdown -->
                <div x-data="{ open: false }" class="absolute right-2 bottom-2">
                    <button @click.prevent="open = !open" type="button" class="p-2 text-gray-400 hover:text-gold-500 hover:bg-gold-50 rounded-lg transition-colors" title="Quick Replies">
                        <i class="ph-fill ph-lightning text-lg"></i>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" style="display: none;" class="absolute bottom-12 right-0 w-64 bg-white rounded-lg shadow-xl border border-gray-100 overflow-hidden text-left z-50">
                        <div class="px-3 py-2 bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">Saved Snippets</div>
                        <button @click.prevent="insertSnippet('Great question! I highly recommend listening to Episode 4 where we cover this exact topic in depth. '); open = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50 transition-colors">Point to Episode 4</button>
                        <button @click.prevent="insertSnippet('Thanks for sharing your thoughts! The key takeaway here is that Grace is a free gift. '); open = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">Grace Definition</button>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold px-6 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center h-[52px]">
                <i class="ph-fill ph-paper-plane-right text-xl"></i>
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
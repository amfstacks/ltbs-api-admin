<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <a href="<?= site_url('admin/forum') ?>" class="text-gray-500 hover:text-navy-900 mr-4"><i class="ph-bold ph-arrow-left text-xl"></i></a>
        <div>
            <h1 class="text-xl font-bold text-navy-900"><?= esc($thread['title']) ?></h1>
            <p class="text-sm text-gray-500 mt-1">Podcast: <span class="font-medium text-navy-900"><?= esc($thread['podcast_title']) ?></span></p>
        </div>
    </div>
</div>

<!-- Alpine.js Component for Polling -->
<!-- Alpine.js Component for Polling & Reply State -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col" style="height: 70vh;" 
     x-data="{ 
        replies: [], 
        replyingToId: null,
        replyingToName: null,
        expandedReplies: [], // Tracks which top-level texts are expanded
        
        // Filter helpers for our UI
        get topLevelReplies() { return this.replies.filter(r => r.parent_reply_id === null); },
        getNestedReplies(parentId) { return this.replies.filter(r => parseInt(r.parent_reply_id) === parseInt(parentId)); },
        
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

        async fetchReplies() {
            const response = await fetch('<?= site_url('admin/forum/api/replies/' . $thread['id']) ?>');
            this.replies = await response.json();
        },
        init() {
            this.fetchReplies();
            setInterval(() => this.fetchReplies(), 5000);
        }
     }">

    <!-- Original Question Header -->
    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-start">
        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mr-4 flex-shrink-0">
            <?= strtoupper(substr($thread['first_name'], 0, 1)) ?>
        </div>
        <div>
            <p class="text-sm font-bold text-navy-900"><?= esc($thread['first_name'] . ' ' . $thread['last_name']) ?> <span class="text-xs font-normal text-gray-500 ml-2">Original Question</span></p>
            <p class="text-sm text-gray-700 mt-1"><?= esc($thread['title']) ?></p>
        </div>
    </div>

    <!-- Chat Timeline -->
    <div class="flex-1 p-6 overflow-y-auto space-y-6 bg-gray-50" x-ref="chatBox">
        
        <!-- Loop ONLY Top-Level Replies -->
        <template x-for="parentReply in topLevelReplies" :key="parentReply.id">
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative group">
                
                <!-- The Parent Text -->
                <div class="flex items-start">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0" :class="parentReply.is_official ? 'bg-gold-500' : 'bg-gray-400'">
                        <span x-text="parentReply.first_name.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center">
                            <span class="text-sm font-bold text-navy-900" x-text="parentReply.first_name + ' ' + parentReply.last_name"></span>
                            <template x-if="parentReply.is_official"><i class="ph-fill ph-seal-check text-gold-400 ml-1 text-xs"></i></template>
                            <span class="text-xs text-gray-400 ml-3" x-text="parentReply.time_ago"></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1" x-text="parentReply.message"></p>
                        
                        <!-- Action Row (Reply & Expand) -->
                        <div class="mt-3 flex items-center space-x-4">
                            <button @click="setReply(parentReply.id, parentReply.first_name)" class="text-xs font-bold text-gray-500 hover:text-gold-500 flex items-center transition-colors">
                                <i class="ph-bold ph-arrow-u-down-left mr-1"></i> Reply
                            </button>
                            
                            <!-- Toggle Button (Only shows if nested replies exist) -->
                            <template x-if="getNestedReplies(parentReply.id).length > 0">
                                <button @click="toggleExpand(parentReply.id)" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center transition-colors">
                                    <i class="ph-bold mr-1" :class="expandedReplies.includes(parentReply.id) ? 'ph-caret-up' : 'ph-caret-down'"></i>
                                    <span x-text="expandedReplies.includes(parentReply.id) ? 'Hide Replies' : 'View ' + getNestedReplies(parentReply.id).length + ' Replies'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Nested Replies (Collapsible Box) -->
                <div x-show="expandedReplies.includes(parentReply.id)" x-collapse class="mt-4 pl-14 space-y-4 border-l-2 border-gray-100">
                    <template x-for="nested in getNestedReplies(parentReply.id)" :key="nested.id">
                        
                        <div class="flex items-start">
                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0" :class="nested.is_official ? 'bg-gold-500' : 'bg-gray-300'">
                                <span x-text="nested.first_name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="ml-3 flex-1 bg-gray-50 rounded-lg p-3">
                                <div class="flex items-center">
                                    <span class="text-xs font-bold text-navy-900" x-text="nested.first_name + ' ' + nested.last_name"></span>
                                    <template x-if="nested.is_official"><i class="ph-fill ph-seal-check text-gold-400 ml-1 text-xs"></i></template>
                                    <span class="text-xs text-gray-400 ml-2" x-text="nested.time_ago"></span>
                                </div>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span class="font-bold text-gold-500 mr-1" x-text="'@' + nested.parent_first_name"></span>
                                    <span x-text="nested.message"></span>
                                </p>
                                <button @click="setReply(parentReply.id, nested.first_name)" class="text-xs font-bold text-gray-500 hover:text-gold-500 mt-2 flex items-center transition-colors">
                                    <i class="ph-bold ph-arrow-u-down-left mr-1"></i> Reply
                                </button>
                            </div>
                        </div>

                    </template>
                </div>

            </div>
        </template>
    </div>

    <!-- Dynamic Reply Box -->
    <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl relative">
        
        <!-- Replying To Indicator -->
        <div x-show="replyingToId !== null" style="display: none;" class="absolute -top-10 left-4 bg-gold-100 text-gold-800 text-xs font-bold px-3 py-1.5 rounded-t-lg flex items-center border border-gold-200 border-b-0">
            <i class="ph-bold ph-arrow-u-down-left mr-2"></i> Replying to <span x-text="replyingToName" class="ml-1"></span>
            <button @click="cancelReply()" type="button" class="ml-3 text-gold-600 hover:text-gold-900"><i class="ph-bold ph-x"></i></button>
        </div>

        <form action="<?= site_url('admin/forum/reply/' . $thread['id']) ?>" method="POST" class="flex items-end">
            <?= csrf_field() ?>
            
            <!-- Hidden Parent ID -->
            <input type="hidden" name="parent_reply_id" :value="replyingToId">
            
            <textarea name="message" x-ref="messageInput" rows="2" required :placeholder="replyingToName ? 'Type your reply to ' + replyingToName + '...' : 'Type your official response to the main question...'" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 resize-none mr-4"></textarea>
            
            <button type="submit" class="bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold py-3 px-6 rounded-lg transition-colors flex items-center h-[50px]">
                <i class="ph-fill ph-paper-plane-right mr-2"></i> Send
            </button>
        </form>
    </div>

</div>

<?= $this->endSection() ?>
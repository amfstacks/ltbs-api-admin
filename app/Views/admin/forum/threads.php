<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <a href="<?= site_url('admin/forum') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors"><i class="ph-bold ph-arrow-left text-xl"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-navy-900"><?= esc($podcast['title']) ?></h1>
            <p class="text-sm text-gray-500 mt-1">Manage all discussions for this specific teaching.</p>
        </div>
    </div>
</div>

<!-- Alpine.js handles the Tab Filtering instantly -->
<div x-data="{ currentTab: 'awaiting' }">
    
    <!-- The Filter Tabs -->
    <div class="border-b border-gray-200 mb-6 flex space-x-8">
        <button @click="currentTab = 'awaiting'" 
                :class="currentTab === 'awaiting' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors flex items-center">
            Awaiting Reply 
            <span class="ml-2 bg-red-100 text-red-600 py-0.5 px-2.5 rounded-full text-xs"><?= $awaitingCount ?></span>
        </button>

        <button @click="currentTab = 'answered'" 
                :class="currentTab === 'answered' ? 'border-gold-500 text-gold-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors">
            Answered
        </button>

        <button @click="currentTab = 'all'" 
                :class="currentTab === 'all' ? 'border-navy-900 text-navy-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors">
            All Discussions (<?= count($threads) ?>)
        </button>
    </div>

    <!-- The Thread List -->
    <div class="space-y-4">
        <?php if(empty($threads)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <p>No questions have been asked on this podcast yet.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($threads as $t): ?>
            <!-- x-show logic checks if the thread belongs in the currently active tab -->
            <div x-show="currentTab === 'all' || (currentTab === 'awaiting' && <?= $t['is_awaiting'] ? 'true' : 'false' ?>) || (currentTab === 'answered' && <?= !$t['is_awaiting'] ? 'true' : 'false' ?>)" 
                 x-transition
                 class="bg-white rounded-xl shadow-sm border <?= $t['is_awaiting'] ? 'border-red-200 border-l-4 border-l-red-500' : 'border-gray-100 border-l-4 border-l-gold-500' ?> p-5 flex justify-between items-center hover:shadow-md transition-shadow">
                
                <div class="flex-1">
                    <div class="flex items-center mb-1">
                        <?php if($t['is_awaiting']): ?>
                            <span class="text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded uppercase tracking-wider mr-3">Action Required</span>
                        <?php else: ?>
                            <span class="text-xs font-bold text-gold-600 bg-gold-50 px-2 py-0.5 rounded uppercase tracking-wider mr-3">Answered</span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500"><i class="ph-fill ph-clock mr-1"></i> <?= date('M d, g:i A', strtotime($t['updated_at'])) ?></span>
                    </div>
                    <h3 class="text-lg font-bold text-navy-900"><?= esc($t['title']) ?></h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Asked by <span class="font-medium text-navy-900"><?= esc($t['first_name'] . ' ' . $t['last_name']) ?></span> • <?= $t['reply_count'] ?> total replies
                    </p>
                </div>

                <a href="<?= site_url('admin/forum/view/' . $t['id']) ?>" class="ml-6 px-6 py-2 bg-navy-900 text-white text-sm font-bold rounded-lg hover:bg-navy-800 transition-colors flex items-center whitespace-nowrap">
                    View Thread <i class="ph-bold ph-arrow-right ml-2"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->endSection() ?>
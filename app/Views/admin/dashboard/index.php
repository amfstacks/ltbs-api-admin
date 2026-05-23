<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-navy-900">Welcome back, <?= esc(session()->get('first_name')) ?>!</h1>
    <p class="text-gray-500 mt-1">Here is what is happening across your platform today.</p>
</div>

<!-- Changed lg:grid-cols-4 to xl:grid-cols-5 so all 5 active cards fit perfectly -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
    
    <!-- Total Podcasts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-gold-50 text-gold-500 mr-4">
            <i class="ph-fill ph-microphone-stage text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Your Podcasts</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= number_format($totalPodcasts) ?></h3>
        </div>
    </div>

    <!-- Total Listens -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-blue-50 text-blue-600 mr-4">
            <i class="ph-fill ph-headphones text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Total Listens</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= number_format($totalPlays) ?></h3>
        </div>
    </div>

    <!-- Forum Discussions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-purple-50 text-purple-600 mr-4">
            <i class="ph-fill ph-chats-circle text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Forum Discussions</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= number_format($totalComments) ?></h3>
        </div>
    </div>

    <!-- Total Likes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-red-50 text-red-600 mr-4">
            <i class="ph-fill ph-heart text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Total Likes</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= number_format($totalLikes) ?></h3>
        </div>
    </div>

    <!-- Total Bookmarks -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-teal-50 text-teal-600 mr-4">
            <i class="ph-fill ph-bookmark-simple text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Total Bookmarks</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= number_format($totalBookmarks) ?></h3>
        </div>
    </div>

</div>

<!-- Recent Uploads Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-navy-900">Recent Uploads</h2>
        <a href="<?= site_url('admin/podcasts') ?>" class="text-sm font-medium text-gold-500 hover:text-gold-600">View All</a>
    </div>
    
    <?php if(empty($recentPodcasts)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="ph ph-folder-open text-4xl mb-2"></i>
            <p>No podcasts uploaded yet. Click 'View All' to add your first teaching.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <tbody>
                    <?php foreach($recentPodcasts as $pod): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded bg-navy-900 flex items-center justify-center overflow-hidden mr-3">
                                        <?php if($pod['cover_image_url']): ?>
                                            <img src="<?= media_url($pod['cover_image_url']) ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <i class="ph-fill ph-book-open text-gold-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-navy-900"><?= esc($pod['title']) ?></p>
                                        <p class="text-xs text-gray-500"><?= esc($pod['category_name']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 text-right">
                                <?php if($pod['status'] === 'published'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Published</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Draft</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy-900">Forum Dashboard</h1>
    <p class="text-sm text-gray-500 mt-1">Select a teaching below to manage its community discussions.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Podcast Teaching</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Threads</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if(empty($podcasts)): ?>
                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400"><i class="ph ph-chats-circle text-4xl mb-2"></i><p>No community discussions yet.</p></td></tr>
            <?php endif; ?>
            
            <?php foreach ($podcasts as $p): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="h-12 w-12 rounded bg-navy-900 flex items-center justify-center overflow-hidden mr-4 flex-shrink-0">
                                <?php if($p['cover_image_url']): ?>
                                    <img src="<?= base_url($p['cover_image_url']) ?>" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <i class="ph-fill ph-book-open text-gold-400"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-navy-900"><?= esc($p['title']) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= esc($p['category_name'] ?? 'Uncategorized') ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-lg font-bold text-navy-900"><?= $p['total_threads'] ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if($p['awaiting_count'] > 0): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                                <?= $p['awaiting_count'] ?> Awaiting Reply
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                <i class="ph-bold ph-check mr-1.5"></i> All Answered
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="<?= site_url('admin/forum/podcast/' . $p['id']) ?>" class="bg-navy-900 hover:bg-navy-800 text-white text-sm font-bold py-2 px-4 rounded transition-colors">
                            View Discussions
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy-900">Forum Inbox</h1>
    <p class="text-sm text-gray-500 mt-1">Manage and respond to app user questions regarding your teachings.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discussion</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Podcast</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if(empty($threads)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No active discussions found.</td></tr>
            <?php endif; ?>
            
            <?php foreach ($threads as $t): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                   <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div>
                                <p class="text-sm font-bold text-navy-900">
                                    <?= esc($t['title']) ?>
                                    <?php if($t['unread_count'] > 0): ?>
                                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                                            <?= $t['unread_count'] ?> New
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Started by <?= esc($t['first_name'] . ' ' . $t['last_name']) ?> • <?= $t['reply_count'] ?> Total Replies</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><i class="ph-fill ph-headphones mr-1 text-gold-500"></i> <?= esc($t['podcast_title']) ?></td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 uppercase tracking-wider">
                            <?= esc($t['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="<?= site_url('admin/forum/view/' . $t['id']) ?>" class="bg-navy-900 hover:bg-navy-800 text-white text-sm font-bold py-1.5 px-4 rounded transition-colors">
                            Open Thread
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
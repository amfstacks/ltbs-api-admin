<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Flagged Content</h1>
        <p class="text-sm text-gray-500 mt-1">Review and moderate community content reported by users.</p>
    </div>
</div>

<div class="space-y-6">
    <?php if(empty($reports)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <i class="ph-fill ph-shield-check text-5xl text-green-400 mb-3"></i>
            <h3 class="text-lg font-bold text-navy-900">Community is Safe</h3>
            <p class="text-gray-500">No content is currently flagged for review.</p>
        </div>
    <?php else: ?>
        
        <?php foreach($reports as $report): ?>
            <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6 flex flex-col md:flex-row gap-6 items-start transition-all hover:shadow-md">
                
                <div class="flex-shrink-0 w-full md:w-64 border-r border-gray-100 pr-6">
                    <span class="px-2 py-1 bg-red-50 text-red-700 text-xs font-bold uppercase rounded border border-red-100 mb-3 inline-block">
                        <i class="ph-fill ph-flag mr-1"></i> <?= str_replace('_', ' ', $report['content_type']) ?>
                    </span>
                    <p class="text-sm text-gray-600 mb-1">Reported by:</p>
                    <p class="font-bold text-navy-900 text-sm"><?= esc($report['first_name'] . ' ' . $report['last_name']) ?></p>
                    <p class="text-xs text-gray-400 mt-2"><i class="ph-fill ph-clock mr-1"></i> <?= date('M j, Y g:i A', strtotime($report['created_at'])) ?></p>
                </div>

                <div class="flex-1">
                    <h4 class="text-sm font-bold text-gray-800 mb-2 uppercase tracking-wider text-xs">Reason for Flagging:</h4>
                    <p class="text-sm text-red-600 font-medium bg-red-50 p-3 rounded-lg border border-red-100 mb-4">
                        "<?= esc($report['reason']) ?>"
                    </p>

                    <h4 class="text-sm font-bold text-gray-800 mb-2 uppercase tracking-wider text-xs">Reported Text:</h4>
                    <div class="text-sm text-gray-700 bg-gray-50 p-4 rounded-lg border border-gray-200 italic">
                        [The actual user comment or forum post text will appear here once the database JOIN is completed for content ID: <?= $report['content_id'] ?>]
                    </div>
                </div>

                <div class="flex-shrink-0 flex flex-col gap-2 w-full md:w-auto">
                    <form action="<?= site_url('admin/moderation/resolve/' . $report['id']) ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" name="action" value="delete_content" class="w-full flex items-center justify-center px-4 py-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 font-bold rounded-lg transition-colors text-sm" onclick="return confirm('Delete this content permanently?')">
                            <i class="ph-bold ph-trash mr-2"></i> Delete Content
                        </button>
                    </form>
                    
                    <form action="<?= site_url('admin/moderation/resolve/' . $report['id']) ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" name="action" value="dismiss_flag" class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold rounded-lg transition-colors text-sm">
                            <i class="ph-bold ph-check mr-2"></i> Dismiss Flag
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Review History</h1>
        <p class="text-sm text-gray-500 mt-1">A permanent ledger of your past QA decisions.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap text-left">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Podcast Title</th>
                    <th class="px-6 py-4">Decision Date</th>
                    <th class="px-6 py-4 text-center">My Decision</th>
                    <th class="px-6 py-4 text-center">Global Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                
                <?php if(empty($history)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <i class="ph-fill ph-folder-open text-4xl text-gray-300 mb-2"></i>
                            <p>You haven't reviewed any podcasts yet.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($history as $item): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-navy-900"><?= esc($item['title']) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600"><?= date('M j, Y g:i A', strtotime($item['decision_date'])) ?></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if($item['my_decision'] == 'approved'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="ph-bold ph-check mr-1 mt-0.5"></i> Approved
                                </span>
                            <?php elseif($item['my_decision'] == 'changes_requested'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="ph-bold ph-pencil-simple mr-1 mt-0.5"></i> Changes Requested
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="ph-bold ph-x mr-1 mt-0.5"></i> Rejected
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                                <?= esc($item['global_status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
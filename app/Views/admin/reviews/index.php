<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Review Queue</h1>
        <p class="text-sm text-gray-500 mt-1">Listen, evaluate, and approve teachings before they go live.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-l-4 border-l-red-500 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-red-50 text-red-600 mr-4"><i class="ph-fill ph-warning-circle text-2xl"></i></div>
        <div>
            <p class="text-sm font-medium text-gray-500">Awaiting Your Review</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= $awaiting_count ?></h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-l-4 border-l-yellow-500 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600 mr-4"><i class="ph-fill ph-clock-countdown text-2xl"></i></div>
        <div>
            <p class="text-sm font-medium text-gray-500">Pending Other Approvals</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= $pending_others_count ?></h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-l-4 border-l-green-500 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-green-50 text-green-600 mr-4"><i class="ph-fill ph-check-circle text-2xl"></i></div>
        <div>
            <p class="text-sm font-medium text-gray-500">Your Approved (Last 30 Days)</p>
            <h3 class="text-2xl font-bold text-navy-900"><?= $recent_approved_count ?></h3>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <h2 class="text-lg font-bold text-navy-900">Pending Podcasts</h2>
        <select class="text-sm border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 py-1.5 pl-3 pr-8">
            <option>Needs My Action</option>
            <option>Waiting on Others</option>
            <option>All Pending</option>
        </select>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap text-left">
            <thead>
                <tr class="bg-white border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Podcast Details</th>
                    <th class="px-6 py-4">Submitted By</th>
                    <th class="px-6 py-4">Consensus</th>
                    <th class="px-6 py-4 text-center">My Status</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                
                <?php if(empty($podcasts)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="ph-fill ph-check-circle text-4xl text-green-400 mb-2"></i>
                            <p>You're all caught up! No podcasts pending review.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($podcasts as $podcast): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 rounded bg-navy-100 flex items-center justify-center text-navy-900 overflow-hidden">
                                    <?php if($podcast['cover_image_url']): ?>
                                        <img src="<?= base_url('uploads/covers/' . $podcast['cover_image_url']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="ph-fill ph-microphone-stage text-xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-navy-900"><?= esc($podcast['title']) ?></div>
                                    <div class="text-xs text-gray-500 flex items-center mt-0.5">
                                        <i class="ph ph-clock mr-1"></i> <?= gmdate("i:s", $podcast['duration']) ?> • 
                                        <span class="ml-2 text-gold-600 font-medium">Under Review</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-navy-900 font-medium"><?= esc($podcast['first_name'] . ' ' . $podcast['last_name']) ?></div>
                            <div class="text-xs text-gray-500"><?= date('M j, Y g:i A', strtotime($podcast['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-1">
                                <div class="h-2 w-6 rounded-full <?= $podcast['review_count'] >= 1 ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                                <div class="h-2 w-6 rounded-full <?= $podcast['review_count'] >= 2 ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                                <div class="h-2 w-6 rounded-full <?= $podcast['review_count'] >= 3 ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1"><?= $podcast['review_count'] ?> / 3 Approvals</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if($podcast['my_review_status'] == 'approved'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="ph-bold ph-check mr-1 mt-0.5"></i> Approved
                                </span>
                            <?php elseif($podcast['my_review_status'] == 'changes_requested'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Changes Requested
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Action Required
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="<?= site_url('admin/reviews/process/' . $podcast['id']) ?>" class="inline-flex items-center px-4 py-2 bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                                <i class="ph-bold ph-play mr-2"></i> Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
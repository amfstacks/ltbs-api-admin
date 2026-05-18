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
        <div class="p-3 rounded-lg bg-red-50 text-red-600 mr-4">
            <i class="ph-fill ph-warning-circle text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Awaiting Your Review</p>
            <h3 class="text-2xl font-bold text-navy-900">4</h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-l-4 border-l-yellow-500 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600 mr-4">
            <i class="ph-fill ph-clock-countdown text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Pending Other Approvals</p>
            <h3 class="text-2xl font-bold text-navy-900">7</h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-l-4 border-l-green-500 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-green-50 text-green-600 mr-4">
            <i class="ph-fill ph-check-circle text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Your Approved (Last 30 Days)</p>
            <h3 class="text-2xl font-bold text-navy-900">24</h3>
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
                
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0 rounded bg-navy-100 flex items-center justify-center text-navy-900">
                                <i class="ph-fill ph-microphone-stage text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-bold text-navy-900">The Power of Forgiveness</div>
                                <div class="text-xs text-gray-500 flex items-center mt-0.5">
                                    <i class="ph ph-clock mr-1"></i> 45:20 • <span class="ml-2 text-gold-600 font-medium">Sermons</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-navy-900 font-medium">Pastor John</div>
                        <div class="text-xs text-gray-500">2 hours ago</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-1">
                            <div class="h-2 w-6 rounded-full bg-green-500" title="Approved by Author"></div>
                            <div class="h-2 w-6 rounded-full bg-gray-200" title="Pending Reviewer 1"></div>
                            <div class="h-2 w-6 rounded-full bg-gray-200" title="Pending Reviewer 2"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">1 / 3 Approvals</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                            Action Required
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="<?= site_url('admin/reviews/process/1') ?>" class="inline-flex items-center px-4 py-2 bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                            <i class="ph-bold ph-play mr-2"></i> Review
                        </a>
                    </td>
                </tr>

                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0 rounded bg-navy-100 flex items-center justify-center text-navy-900">
                                <i class="ph-fill ph-microphone-stage text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-bold text-navy-900">Understanding Grace</div>
                                <div class="text-xs text-gray-500 flex items-center mt-0.5">
                                    <i class="ph ph-clock mr-1"></i> 32:10 • <span class="ml-2 text-gold-600 font-medium">Bible Study</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-navy-900 font-medium">Elder Samuel</div>
                        <div class="text-xs text-gray-500">Yesterday</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-1">
                            <div class="h-2 w-6 rounded-full bg-green-500"></div>
                            <div class="h-2 w-6 rounded-full bg-green-500"></div>
                            <div class="h-2 w-6 rounded-full bg-gray-200"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">2 / 3 Approvals</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="ph-bold ph-check mr-1"></i> Approved
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="<?= site_url('admin/reviews/process/2') ?>" class="text-gray-400 hover:text-navy-900 transition-colors">
                            <i class="ph-bold ph-eye text-xl"></i>
                        </a>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
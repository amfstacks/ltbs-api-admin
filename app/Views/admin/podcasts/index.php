<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Podcasts</h1>
        <p class="text-sm text-gray-500 mt-1">Manage all uploaded teachings and audio files.</p>
    </div>
    <a href="<?= site_url('admin/podcasts/wizard') ?>" class="bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center">
        <i class="ph-bold ph-microphone-stage mr-2"></i> Upload Podcast
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teaching</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if(empty($podcasts)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <i class="ph ph-headphones text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500 font-medium">No podcasts uploaded yet.</p>
                            <p class="text-sm text-gray-400 mt-1">Click the upload button above to add your first teaching.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                
                <?php foreach ($podcasts as $podcast): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 rounded-lg bg-navy-900 flex items-center justify-center overflow-hidden shadow-sm">
                                    <?php if($podcast['cover_image_url']): ?>
                                        <img class="h-12 w-12 object-cover" src="<?= base_url($podcast['cover_image_url']) ?>" alt="Cover">
                                    <?php else: ?>
                                        <i class="ph-fill ph-book-open text-gold-400 text-xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-navy-900"><?= esc($podcast['title']) ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5">/<?= esc($podcast['slug']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                <?= esc($podcast['category_name'] ?? 'Uncategorized') ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if($podcast['status'] === 'published'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span> Published
                                </span>
                            <?php elseif($podcast['status'] === 'draft'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-gray-100 text-gray-800 border border-gray-200">
                                    <span class="w-1.5 h-1.5 bg-gray-500 rounded-full mr-1.5"></span> Draft
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span> Hidden
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if($podcast['published_at']): ?>
                                <?= date('M d, Y', strtotime($podcast['published_at'])) ?>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Not Published</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-4 transition-colors" title="Edit">
                                <i class="ph ph-pencil-simple text-lg"></i>
                            </a>
                            <a href="#" onclick="return confirm('Are you sure you want to delete this teaching?');" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                <i class="ph ph-trash text-lg"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
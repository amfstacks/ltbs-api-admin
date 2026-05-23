<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Podcasts</h1>
        <p class="text-sm text-gray-500 mt-1">Manage all uploaded teachings and audio files.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row items-center gap-4">
        <form action="<?= site_url('admin/podcasts') ?>" method="GET" class="relative w-full sm:w-auto">
            <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search teachings..." 
                   class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 text-sm">
            <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-lg"></i>
            
            <?php if(!empty($search)): ?>
                <a href="<?= site_url('admin/podcasts') ?>" class="absolute right-3 top-2.5 text-gray-400 hover:text-red-500 transition-colors">
                    <i class="ph-bold ph-x"></i>
                </a>
            <?php endif; ?>
        </form>

        <a href="<?= site_url('admin/podcasts/wizard') ?>" class="w-full sm:w-auto bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center justify-center whitespace-nowrap">
            <i class="ph-bold ph-microphone-stage mr-2"></i> Upload Podcast
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teaching</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th> -->
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engagement</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if(empty($podcasts)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="ph ph-magnifying-glass text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500 font-medium"><?= !empty($search) ? 'No teachings found matching your search.' : 'No podcasts uploaded yet.' ?></p>
                            <?php if(empty($search)): ?>
                                <p class="text-sm text-gray-400 mt-1">Click the upload button above to add your first teaching.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                
                <?php foreach ($podcasts as $podcast): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 rounded-lg bg-navy-900 flex items-center justify-center overflow-hidden shadow-sm">
                                    <?php if($podcast['cover_image_url']): ?>
                                        <img class="h-12 w-12 object-cover" src="<?= media_url($podcast['cover_image_url']) ?>" alt="Cover">
                                    <?php else: ?>
                                        <i class="ph-fill ph-book-open text-gold-400 text-xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-navy-900"><?= esc($podcast['title']) ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5 max-w-[200px] truncate" title="<?= esc($podcast['slug']) ?>">/<?= esc($podcast['slug']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                <?= esc($podcast['category_name'] ?? 'Uncategorized') ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if($podcast['status'] === 'published'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span> Published
                                </span>
                            <?php elseif($podcast['status'] === 'in_review'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200" title="Awaiting QA Approval">
                                    <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full mr-1.5"></span> In Review
                                </span>
                            <?php elseif($podcast['status'] === 'processing'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200" title="Server is optimizing audio">
                                    <i class="ph-bold ph-spinner animate-spin mr-1.5"></i> Processing
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



                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-4 text-xs font-medium text-gray-500">
                                
                                <div class="flex items-center gap-1.5 cursor-help hover:text-navy-900 transition-colors" title="Total Plays">
                                    <i class="ph-fill ph-play-circle text-gray-400 text-sm"></i>
                                    <span><?= number_format($podcast['play_count']) ?></span>
                                </div>
                                
                                <div class="flex items-center gap-1.5 cursor-help hover:text-red-500 transition-colors" title="Total Likes">
                                    <i class="ph-fill ph-heart text-gray-400 text-sm"></i>
                                    <span><?= number_format($podcast['like_count']) ?></span>
                                </div>
                                
                                <div class="flex items-center gap-1.5 cursor-help hover:text-blue-500 transition-colors" title="Total Comments">
                                    <i class="ph-fill ph-chats text-gray-400 text-sm"></i>
                                    <span><?= number_format($podcast['comment_count']) ?></span>
                                </div>

                                <div class="flex items-center gap-1.5 cursor-help hover:text-green-500 transition-colors" title="QA Approvals">
                                    <i class="ph-fill ph-shield-check text-gray-400 text-sm"></i>
                                    <span class="<?= $podcast['review_count'] >= 3 ? 'text-green-600 font-bold' : '' ?>">
                                        <?= $podcast['review_count'] ?>/3
                                    </span>
                                </div>

                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="<?= site_url('admin/podcasts/media/' . $podcast['id']) ?>" class="text-gold-600 hover:text-gold-700 mr-3 transition-colors" title="Manage Audio Media">
                                <i class="ph-fill ph-headphones text-lg"></i>
                            </a>

                            <a href="<?= site_url('admin/podcasts/edit/' . $podcast['id']) ?>" class="text-blue-600 hover:text-blue-900 mr-3 transition-colors" title="Edit Details">
                                <i class="ph ph-pencil-simple text-lg"></i>
                            </a>
                            
                            <a href="<?= site_url('admin/podcasts/delete/' . $podcast['id']) ?>" onclick="return confirm('Are you sure you want to delete this teaching and all its media files?');" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                <i class="ph ph-trash text-lg"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 mt-auto">
            <?= $pager->links() ?> 
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
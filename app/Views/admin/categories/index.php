<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Categories</h1>
        <p class="text-sm text-gray-500 mt-1">Manage podcast categories like Faith, Grace, etc.</p>
    </div>
    <a href="<?= site_url('admin/categories/form') ?>" class="bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center">
        <i class="ph-bold ph-plus mr-2"></i> Add Category
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if(empty($categories)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No categories found. Create one above!</td></tr>
            <?php endif; ?>
            
            <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if($cat['icon_url']): ?>
                            <img src="<?= media_url($cat['icon_url']) ?>" alt="icon" class="h-10 w-10 rounded bg-navy-900 object-cover">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center text-gray-400">
                                <i class="ph ph-image text-xl"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-navy-900"><?= esc($cat['name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= esc($cat['slug']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="<?= site_url('admin/categories/form/'.$cat['id']) ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="ph ph-pencil-simple text-lg"></i></a>
                        <a href="<?= site_url('admin/categories/delete/'.$cat['id']) ?>" onclick="return confirm('Delete this category?');" class="text-red-600 hover:text-red-900"><i class="ph ph-trash text-lg"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
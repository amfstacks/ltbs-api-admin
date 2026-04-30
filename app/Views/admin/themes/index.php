<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Themes</h1>
        <p class="text-sm text-gray-500 mt-1">Manage podcast themes and topics.</p>
    </div>
    <a href="<?= site_url('admin/themes/form') ?>" class="bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center">
        <i class="ph-bold ph-plus mr-2"></i> Add Theme
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
            <?php if(empty($themes)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No themes found. Create one above!</td></tr>
            <?php endif; ?>
            
            <?php foreach ($themes as $theme): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if($theme['icon_url']): ?>
                            <img src="<?= $theme['icon_url'] ?>" alt="icon" class="h-10 w-10 rounded bg-navy-900 object-cover">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center text-gray-400">
                                <i class="ph ph-image text-xl"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-navy-900"><?= esc($theme['name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= esc($theme['slug']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="<?= site_url('admin/themes/form/'.$theme['id']) ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="ph ph-pencil-simple text-lg"></i></a>
                        <a href="<?= site_url('admin/themes/delete/'.$theme['id']) ?>" onclick="return confirm('Delete this theme?');" class="text-red-600 hover:text-red-900"><i class="ph ph-trash text-lg"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
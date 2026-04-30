<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-navy-900">Team Management</h1>
        <p class="text-sm text-gray-500 mt-1">Manage Authors and Super Admins.</p>
    </div>
    <a href="<?= site_url('admin/users/create') ?>" class="bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center">
        <i class="ph-bold ph-user-plus mr-2"></i> Invite Member
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-navy-900 flex items-center justify-center text-gold-400 font-bold">
                                <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-bold text-navy-900"><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= esc($user['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                        <?= esc($user['role']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if($user['status'] === 'active'): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800">Active</span>
                        <?php elseif($user['status'] === 'pending'): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800">Pending Invite</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-100 text-red-800">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <?php if($user['id'] !== session()->get('user_id')): ?>
                            <a href="#" class="text-red-600 hover:text-red-900 transition-colors" title="Disable User"><i class="ph ph-prohibit text-lg"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
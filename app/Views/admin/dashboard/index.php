<?= $this->extend('admin/layout/master') ?>

<?= $this->section('content') ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-navy-900">Welcome back, <?= esc(session()->get('first_name')) ?>!</h1>
    <p class="text-gray-500 mt-1">Here is what is happening across your platform today.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-blue-50 text-blue-600 mr-4">
            <i class="ph-fill ph-headphones text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Total Listens</p>
            <h3 class="text-2xl font-bold text-navy-900">24,592</h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-green-50 text-green-600 mr-4">
            <i class="ph-fill ph-users text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Active App Users</p>
            <h3 class="text-2xl font-bold text-navy-900">1,204</h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-gold-50 text-gold-500 mr-4">
            <i class="ph-fill ph-microphone-stage text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Published Podcasts</p>
            <h3 class="text-2xl font-bold text-navy-900">86</h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-lg bg-purple-50 text-purple-600 mr-4">
            <i class="ph-fill ph-chats-circle text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Forum Discussions</p>
            <h3 class="text-2xl font-bold text-navy-900">342</h3>
        </div>
    </div>

</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-navy-900">Recent Uploads</h2>
        <a href="#" class="text-sm font-medium text-gold-500 hover:text-gold-600">View All</a>
    </div>
    <div class="text-center py-12 text-gray-400">
        <i class="ph ph-folder-open text-4xl mb-2"></i>
        <p>No podcasts uploaded yet. The data table will appear here.</p>
    </div>
</div>

<?= $this->endSection() ?>
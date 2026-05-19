<aside class="w-64 bg-navy-900 text-gray-300 flex flex-col flex-shrink-0 transition-all duration-300 shadow-2xl z-20">
    <div class="h-16 flex items-center px-6 bg-navy-800 border-b border-navy-700">
        <i class="ph-fill ph-book-open text-2xl text-gold-400 mr-3"></i>
        <span class="text-white font-bold text-lg tracking-wide">Admin Portal</span>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
        
        <a href="<?= site_url('admin/dashboard') ?>" 
           class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/dashboard') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
            <i class="ph ph-squares-four text-xl mr-3 <?= url_is('admin/dashboard') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
            Dashboard
        </a>

        <?php if(in_array(session()->get('role'), ['reviewer', 'superadmin'])): ?>
            <div class="pt-4 pb-1">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Quality Control</p>
            </div>

            <a href="<?= site_url('admin/reviews') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/reviews') || url_is('admin/reviews/process/*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <div class="relative">
                    <i class="ph ph-headphones text-xl mr-3 <?= url_is('admin/reviews') || url_is('admin/reviews/process/*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                    <span class="absolute -top-1 -right-1 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-navy-900"></span>
                </div>
                Review Queue
            </a>

            <a href="<?= site_url('admin/reviews/history') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/reviews/history') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-clock-counter-clockwise text-xl mr-3 <?= url_is('admin/reviews/history') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Review History
            </a>

            <a href="<?= site_url('admin/moderation/flagged') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/moderation*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-shield-warning text-xl mr-3 <?= url_is('admin/moderation*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Flagged Content
            </a>

            <a href="<?= site_url('admin/reviews/guidelines') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/reviews/guidelines') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-list-checks text-xl mr-3 <?= url_is('admin/reviews/guidelines') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                QA Guidelines
            </a>
        <?php endif; ?>

        <?php if(in_array(session()->get('role'), ['author', 'superadmin'])): ?>
            <div class="pt-4 pb-1">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</p>
            </div>

            <a href="<?= site_url('admin/podcasts') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/podcasts*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-microphone-stage text-xl mr-3 <?= url_is('admin/podcasts*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Podcasts
            </a>
        <?php endif; ?>

        <?php if(session()->get('role') === 'superadmin'): ?>
            <a href="<?= site_url('admin/categories') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/categories*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-folders text-xl mr-3 <?= url_is('admin/categories*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Categories
            </a>
            <a href="<?= site_url('admin/themes') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/themes*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-bookmark-simple text-xl mr-3 <?= url_is('admin/themes*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Themes
            </a>
        <?php endif; ?>

        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Community</p>
        </div>

        <a href="<?= site_url('admin/forum') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/forum*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
            <i class="ph ph-chats-circle text-xl mr-3 <?= url_is('admin/forum*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
            Forum Threads
        </a>

        <?php if(session()->get('role') === 'superadmin'): ?>
            <div class="pt-4 pb-1">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">System</p>
            </div>

            <a href="<?= site_url('admin/users') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/users*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-users text-xl mr-3 <?= url_is('admin/users*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Authors & Admins
            </a>

            <a href="<?= site_url('admin/settings') ?>" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= url_is('admin/settings*') ? 'bg-gold-500 text-navy-900 font-bold shadow-md' : 'hover:bg-navy-800 hover:text-white' ?>">
                <i class="ph ph-gear text-xl mr-3 <?= url_is('admin/settings*') ? 'text-navy-900' : 'text-gray-400' ?>"></i>
                Global Settings
            </a>
        <?php endif; ?>

    </nav>

    <div class="p-4 bg-navy-800 border-t border-navy-700">
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-gold-400 flex items-center justify-center text-navy-900 font-bold shadow-sm">
                <?= strtoupper(substr(session()->get('first_name') ?? 'U', 0, 1)) ?>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-white"><?= esc(session()->get('first_name')) ?></p>
                <p class="text-xs text-gold-400 capitalize"><?= esc(session()->get('role')) ?></p>
            </div>
        </div>
    </div>
</aside>
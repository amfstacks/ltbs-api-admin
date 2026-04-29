<header class="h-16 bg-white shadow-sm border-b border-gray-100 flex items-center justify-between px-6 flex-shrink-0 z-10 relative">
    
    <div class="flex items-center">
        <h2 class="text-lg font-semibold text-gray-800"><?= esc($title ?? 'Dashboard') ?></h2>
    </div>

    <div class="flex items-center space-x-4">
        
        <button class="relative p-2 text-gray-400 hover:text-navy-900 transition-colors">
            <i class="ph ph-bell text-xl"></i>
            <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="h-6 w-px bg-gray-200"></div>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false" class="flex items-center space-x-2 focus:outline-none">
                <span class="text-sm font-medium text-gray-700"><?= esc(session()->get('first_name')) ?></span>
                <i class="ph ph-caret-down text-gray-400 text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-3 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
                 style="display: none;">
                
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-navy-900">
                    <i class="ph ph-user mr-2"></i> My Profile
                </a>
                
                <div class="border-t border-gray-100 my-1"></div>
                
                <a href="<?= site_url('admin/logout') ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="ph ph-sign-out mr-2"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>
<div class="mt-8 pt-6 border-t border-gray-800 flex items-center justify-between">
    <h3 class="text-lg font-bold text-gray-300">Share this Series</h3>
    <div class="flex gap-4">
        <?php
            $shareUrl = urlencode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            $shareTitle = urlencode($series['title'] ?? 'SOLOREEL');
        ?>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" class="text-blue-600 hover:text-blue-500">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </a>
        <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
        </a>
        <a href="https://api.whatsapp.com/send?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" class="text-green-500 hover:text-green-400">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 0 5.385 0 12.031c0 2.128.55 4.195 1.595 6.012L.265 23.735l5.856-1.534A11.97 11.97 0 0012.031 24c6.645 0 12-5.385 12-12.031S18.676 0 12.031 0zm0 22.008c-1.802 0-3.565-.483-5.11-1.401l-.367-.217-3.805.998.998-3.71-.238-.378a9.986 9.986 0 01-1.528-5.308c0-5.545 4.512-10.057 10.057-10.057 5.545 0 10.058 4.512 10.058 10.057 0 5.546-4.513 10.057-10.058 10.057z"/></svg>
        </a>
    </div>
</div>

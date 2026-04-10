<?php
/**
 * SIDIK-TI - Pagination Helper UI
 * 
 * @param int $currentPage The current page number
 * @param bool $hasMore Whether there are more items to fetch
 * @param string $baseUrl The base URL for links
 * @param array $extraParams Any extra query params to preserve
 */
function renderPagination($currentPage, $hasMore, $baseUrl, $extraParams = []) {
    $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
    $nextPage = $hasMore ? $currentPage + 1 : null;
    
    $buildUrl = function($page) use ($baseUrl, $extraParams) {
        $params = array_merge($extraParams, ['page' => $page]);
        return $baseUrl . '?' . http_build_query($params);
    };
    ?>
    <div class="flex items-center justify-between px-8 py-4 bg-slate-50/50 border-t border-outline-variant/10">
        <div class="text-xs text-on-surface-variant font-medium">
            Page <span class="font-bold text-primary"><?php echo $currentPage; ?></span>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($prevPage): ?>
                <a href="<?php echo $buildUrl($prevPage); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-outline-variant/20 rounded-xl text-xs font-bold text-on-surface hover:bg-slate-50 transition-all">
                    <span class="material-symbols-outlined text-sm">chevron_left</span> Previous
                </a>
            <?php else: ?>
                <button disabled class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 border border-outline-variant/10 rounded-xl text-xs font-bold text-slate-400 cursor-not-allowed">
                    <span class="material-symbols-outlined text-sm">chevron_left</span> Previous
                </button>
            <?php endif; ?>

            <?php if ($nextPage): ?>
                <a href="<?php echo $buildUrl($nextPage); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-outline-variant/20 rounded-xl text-xs font-bold text-on-surface hover:bg-slate-50 transition-all">
                    Next <span class="material-symbols-outlined text-sm">chevron_right</span>
                </a>
            <?php else: ?>
                <button disabled class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 border border-outline-variant/10 rounded-xl text-xs font-bold text-slate-400 cursor-not-allowed">
                    Next <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

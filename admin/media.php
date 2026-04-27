<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/MediaManager.php';

$mm = new MediaManager();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        $mm->uploadFile($_FILES['file'], $_POST['title']);
        $msg = "فایل با موفقیت آپلود شد.";
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    $mm->deleteMedia($_GET['delete']);
    echo "<script>window.location='media.php';</script>";
    exit;
}

$media = $mm->getAllMedia();
?>

<div class="bg-white p-5 rounded-xl border border-[#e2e8f0] mb-6">
    <h1 class="text-lg font-semibold text-[#1e293b]">مدیریت فایل‌ها و رسانه</h1>
</div>

<?php if ($msg): ?>
<div class="bg-[#dcfce7] border border-[#bbf7d0] text-[#166534] px-4 py-3 rounded-lg mb-6 text-sm font-medium"><?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] px-4 py-3 rounded-lg mb-6 text-sm font-medium"><?= $err ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-[#e2e8f0]">
            <div class="p-5 border-b border-[#f1f5f9]">
                <h3 class="text-base font-semibold text-[#1e293b]">آپلود فایل جدید</h3>
            </div>
            <div class="p-5">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">عنوان رسانه</label>
                        <input type="text" name="title" required class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-[#475569] mb-2">انتخاب فایل</label>
                        <input type="file" name="file" required class="w-full text-sm text-[#64748b] file:mr-0 file:ml-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-[#e2e8f0] rounded-lg p-2 focus:outline-none">
                        <p class="text-[11px] text-[#94a3b8] mt-2">فرمت‌های مجاز: jpg, png, mp4, mp3, pdf</p>
                    </div>
                    <button type="submit" class="w-full bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg text-[13px] transition-colors">
                        آپلود فایل
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl border border-[#e2e8f0] min-h-[400px]">
             <div class="p-5 border-b border-[#f1f5f9]">
                <h3 class="text-base font-semibold text-[#1e293b]">فایل‌های آپلود شده</h3>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php foreach ($media as $m): ?>
                        <div class="border border-[#e2e8f0] rounded-xl p-3 flex flex-col justify-between items-center bg-white hover:border-[#cbd5e1] hover:shadow-sm transition-all group relative overflow-hidden">
                            <?php if ($m['file_type'] === 'photo'): ?>
                            <img src="..<?= $m['file_path'] ?>" class="h-28 w-full object-cover rounded-lg mb-3">
                            <?php else: ?>
                            <div class="h-28 w-full bg-[#f8fafc] rounded-lg mb-3 flex items-center justify-center text-[#94a3b8] group-hover:text-[#64748b] transition-colors border border-[#f1f5f9]">
                                <i class="bi bi-file-earmark-<?= $m['file_type'] ?> text-4xl"></i>
                            </div>
                            <?php endif; ?>
                            <div class="text-center w-full">
                                <p class="text-[13px] font-medium text-[#1e293b] truncate mb-1" title="<?= htmlspecialchars($m['title']) ?>"><?= htmlspecialchars($m['title']) ?></p>
                                <p class="text-[11px] text-[#64748b] truncate mb-3" dir="ltr"><?= round($m['file_size'] / 1024, 1) ?> KB</p>
                                <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('واقعاً از حذف این فایل مطمئن هستید؟')" class="text-white text-[11px] font-medium bg-red-500 hover:bg-red-600 rounded px-2 py-1 transition-colors block w-full">
                                    حذف فایل
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($media)): ?>
                        <div class="col-span-full py-16 text-center text-[#64748b] text-[13px]">
                            فایلی تاکنون آپلود نشده است.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

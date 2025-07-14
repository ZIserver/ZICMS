<?php
require_once '.././function/functions.php';
require_once 'admin_header.php';
if (!isLoggedIn() || !isAdmin($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
// 获取配置项函数（已存在）
function get_config($key) {
    global $conn;
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc()['setting_value'] : null;
}
// 更新配置项函数（已存在）
function update_config($key, $value) {
    global $conn;
    $stmt = $conn->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param("ss", $key, $value);
    return $stmt->execute();
}
// 处理LOGO上传
function handleLogoUpload() {
    $errors = [];
    $success = '';
    
    // 设置上传目录（相对于当前文件的路径）
    $upload_dir = dirname(__DIR__) . '/uploads/';
    
    // 创建上传目录（如果不存在）
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $errors[] = "无法创建上传目录";
            return compact('errors', 'success');
        }
    }
    
    // 检查目录可写权限
    if (!is_writable($upload_dir)) {
        $errors[] = "上传目录不可写，请检查权限";
        return compact('errors', 'success');
    }
    
    // 检查文件上传是否正常
    if (!isset($_FILES['site_logo']) || $_FILES['site_logo']['error'] !== UPLOAD_ERR_OK) {
        // 不是文件上传错误，可能是用户没有选择文件
        return compact('errors', 'success');
    }
    
    $file = $_FILES['site_logo'];
    
    // 验证文件类型
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = "只允许上传 JPG, PNG, GIF 或 WebP 格式的图片";
        return compact('errors', 'success');
    }
    
    // 生成安全的文件名
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target_path = $upload_dir . $filename;
    
    // 移动上传的文件
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // 删除旧LOGO文件（如果存在）
        $old_logo = get_config('site_logo');
        if ($old_logo && file_exists(dirname(__DIR__) . '/' . $old_logo)) {
            @unlink(dirname(__DIR__) . '/' . $old_logo);
        }
        
        // 保存相对路径到数据库
        $relative_path = 'uploads/' . $filename;
        if (update_config('site_logo', $relative_path)) {
            $success = "LOGO上传成功";
        } else {
            // 数据库更新失败，删除已上传的文件
            @unlink($target_path);
            $errors[] = "数据库更新失败";
        }
    } else {
        $errors[] = "文件上传失败，请重试";
    }
    
    return compact('errors', 'success');
}
// 处理表单提交
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 先处理LOGO上传
    $upload_result = handleLogoUpload();
    $errors = array_merge($errors, $upload_result['errors']);
    if (!empty($upload_result['success'])) {
        $success = $upload_result['success'];
    }
    
    // 处理其他配置项
    $configs = [
        'site_name' => trim($_POST['site_name']),
        'beian_number' => trim($_POST['beian_number']),
        'e_url' => trim($_POST['e_url']),
        'e_id' => trim($_POST['e_id']),
        'e_mi' => trim($_POST['e_mi']),
        'qq' => trim($_POST['qq']),
        'site_url' => filter_input(INPUT_POST, 'site_url', FILTER_VALIDATE_URL),
        'reg_enabled' => isset($_POST['reg_enabled']) ? 1 : 0,
        'is_card' => isset($_POST['is_card']) ? 1 : 0,
        'hot_articles' => isset($_POST['hot_articles']) ? 1 : 0,
        'comment_approve' => isset($_POST['comment_approve']) ? 1 : 0
    ];
    
    // 验证必填项
    if (empty($configs['site_name'])) {
        $errors[] = "站点名称不能为空";
    }
    
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            foreach ($configs as $key => $value) {
                if (!update_config($key, $value)) {
                    throw new Exception("更新配置项 $key 失败");
                }
            }
            
            $conn->commit();
            if (empty($success)) {
                $success = '系统配置已成功更新！';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = '配置更新失败：' . $e->getMessage();
        }
    }
}
// 获取当前配置
$currentConfig = [
    'site_name' => get_config('site_name') ?? site_name,
    'site_logo' => get_config('site_logo'),
    'e_url' => get_config('e_url'),
    'e_id' => get_config('e_id'),
    'e_mi' => get_config('e_mi'),
    'qq' => get_config('qq'),
    'beian_number' => get_config('beian_number') ?? '',
    'site_url' => get_config('site_url') ?? 'http://localhost',
    'reg_enabled' => get_config('reg_enabled') ?? 1,
    'comment_approve' => get_config('comment_approve') ?? 1,
    'is_card' => get_config('is_card') ?? 1,
    'hot_articles' => get_config('hot_articles') ?? 1
];
?>
<div class="card">
    <div class="card-body">
        <h2 class="h5 mb-4">系统设置</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <!-- 基础设置 -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <i class="bi bi-gear me-2"></i>基础设置
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">站点名称 <span class="text-danger">*</span></label>
                                <input type="text" name="site_name" 
                                       value="<?= htmlspecialchars($currentConfig['site_name']) ?>" 
                                       class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">站点LOGO</label>
                                <input type="file" name="site_logo" class="form-control" accept="image/*">
                                <small class="text-muted">支持格式: JPG, PNG, GIF | 建议尺寸: 200×60px</small>
                                
                                <?php if ($currentConfig['site_logo']): ?>
                                    <div class="mt-2">
                                        <p>当前LOGO:</p>
                                        <img src="../<?= htmlspecialchars($currentConfig['site_logo']) ?>" 
                                             style="max-height: 60px;" class="img-thumbnail">
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="remove_logo" name="remove_logo">
                                            <label class="form-check-label" for="remove_logo">删除当前LOGO</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">备案号</label>
                                <input type="text" name="beian_number" 
                                       value="<?= htmlspecialchars($currentConfig['beian_number']) ?>" 
                                       class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">QQ</label>
                                <input type="text" name="qq" 
                                       value="<?= htmlspecialchars($currentConfig['qq']) ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">站点URL</label>
                                <input type="url" name="site_url" 
                                       value="<?= htmlspecialchars($currentConfig['site_url']) ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">易支付URL</label>
                                <input type="url" name="e_url" 
                                       value="<?= htmlspecialchars($currentConfig['e_url']) ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">易支付商户ID</label>
                                <input type="text" name="e_id" 
                                       value="<?= htmlspecialchars($currentConfig['e_id']) ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">易支付商户密钥</label>
                                <input type="password" name="e_mi" 
                                       value="<?= htmlspecialchars($currentConfig['e_mi']) ?>" 
                                       class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 功能开关 -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <i class="bi bi-toggle-on me-2"></i>功能开关
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="reg_enabled" 
                                       id="regEnabled" <?= $currentConfig['reg_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="regEnabled">
                                    开放用户注册
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="comment_approve" 
                                       id="commentApprove" <?= $currentConfig['comment_approve'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="commentApprove">
                                    评论需要审核
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_card" 
                                       id="is_card" <?= $currentConfig['is_card'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_card">
                                    列表/卡片
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="hot_articles" 
                                       id="hot_articles" <?= $currentConfig['hot_articles'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hot_articles">
                                    文章热榜
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save me-2"></i>保存设置
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function delSlide(id) {
    if(confirm('确定要删除这张幻灯片吗？')) {
        document.getElementById('delIdInput').value = id;
        document.getElementById('delIdInput').closest('form').submit();
    }
}
function editSlide(el) {
    const card = $(el).closest('.slider-card');
    const id = card.data('id');
    const title = card.find('.card-title').text();
    const desc = card.find('.text-muted').text();
    const url = card.find('.text-success').text();
    const sort = card.find('.card-subtitle').text();
    
    $('#slideId').val(id);
    $('[name="slide_title"]').val(title);
    $('[name="slide_desc"]').val(desc);
    $('[name="slide_url"]').val(url);
    $('[name="slide_sort"]').val(sort);
    $(el).closest('form').find('[type="submit1"]').text('更新幻灯片');
}
</script>

            </div>
        </div>
    </div>
</div>



